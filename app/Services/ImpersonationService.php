<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\ImpersonationSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationService
{
    public const INACTIVITY_TIMEOUT_MINUTES = 30;

    private const SESSION_KEY = 'impersonation';

    public static function start(Admin $admin, User $user, Request $request): void
    {
        // switching targets (customer A -> customer B) without ever clicking "Return to Admin"
        // in between would otherwise leave A's log row with ended_at = null forever — looking
        // like a still-active session in the audit log even though it was actually superseded
        if ($request->session()->has(self::SESSION_KEY)) {
            static::stop($request, 'switched');
        }

        $log = ImpersonationSession::create([
            'admin_id' => $admin->id,
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        // session-only login (no "remember" cookie) — impersonation must never outlive this
        // browser session or survive the admin closing the tab
        Auth::guard('web')->login($user);

        $request->session()->put(self::SESSION_KEY, [
            'log_id' => $log->id,
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'last_activity' => now()->timestamp,
        ]);
    }

    public static function stop(Request $request, string $reason = 'manual'): void
    {
        $marker = $request->session()->get(self::SESSION_KEY);

        // web guard only — never session()->invalidate(), which would also destroy the
        // admin guard's own login living in this same session
        Auth::guard('web')->logout();
        $request->session()->forget(self::SESSION_KEY);

        if ($marker) {
            ImpersonationSession::where('id', $marker['log_id'])->update([
                'ended_at' => now(),
                'end_reason' => $reason,
            ]);
        }
    }

    public static function active(): ?array
    {
        return request()?->session()->get(self::SESSION_KEY);
    }

    public static function touch(Request $request): void
    {
        $marker = $request->session()->get(self::SESSION_KEY);

        if (!$marker) {
            return;
        }

        $marker['last_activity'] = now()->timestamp;
        $request->session()->put(self::SESSION_KEY, $marker);
    }

    public static function isExpired(array $marker): bool
    {
        return now()->timestamp - $marker['last_activity'] > self::INACTIVITY_TIMEOUT_MINUTES * 60;
    }
}
