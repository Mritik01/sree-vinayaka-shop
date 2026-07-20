<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class ProductTag extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProductTag $tag) {
            if (empty($tag->slug)) {
                $tag->slug = static::uniqueSlugFor($tag->name);
            }
        });
    }

    protected static function uniqueSlugFor(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-".++$suffix;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function products(): BelongsToMany
    {
        // explicit pivot name — Laravel's alphabetical-default would be "product_product_tag"
        return $this->belongsToMany(Product::class, 'product_tag');
    }

    public function featuredCategories(): BelongsToMany
    {
        return $this->belongsToMany(FeaturedCategory::class);
    }
}
