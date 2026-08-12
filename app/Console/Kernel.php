<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // safety net: orders not delivered within 90 min of being placed get auto-cancelled —
        // requires the server's cron to run `php artisan schedule:run` every minute in production
        $schedule->command('orders:cancel-stale')->everyFiveMinutes();

        // nudges users whose cart has sat untouched ~24h — see AbandonedCartReminderService for
        // the time-window approach (no "already reminded" tracking column by design). Same cron
        // requirement as orders:cancel-stale above.
        $schedule->command('carts:remind-abandoned')->hourly();

        // proof-of-delivery photos are only kept for 3 days
        $schedule->command('delivery-photos:prune')->daily();

        // support-chat photo attachments are only kept for 2 days
        $schedule->command('support-chat-images:prune')->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
