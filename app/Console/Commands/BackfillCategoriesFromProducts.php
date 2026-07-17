<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;

// One-time operational command — not a fresh-install seeder. Creates a proper Category row
// (with its own slug, ready for an image) for every distinct value the old plain-text
// `category` column already has, and attaches every matching product to it, so the new
// admin Category Management screen and mobile panel start out already correct without
// anyone having to re-tag products by hand.
class BackfillCategoriesFromProducts extends Command
{
    protected $signature = 'categories:backfill';

    protected $description = 'Create a Category row for each distinct product.category value and attach matching products';

    public function handle(): int
    {
        $values = Product::query()->distinct()->orderBy('category')->pluck('category');

        if ($values->isEmpty()) {
            $this->info('No products to backfill from.');

            return self::SUCCESS;
        }

        foreach ($values as $index => $name) {
            $category = Category::firstOrCreate(
                ['name' => $name],
                ['sort_order' => $index]
            );

            $productIds = Product::where('category', $name)->pluck('id');
            $category->products()->syncWithoutDetaching($productIds);

            $this->info("'{$name}' — {$productIds->count()} product(s) attached.");
        }

        return self::SUCCESS;
    }
}
