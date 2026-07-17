<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\PaginatesAdminLists;
use App\Http\Controllers\Controller;
use App\Models\ImpersonationSession;
use Illuminate\Http\Request;

class ImpersonationLogController extends Controller
{
    use PaginatesAdminLists;

    // read-only audit trail of every "Login as Customer" session — who, whom, when, from where
    public function index(Request $request)
    {
        $sessions = ImpersonationSession::with('admin', 'user')
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('admin.impersonation-log.index', ['sessions' => $sessions]);
    }
}
