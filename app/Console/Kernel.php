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

        // carts:remind-abandoned is deliberately NOT scheduled here — sent manually (on-demand
        // trigger TBD) rather than automatically. See AbandonedCartReminderService for the
        // time-window logic (no "already reminded" tracking column by design) that still applies
        // whenever/however it's run.

        // analytics data (page views, click events, session/device/IP/location data) is only kept
        // for 3 days, for registered customers AND guests — unlike carts:remind-abandoned above
        // (deliberately left manual), this one IS scheduled: un-pruned rows are exactly the
        // unbounded PII/storage growth the 3-day retention policy exists to prevent
        $schedule->command('analytics:prune')->daily();

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
