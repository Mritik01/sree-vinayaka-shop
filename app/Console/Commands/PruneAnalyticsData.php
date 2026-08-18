<?php

namespace App\Console\Commands;

use App\Models\SiteVisit;
use App\Models\UserActivity;
use Illuminate\Console\Command;

class PruneAnalyticsData extends Command
{
    protected $signature = 'analytics:prune';

    protected $description = 'Deletes user_activities and site_visits data older than 3 days (registered users and guests alike)';

    public function handle(): void
    {
        $activityCount = UserActivity::where('created_at', '<=', now()->subDays(3))->delete();

        // keyed off last_seen (not first_seen) — a session that started >3 days ago but is still
        // active shouldn't be deleted just because it began a while back
        $visitCount = SiteVisit::where('last_seen', '<=', now()->subDays(3))->delete();

        $this->info("Pruned {$activityCount} activity row(s) and {$visitCount} visit(s) older than 3 days.");
    }
}
