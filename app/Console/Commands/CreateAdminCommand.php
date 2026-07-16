<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

// Deliberately the ONLY way to create the very first admin account — there is no public
// registration route for admins anywhere in the app. Running this requires access to the
// server/hosting console itself, which is a much stronger boundary than any URL could be.
// After the first admin exists, day-to-day admin creation happens through the "Manage
// Admins" screen in the admin panel (Super Admins only) — this command stays available
// afterward only as a break-glass recovery tool (e.g. locked out, need another Super Admin).
class CreateAdminCommand extends Command
{
    // options let this run unattended (e.g. a hosting provider's automated setup script);
    // leave them off and it falls back to friendly interactive prompts for a real terminal
    protected $signature = 'admin:create
        {--name= : Full name}
        {--username= : Username}
        {--password= : Password (min 8 characters)}
        {--super : Make this admin a Super Admin}';

    protected $description = 'Create an admin account — the only way to create the first one, and a recovery tool afterward';

    public function handle(): int
    {
        $isFirstAdmin = Admin::count() === 0;

        $this->info($isFirstAdmin
            ? 'No admin accounts exist yet — this will create the first one (automatically a Super Admin).'
            : 'Creating a new admin account.');

        $name = $this->option('name') ?: $this->ask('Full name');
        $username = $this->option('username') ?: $this->ask('Username');

        $password = $this->option('password');
        if ($password) {
            // supplied non-interactively (e.g. scripted) — trust it as-is, no re-typing to confirm
            $passwordConfirmation = $password;
        } else {
            $password = $this->secret('Password (min 8 characters)');
            $passwordConfirmation = $this->secret('Confirm password');
        }

        $validator = Validator::make(
            ['name' => $name, 'username' => $username, 'password' => $password],
            [
                'name' => 'required|string|max:100',
                'username' => 'required|string|max:50|unique:admins,username',
                'password' => 'required|string|min:8',
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if ($password !== $passwordConfirmation) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }

        $isSuperAdmin = $isFirstAdmin || $this->option('super') || $this->confirm(
            'Make this admin a Super Admin? (Super Admins can create and manage other admin accounts)',
            false
        );

        $admin = Admin::create([
            'name' => $name,
            'username' => $username,
            'password' => $password,
            'is_super_admin' => $isSuperAdmin,
        ]);

        $this->info("Admin account '{$admin->username}' created".($isSuperAdmin ? ' as a Super Admin.' : '.'));

        return self::SUCCESS;
    }
}
