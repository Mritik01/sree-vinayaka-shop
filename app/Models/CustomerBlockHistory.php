<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// one permanent row per block/unblock action, ever — see CustomerBlockService (the only place
// these are created) and the migration's own docblock for why this table is append-only
class CustomerBlockHistory extends Model
{
    // Eloquent's default pluralization would guess "customer_block_histories" ("history" ->
    // "histories") — the migration deliberately names the table the singular-looking
    // "customer_block_history" instead, so this needs to be explicit.
    protected $table = 'customer_block_history';

    protected $fillable = [
        'user_id',
        'action',
        'reason',
        'message',
        'notes',
        'admin_id',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function reasonLabel(): ?string
    {
        return $this->reason ? (User::BLOCK_REASONS[$this->reason] ?? $this->reason) : null;
    }
}
