<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Reachable only by an already-logged-in Super Admin — see routes/web.php (nested inside
// both `admin.auth` and `admin.super`) and app/Http/Middleware/EnsureSuperAdmin.php.
// This is the ongoing, friendly way to create/manage admin accounts after the first one
// exists; see App\Console\Commands\CreateAdminCommand for how that first one gets made.
class AdminAccountController extends Controller
{
    public function index()
    {
        return view('admin.admins.index', [
            'admins' => Admin::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.admins.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['is_super_admin'] = $request->boolean('is_super_admin');

        Admin::create($data);

        return redirect()->route('admin.admins.index')->with('status', 'Admin account created.');
    }

    public function edit(Admin $admin)
    {
        return view('admin.admins.edit', ['admin' => $admin]);
    }

    public function update(Request $request, Admin $admin)
    {
        $data = $this->validateData($request, $admin->id);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        // a super admin can't strip their own or the last remaining super admin's status —
        // either way it risks nobody being left who can reach this screen at all
        $keepsSuperAdmin = $request->boolean('is_super_admin');
        if ($admin->is_super_admin && !$keepsSuperAdmin && $this->isLastSuperAdmin($admin)) {
            return back()->withErrors(['is_super_admin' => 'This is the last Super Admin — promote another admin first.'])->withInput();
        }

        $data['is_super_admin'] = $keepsSuperAdmin;

        $admin->update($data);

        return redirect()->route('admin.admins.index')->with('status', 'Admin account updated.');
    }

    public function destroy(Admin $admin)
    {
        if ($admin->id === Auth::guard('admin')->id()) {
            return back()->withErrors(['admin' => "You can't delete your own account while logged in as it."]);
        }

        if ($admin->is_super_admin && $this->isLastSuperAdmin($admin)) {
            return back()->withErrors(['admin' => 'This is the last Super Admin — promote another admin before deleting this one.']);
        }

        $admin->delete();

        return redirect()->route('admin.admins.index')->with('status', 'Admin account deleted.');
    }

    private function isLastSuperAdmin(Admin $admin): bool
    {
        return Admin::where('is_super_admin', true)->where('id', '!=', $admin->id)->doesntExist();
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:admins,username'.($ignoreId ? ",{$ignoreId}" : ''),
            'password' => ($ignoreId ? 'nullable' : 'required').'|string|min:8',
        ]);
    }
}
