<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserConsent extends Model
{
    protected $fillable = [
        'user_id',
        'context',
        'terms_version',
        'privacy_version',
        'ip_address',
        'user_agent',
        'accepted_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // records this specific acceptance event — always an insert, never an update, so the
    // audit trail can never lose a past consent (see class-level note on the migration)
    public static function record(
        int $userId,
        string $context,
        ?int $termsVersion,
        ?int $privacyVersion,
        ?string $ip,
        ?string $userAgent
    ): self {
        return static::create([
            'user_id' => $userId,
            'context' => $context,
            'terms_version' => $termsVersion,
            'privacy_version' => $privacyVersion,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'accepted_at' => now(),
        ]);
    }
}
