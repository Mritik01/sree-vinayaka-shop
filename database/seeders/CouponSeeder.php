<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'FIRST10',
                'description' => '10% off your first order',
                'discount_type' => 'percent',
                'discount_value' => 10,
                'usage_type' => 'once_per_user',
                'expires_at' => '2026-12-31 23:59:59',
                'is_active' => true,
            ],
            [
                'code' => 'MAKHAN50',
                'description' => 'Flat ₹50 off',
                'discount_type' => 'flat',
                'discount_value' => 50,
                'usage_type' => 'single_use',
                'expires_at' => '2026-08-31 23:59:59',
                'is_active' => true,
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::updateOrCreate(['code' => $coupon['code']], $coupon);
        }
    }
}
