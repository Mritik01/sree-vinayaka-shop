<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivity extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'session_id',
        'event',
        'label',
        'path',
        'url',
        'product_id',
        'device_type',
        'browser',
        'platform',
        'ip_address',
        'user_agent',
        'referrer',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
