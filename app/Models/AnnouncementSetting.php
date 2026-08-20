<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AnnouncementSetting extends Model
{
    public const LANDING_PAGE_MODES = ['none', 'custom', 'discounted'];

    private const CACHE_KEY = 'announcement_setting.current';

    private static ?self $cached = null;

    protected $fillable = [
        'is_enabled',
        'headline',
        'description',
        'button_text',
        'button_url',
        'image_path',
        'theme',
        'background_color',
        'text_color',
        'display_frequency',
        'show_close_button',
        'auto_close_seconds',
        'landing_page_mode',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'show_close_button' => 'boolean',
        'auto_close_seconds' => 'integer',
    ];

    // this singleton row is read on every page via AppServiceProvider's shared view data — was
    // previously a fresh, uncached SELECT every single request. Request-level static plus a
    // cross-request file cache (same pattern as ShopSetting::current()), invalidated instantly
    // by the saved/deleted hooks below rather than relying on the 6h TTL alone.
    public static function current(): self
    {
        return static::$cached ??= Cache::remember(self::CACHE_KEY, now()->addHours(6), fn () => static::firstOrCreate([]));
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    // a banner with nothing to say has no business popping up, even if left toggled on
    public function isLive(): bool
    {
        return $this->is_enabled && (filled($this->headline) || filled($this->description));
    }

    // 'custom' mode's hand-picked, admin-ordered product list — announcement_products.sort_order
    // is what drives the order, not insertion order, since sync() (Admin\AnnouncementController)
    // doesn't guarantee row order matches array order
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'announcement_products')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function hasLandingPage(): bool
    {
        return $this->landing_page_mode !== 'none';
    }

    // the actual products a visitor sees on the promo landing page — 'discounted' is a live
    // query (never a stored snapshot), so it can never go stale when a product's discount is
    // added or removed elsewhere; 'custom' is exactly what the admin picked, in their chosen
    // order; 'none' has no landing page at all, see hasLandingPage() above.
    public function landingProducts(): Collection
    {
        return match ($this->landing_page_mode) {
            'custom' => $this->products()
                ->withAvg('reviews', 'rating')->withCount('reviews')
                ->get(),
            'discounted' => Product::whereNotNull('discount_type')->where('discount_value', '>', 0)
                ->withAvg('reviews', 'rating')->withCount('reviews')
                ->orderBy('sort_order')
                ->get(),
            default => collect(),
        };
    }
}
