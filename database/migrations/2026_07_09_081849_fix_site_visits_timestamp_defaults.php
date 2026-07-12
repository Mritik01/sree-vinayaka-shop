<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // MySQL/MariaDB's TIMESTAMP type converts values through the DB server's own SYSTEM
    // timezone on every read/write, and its DEFAULT/ON UPDATE CURRENT_TIMESTAMP evaluates in
    // that same server timezone rather than the app's UTC. When Eloquent's dirty-checking
    // occasionally skipped re-including last_seen in an UPDATE, the DB silently filled it with
    // its own SYSTEM-tz clock instead of ActivityLogger's explicit value — an intermittent
    // +5:30 (IST) offset. Switching to DATETIME sidesteps all of that: it's stored and read
    // back verbatim, with no server-side timezone conversion or auto-default behavior at all.
    public function up(): void
    {
        DB::statement('ALTER TABLE site_visits MODIFY first_seen DATETIME NOT NULL, MODIFY last_seen DATETIME NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE site_visits MODIFY first_seen TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, MODIFY last_seen TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }
};
