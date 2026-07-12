<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class PruneDeliveryPhotos extends Command
{
    protected $signature = 'delivery-photos:prune';

    protected $description = 'Deletes proof-of-delivery photos (and clears the order reference) 3 days after upload';

    public function handle(): void
    {
        $orders = Order::whereNotNull('delivery_photo_path')
            ->where('delivery_photo_uploaded_at', '<=', now()->subDays(3))
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No delivery photos old enough to prune.');

            return;
        }

        foreach ($orders as $order) {
            @unlink(public_path($order->delivery_photo_path));
            $order->forceFill([
                'delivery_photo_path' => null,
                'delivery_photo_uploaded_at' => null,
            ])->save();
        }

        $this->info("Pruned {$orders->count()} delivery photo(s) older than 3 days.");
    }
}
