<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\CustomerBlockHistory;
use App\Models\User;

// the only place a customer's block state is ever written — keeps the user row's "current
// state" columns and the permanent customer_block_history ledger from ever drifting apart,
// since every call here updates both in one place
class CustomerBlockService
{
    public static function block(User $user, Admin $admin, string $reason, ?string $message, ?string $notes): void
    {
        $user->forceFill([
            'is_blocked' => true,
            'block_reason' => $reason,
            'block_message' => $message,
            'block_notes' => $notes,
            'blocked_by_admin_id' => $admin->id,
            'blocked_at' => now(),
            'unblocked_by_admin_id' => null,
            'unblocked_at' => null,
        ])->save();

        CustomerBlockHistory::create([
            'user_id' => $user->id,
            'action' => 'blocked',
            'reason' => $reason,
            'message' => $message,
            'notes' => $notes,
            'admin_id' => $admin->id,
            'occurred_at' => now(),
        ]);
    }

    // never clears block_reason/block_message/blocked_by/blocked_at — those stay as "last known
    // block" context on the Account Status card even after unblocking (see the migration's
    // docblock). Re-blocking later simply overwrites them via block() above.
    public static function unblock(User $user, Admin $admin, ?string $notes = null): void
    {
        $user->forceFill([
            'is_blocked' => false,
            'unblocked_by_admin_id' => $admin->id,
            'unblocked_at' => now(),
        ])->save();

        CustomerBlockHistory::create([
            'user_id' => $user->id,
            'action' => 'unblocked',
            'reason' => $user->block_reason,
            'message' => null,
            'notes' => $notes,
            'admin_id' => $admin->id,
            'occurred_at' => now(),
        ]);
    }
}
