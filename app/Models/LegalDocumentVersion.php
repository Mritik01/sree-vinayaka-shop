<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalDocumentVersion extends Model
{
    public const TYPES = ['terms', 'privacy', 'refund', 'shipping'];

    protected $fillable = [
        'type',
        'version',
        'title',
        'content',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public static function current(string $type): ?self
    {
        return static::where('type', $type)->orderByDesc('version')->first();
    }
}
