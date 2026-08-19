<?php

namespace App\Console\Commands;

use App\Models\ShopSetting;
use Illuminate\Console\Command;

class ResumeAcceptingOrders extends Command
{
    protected $signature = 'orders:resume-accepting';

    protected $description = 'Turns "Accepting Orders" back on once the admin-scheduled reopen time has passed';

    public function handle(): void
    {
        if (ShopSetting::resumeIfDue()) {
            $this->info('Accepting Orders turned back on — scheduled reopen time has passed.');

            return;
        }

        $this->info('Nothing to resume.');
    }
}
