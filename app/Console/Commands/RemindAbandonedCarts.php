<?php

namespace App\Console\Commands;

use App\Services\AbandonedCartReminderService;
use Illuminate\Console\Command;

class RemindAbandonedCarts extends Command
{
    protected $signature = 'carts:remind-abandoned';

    protected $description = 'Sends a WhatsApp reminder to users whose cart has sat untouched for ~24 hours';

    public function handle(): void
    {
        $reminded = AbandonedCartReminderService::sendReminders();

        if ($reminded->isEmpty()) {
            $this->info('No abandoned carts to remind right now.');

            return;
        }

        $this->info("Reminded {$reminded->count()} user(s) with an abandoned cart: #".$reminded->pluck('id')->join(', #'));
    }
}
