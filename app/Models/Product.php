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
        'unit',
        'portions',
        'portion_prices',
        'portion_images',
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
        'portion_images' => 'array',
        'search_tags' => 'array',
    ];

    // shown to the admin as two separate checkbox groups (see admin/products/_form.blade.php),
    // filtered by whichever unit the product is set to — grams for a bulk/loose item like dal,
    // ml for a bottled liquid like handwash. Same set of sizes for both, just labeled differently
    // (portionLabel() renders this list as g/kg for weight, ml/L for volume) — PORTION_OPTIONS is
    // kept as a separate alias for readability wherever validation only needs "is this a
    // plausible portion value" (CartController etc.) without caring which unit it belongs to.
    public const WEIGHT_PORTION_OPTIONS = [25, 50, 100, 150, 200, 250, 400, 500, 650, 750, 800, 1000];
    public const VOLUME_PORTION_OPTIONS = [25, 50, 100, 150, 200, 250, 400, 500, 650, 750, 800, 1000];
    public const PORTION_OPTIONS = [25, 50, 100, 150, 200, 250, 400, 500, 650, 750, 800, 1000];

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

        // round(), not (int) cast — a plain cast truncates toward zero, so any portion smaller
        // than the 250 base (100ml, 125ml, 200g...) priced out to ₹0 unless it had its own
        // override. Matters a lot more now that most volume sizes (25ml-200ml) are under 250.
        return (int) round($basePrice * $grams / 250);
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

        return (int) round($this->price * $grams / 250);
    }

    // this portion's own admin-set price, if one was given — null falls back to the linear
    // "price * grams/250" formula in priceForPortion()/originalPriceForPortion() above
    public function portionOverridePrice(int $grams): ?int
    {
        $price = $this->portion_prices[$grams] ?? null;

        return $price !== null && $price !== '' ? (int) $price : null;
    }

    // this portion's own admin-set photo, if one was given — same idea as
    // portionOverridePrice() above, just for the image (a 25ml bottle doesn't look like a 1L
    // one). Returns a raw storage path, not asset()-wrapped — see imageForPortion() for that.
    public function portionOverrideImage(int $grams): ?string
    {
        return $this->portion_images[$grams] ?? null;
    }

    // the photo to show for a given portion — that portion's own override if it has one,
    // otherwise the product's normal image. Pass null for a non-loose product or when no
    // portion is selected yet.
    public function imageForPortion(?int $grams): string
    {
        if ($grams !== null && $this->isLoose() && ($override = $this->portionOverrideImage($grams))) {
            return $override;
        }

        return $this->image;
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

        return $options ? min($options) : ($this->unit === 'volume' ? self::VOLUME_PORTION_OPTIONS[0] : self::WEIGHT_PORTION_OPTIONS[0]);
    }

    // "250g" / "500g" / "1kg" (or, for a volume product, "250ml" / "500ml" / "1L") — the one
    // place grams/ml-to-label conversion lives on the PHP side; window.portionLabel() in app.js
    // mirrors this for Alpine contexts
    public static function portionLabel(int $value, string $unit = 'weight'): string
    {
        $bigUnit = $unit === 'volume' ? 'L' : 'kg';
        $smallUnit = $unit === 'volume' ? 'ml' : 'g';

        return $value >= 1000 ? rtrim(rtrim(number_format($value / 1000, 2), '0'), '.').$bigUnit : $value.$smallUnit;
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

        $options = $this->portions ?: [$this->defaultPortion()];
        sort($options);

        return array_map(fn ($grams) => [
            'portion' => (int) $grams,
            'label' => self::portionLabel((int) $grams, $this->unit),
            'price' => $this->priceForPortion((int) $grams),
        ], $options);
    }

    // real co-purchase data, not a same-category guess — other products that showed up in the
    // same order as this one, most-common first. Bounded to the last 6 months (order_items has
    // no direct order-date column, but its own created_at is set once at checkout and never
    // touched again except by removal, so it's a reliable proxy) so this stays fast and reflects
    // recent buying patterns rather than the entire order history as the shop grows. Excludes
    // removed line items and out-of-stock products — no point suggesting something unbuyable.
    public function frequentlyBoughtWith(int $limit = 4)
    {
        $orderIds = OrderItem::where('product_id', $this->id)
            ->whereNull('removed_at')
            ->where('created_at', '>=', now()->subMonths(6))
            ->limit(1000)
            ->pluck('order_id');

        if ($orderIds->isEmpty()) {
            return collect();
        }

        // over-fetch product ids since some may since have gone out of stock — filtered below
        $counts = OrderItem::whereIn('order_id', $orderIds)
            ->whereNull('removed_at')
            ->whereNotNull('product_id')
            ->where('product_id', '!=', $this->id)
            ->selectRaw('product_id, COUNT(DISTINCT order_id) as co_purchase_count')
            ->groupBy('product_id')
            ->orderByDesc('co_purchase_count')
            ->limit($limit * 3)
            ->pluck('co_purchase_count', 'product_id');

        if ($counts->isEmpty()) {
            return collect();
        }

        return self::whereIn('id', $counts->keys())
            ->where('is_out_of_stock', false)
            ->get()
            ->sortByDesc(fn (self $p) => $counts[$p->id])
            ->take($limit)
            ->values();
    }

    // how many distinct orders included this product in the last $days — real social proof
    // ("23 people ordered this this week") instead of a manual/fake-feeling badge
    public function recentOrderCount(int $days = 7): int
    {
        return (int) OrderItem::where('product_id', $this->id)
            ->whereNull('removed_at')
            ->where('created_at', '>=', now()->subDays($days))
            ->distinct()
            ->count('order_id');
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
            'image' => asset($this->imageForPortion($portion ?: null)),
            'weight' => $this->weight,
            'tag' => $this->tag,
            'color' => $this->color,
            'type' => $this->type,
            'unit' => $this->unit,
            'portions' => $this->portions ?? [],
            'portion' => $portion,
            'price' => $this->priceForPortion($portion ?: null),
            'quantity' => $quantity,
            'is_out_of_stock' => $this->is_out_of_stock,
        ];
    }
}
