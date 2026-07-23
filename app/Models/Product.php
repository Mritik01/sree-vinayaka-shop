<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'description',
        'price',
        'discount_type',
        'discount_value',
        'type',
        'portions',
        'portion_prices',
        'weight',
        'tag',
        'search_tags',
        'image',
        'image_position',
        'color',
        'sort_order',
        'is_bestseller',
        'is_festival_special',
        'is_out_of_stock',
    ];

    protected $casts = [
        'is_bestseller' => 'boolean',
        'is_festival_special' => 'boolean',
        'is_out_of_stock' => 'boolean',
        'portions' => 'array',
        'portion_prices' => 'array',
        'search_tags' => 'array',
    ];

    public const PORTION_OPTIONS = [250, 500, 750, 1000];

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->slug)) {
                $product->slug = static::uniqueSlugFor($product->name);
            }
        });

        // keeps search_tags_flat in sync with search_tags on every save, regardless of which
        // code path wrote it — trims/dedupes/lowercases once here so search stays a plain
        // indexed LIKE query against one string column instead of needing JSON-aware SQL
        static::saving(function (Product $product) {
            $tags = collect($product->search_tags ?? [])
                ->map(fn ($tag) => trim((string) $tag))
                ->filter()
                ->unique(fn ($tag) => Str::lower($tag))
                ->values();

            $product->search_tags = $tags->all();
            $product->search_tags_flat = $tags->map(fn ($tag) => Str::lower($tag))->implode(' ');
        });
    }

    protected static function uniqueSlugFor(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-" . ++$suffix;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    // additive to the plain `category` string column above (which keeps driving bestseller/
    // festival-special grouping, breadcrumbs, etc. untouched) — this is the new multi-assign
    // relation for the admin Category Management screen and the mobile category panel
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    // powers the admin-curated Featured Categories shortcut row (a featured category maps to
    // one or more tags; a product can carry several) — independent of the `categories()`
    // taxonomy above, see app/Models/FeaturedCategory.php
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ProductTag::class, 'product_tag');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->latest();
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function isLoose(): bool
    {
        return $this->type === 'loose';
    }

    public function hasDiscount(): bool
    {
        return !empty($this->discount_type) && $this->discount_value > 0;
    }

    // the discounted per-unit price (per piece, or per 250g for loose products) — this is
    // what priceForPortion() multiplies by the portion ratio, so a discount flows through
    // to the cart/checkout/order total automatically without touching that pricing code
    public function discountedBasePrice(): int
    {
        return $this->applyDiscountTo((int) $this->price);
    }

    // "20% OFF" / "₹50 OFF" — shown as a corner badge on the product card/page
    public function discountBadgeLabel(): ?string
    {
        if (!$this->hasDiscount()) {
            return null;
        }

        return $this->discount_type === 'percentage'
            ? "{$this->discount_value}% OFF"
            : '₹'.number_format($this->discount_value).' OFF';
    }

    // loose products are normally priced per 250g, with every configured portion an exact
    // multiple of that base price (500g=2x, 750g=3x, 1kg=4x) — this fits genuinely bulk items
    // where price truly is proportional to weight (loose mithai, namkeen sold by the gram).
    // A pre-packaged item with its own fixed pack sizes (Surf Excel 500g/750g/1kg) usually
    // isn't a clean multiple like that, so portion_prices lets a specific portion override the
    // formula with its own real price instead — see portionOverridePrice(). Discount is applied
    // to whichever base value is in play (the 250g price, or the portion's own override) first,
    // then any remaining ratio — so a "20% off" loose product is 20% off at every portion either
    // way, not just the 250g one.
    public function priceForPortion(?int $grams): int
    {
        if ($this->isLoose() && $grams && ($override = $this->portionOverridePrice($grams)) !== null) {
            return $this->applyDiscountTo($override);
        }

        $basePrice = $this->discountedBasePrice();

        if (!$this->isLoose() || !$grams) {
            return $basePrice;
        }

        return $basePrice * (int) ($grams / 250);
    }

    // the pre-discount price for the same portion — used for the struck-through
    // "was ₹X" display alongside priceForPortion()'s discounted price
    public function originalPriceForPortion(?int $grams): int
    {
        if ($this->isLoose() && $grams && ($override = $this->portionOverridePrice($grams)) !== null) {
            return $override;
        }

        if (!$this->isLoose() || !$grams) {
            return (int) $this->price;
        }

        return (int) $this->price * (int) ($grams / 250);
    }

    // this portion's own admin-set price, if one was given — null falls back to the linear
    // "price * grams/250" formula in priceForPortion()/originalPriceForPortion() above
    public function portionOverridePrice(int $grams): ?int
    {
        $price = $this->portion_prices[$grams] ?? null;

        return $price !== null && $price !== '' ? (int) $price : null;
    }

    private function applyDiscountTo(int $price): int
    {
        if (!$this->hasDiscount()) {
            return $price;
        }

        if ($this->discount_type === 'percentage') {
            return (int) round($price * (1 - min($this->discount_value, 100) / 100));
        }

        return max(0, $price - (int) $this->discount_value);
    }

    // smallest configured portion — used when a compact UI (product card) adds
    // to cart without an inline picker
    public function defaultPortion(): ?int
    {
        if (!$this->isLoose()) {
            return null;
        }

        $options = $this->portions ?? [];

        return $options ? min($options) : self::PORTION_OPTIONS[0];
    }

    // "250g" / "500g" / "1kg" — the one place grams-to-label conversion lives on
    // the PHP side; window.portionLabel() in app.js mirrors this for Alpine contexts
    public static function portionLabel(int $grams): string
    {
        return $grams >= 1000 ? rtrim(rtrim(number_format($grams / 1000, 2), '0'), '.').'kg' : $grams.'g';
    }

    // every buyable variant of this product as {portion, label, price} — a loose product
    // expands to each of its configured portions; a piece product is a single portion-0 row.
    // Powers the admin "add product to order" picker (variant dropdown + live price) and lets
    // the server re-derive prices via priceForPortion() rather than trusting a client value.
    public function portionPriceList(): array
    {
        if (!$this->isLoose()) {
            return [[
                'portion' => 0,
                'label' => null,
                'price' => $this->priceForPortion(null),
            ]];
        }

        $options = $this->portions ?: [self::PORTION_OPTIONS[0]];
        sort($options);

        return array_map(fn ($grams) => [
            'portion' => (int) $grams,
            'label' => self::portionLabel((int) $grams),
            'price' => $this->priceForPortion((int) $grams),
        ], $options);
    }

    // shared shape for cart/checkout/drawer payloads — consolidates what used to be
    // copy-pasted per-item array literals across CartController, CheckoutController
    // and multiple Blade files
    public function cartRow(int $portion, int $quantity): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'image' => asset($this->image),
            'weight' => $this->weight,
            'tag' => $this->tag,
            'color' => $this->color,
            'type' => $this->type,
            'portions' => $this->portions ?? [],
            'portion' => $portion,
            'price' => $this->priceForPortion($portion ?: null),
            'quantity' => $quantity,
            'is_out_of_stock' => $this->is_out_of_stock,
        ];
    }
}
