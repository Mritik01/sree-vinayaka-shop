<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteVisit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'user_id',
        'device_type',
        'browser',
        'platform',
        'ip_address',
        'entry_path',
        'page_views',
        'first_seen',
        'last_seen',
    ];

    protected $casts = [
        'first_seen' => 'datetime',
        'last_seen' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
