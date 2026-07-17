<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // carries the previously hardcoded hero-slider slides into the new admin-managed table so
    // the homepage looks identical until the admin edits anything (inserts are parameterized,
    // so apostrophes are safe here — only DDL defaults have the escaping footgun)
    public function up(): void
    {
        $now = now();

        DB::table('hero_banners')->insert([
            [
                'image_path' => 'images/hero/hero-1.jpg',
                'eyebrow' => 'No. 1 Sweet Shop in Thuthibari',
                'title' => 'Celebrating Moments, Creating Sweet Memories',
                'subtitle' => 'Pure ingredients. Authentic taste. Made with love.',
                'button_text' => 'Explore Our Sweets',
                'button_url' => '#bestsellers',
                'sort_order' => 0,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'image_path' => 'images/hero/hero-2.jpg',
                'eyebrow' => 'Fresh Every Morning',
                'title' => 'Handcrafted Mithai, Straight From Thuthibari',
                'subtitle' => '100% pure ghee. Traditional recipes. No shortcuts, ever.',
                'button_text' => 'Order Now — Cash on Delivery',
                'button_url' => '#bestsellers',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'image_path' => 'images/hero/hero-3.jpg',
                'eyebrow' => 'Something For Everyone',
                'title' => 'From Classic Mithai to Everyday Cravings',
                'subtitle' => 'Sweets, snacks and more — all made fresh, all made right.',
                'button_text' => 'Explore Our Sweets',
                'button_url' => '#bestsellers',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'image_path' => 'images/hero/hero-4.jpg',
                'eyebrow' => 'Crafted With Care',
                'title' => 'Every Sweet, Made By Hand',
                'subtitle' => 'Our halwais start before sunrise so your mithai is always fresh.',
                'button_text' => 'See Our Kitchen',
                'button_url' => '#range',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'image_path' => 'images/hero/hero-5.jpg',
                'eyebrow' => 'Loved By Thuthibari',
                'title' => "The Sweet Box Everyone's Taking Home",
                'subtitle' => 'Perfect for gifting, celebrating, or simply treating yourself.',
                'button_text' => 'Order Now — Cash on Delivery',
                'button_url' => '#bestsellers',
                'sort_order' => 4,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('hero_banners')
            ->whereIn('image_path', [
                'images/hero/hero-1.jpg',
                'images/hero/hero-2.jpg',
                'images/hero/hero-3.jpg',
                'images/hero/hero-4.jpg',
                'images/hero/hero-5.jpg',
            ])
            ->delete();
    }
};
