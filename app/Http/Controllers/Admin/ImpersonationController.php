<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ImpersonationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function start(Request $request, User $user)
    {
        ImpersonationService::start(Auth::guard('admin')->user(), $user, $request);

        return redirect('/account');
    }

    public function stop(Request $request)
    {
        $marker = ImpersonationService::active();

        ImpersonationService::stop($request);

        if ($marker) {
            return redirect()->route('admin.customers.show', $marker['user_id']);
        }

        return redirect()->route('admin.dashboard');
    }
}
