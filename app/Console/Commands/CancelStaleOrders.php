<?php

namespace App\Console\Commands;

use App\Services\OrderAutoCancelService;
use Illuminate\Console\Command;

class CancelStaleOrders extends Command
{
    protected $signature = 'orders:cancel-stale';

    protected $description = 'Auto-cancels orders that have not been delivered within 90 minutes of being placed';

    public function handle(): void
    {
        $cancelled = OrderAutoCancelService::cancelStaleOrders();

        if ($cancelled->isEmpty()) {
            $this->info('No stale orders to cancel.');

            return;
        }

        $this->warn("Auto-cancelled {$cancelled->count()} stale order(s): #".$cancelled->pluck('id')->join(', #'));
    }
}
