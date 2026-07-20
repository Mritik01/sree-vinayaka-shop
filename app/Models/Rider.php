<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rider extends Model implements Authenticatable
{
    use AuthenticatableTrait, HasFactory;

    protected $fillable = [
        'name',
        'username',
        'password',
        'phone',
        'locale',
        'photo_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(RiderRating::class);
    }

    // computed live at query time, never cached — matches how Product's own review aggregates
    // (reviews_avg_rating/reviews_count) already work in this codebase, so a fresh rating shows
    // up immediately with no propagation/observer needed
    public function averageRating(): ?float
    {
        $avg = $this->ratings()->avg('rating');

        return $avg !== null ? round($avg, 1) : null;
    }

    public function ratingsCount(): int
    {
        return $this->ratings()->count();
    }

    // [5 => count, 4 => count, ..., 1 => count] — every star always present, even at 0, so the
    // dashboard's distribution bars don't need to guard against a missing key
    public function ratingDistribution(): array
    {
        $counts = $this->ratings()->selectRaw('rating, count(*) as c')->groupBy('rating')->pluck('c', 'rating');

        return collect(range(5, 1))->mapWithKeys(fn ($star) => [$star => $counts[$star] ?? 0])->all();
    }
}
