<?php

namespace App\Services;

use App\Jobs\SendAiSensyMessage;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AbandonedCartReminderService
{
    // reminder fires once, in a 1-hour window 24-25h after the cart's most recent activity —
    // a time-window query instead of an "already reminded" tracking column, so no new schema is
    // needed. Trade-off: if a scheduled run is ever missed, that user's window is skipped
    // entirely for this cycle — acceptable for a non-critical reminder, unlike order-critical
    // notifications (see OrderObserver).
    private const WINDOW_START_HOURS = 24;
    private const WINDOW_END_HOURS = 25;

    /**
     * Sends a WhatsApp reminder to every user whose cart's most recent activity falls in the
     * 24-25h window above. Returns the users that were just reminded.
     */
    public static function sendReminders(): Collection
    {
        $windowStart = now()->subHours(self::WINDOW_END_HOURS);
        $windowEnd = now()->subHours(self::WINDOW_START_HOURS);

        // one row per user: their single most-recently-touched cart_items.updated_at
        $staleUserIds = DB::table('cart_items')
            ->select('user_id', DB::raw('MAX(updated_at) as last_touched'))
            ->groupBy('user_id')
            ->havingBetween('last_touched', [$windowStart, $windowEnd])
            ->pluck('user_id');

        if ($staleUserIds->isEmpty()) {
            return collect();
        }

        // carts in this schema always belong to a real registered user (no guest cart) — still
        // require a verified phone since that's what confirms it's a real, reachable number
        $users = User::whereIn('id', $staleUserIds)
            ->whereNotNull('phone_verified_at')
            ->with('cart')
            ->get();

        foreach ($users as $user) {
            $phone = PhoneNumber::normalize((string) $user->phone);

            if (!PhoneNumber::isValidIndianMobile($phone)) {
                continue;
            }

            $itemCount = (int) $user->cart->sum('pivot.quantity');

            SendAiSensyMessage::dispatch('abandoned_cart', $phone, [$user->name, (string) $itemCount], $user->name);
        }

        return $users;
    }
}
