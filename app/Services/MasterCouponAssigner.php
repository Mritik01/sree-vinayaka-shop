<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MasterCouponAssigner
{
    // called once, right after a brand-new account is created — attaches every currently
    // active "master coupon" that still has slots left. Wrapped per-coupon in a row lock so
    // two signups racing for the last slot can't both squeeze in and blow past the limit.
    public static function assignFor(User $user): void
    {
        $masterCoupons = Coupon::where('is_active', true)
            ->where('expires_at', '>=', now())
            ->whereNotNull('auto_assign_limit')
            ->get();

        foreach ($masterCoupons as $coupon) {
            DB::transaction(function () use ($coupon, $user) {
                $locked = Coupon::whereKey($coupon->id)->lockForUpdate()->first();

                if ($locked->autoAssignSlotsRemaining() > 0) {
                    $user->assignedCoupons()->syncWithoutDetaching([$locked->id]);
                }
            });
        }
    }
}
