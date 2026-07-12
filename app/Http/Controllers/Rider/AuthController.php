<?php

namespace App\Http\Controllers\Rider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('rider')->check()) {
            return redirect()->route('rider.orders.index');
        }

        return view('rider.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if (!Auth::guard('rider')->attempt($data, remember: true)) {
            return back()->withErrors(['username' => 'Incorrect username or password.'])->onlyInput('username');
        }

        $request->session()->regenerate();

        return redirect()->route('rider.orders.index');
    }

    public function logout(Request $request)
    {
        Auth::guard('rider')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('rider.login');
    }
}
