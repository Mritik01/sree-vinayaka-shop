<?php

namespace App\Services;

use App\Models\ShopSetting;
use App\Models\User;

class RewardService
{
    // called once, right when an order first transitions to 'delivered' — advances the
    // customer's progress toward their next free gift, banking a claim at the threshold
    public static function recordDelivery(User $user): void
    {
        $settings = ShopSetting::current();

        if (!$settings->rewardConfigured()) {
            return;
        }

        $required = max(1, $settings->reward_orders_required);
        $progress = $user->reward_progress + 1;
        $freeGifts = $user->free_gifts_available;

        if ($progress >= $required) {
            $freeGifts++;
            $progress = 0;
        }

        $user->forceFill([
            'reward_progress' => $progress,
            'free_gifts_available' => $freeGifts,
        ])->save();
    }

    // what the account page / checkout page need to render the gift box + progress stamps
    public static function statusFor(User $user): array
    {
        $settings = ShopSetting::current();
        $required = max(1, $settings->reward_orders_required);
        $product = $settings->rewardGiftProduct;

        return [
            'configured' => $settings->rewardConfigured(),
            'required' => $required,
            'progress' => min($user->reward_progress, $required),
            'available' => $user->free_gifts_available,
            'gift_label' => $settings->reward_gift_label ?: $product?->name ?? 'Free Gift',
            'gift_image' => $product ? asset($product->image) : null,
        ];
    }
}
