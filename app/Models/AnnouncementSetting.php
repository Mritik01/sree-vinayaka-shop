<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class AnnouncementSetting extends Model
{
    public const LANDING_PAGE_MODES = ['none', 'custom', 'discounted'];

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

    public static function current(): self
    {
        return static::firstOrCreate([]);
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
