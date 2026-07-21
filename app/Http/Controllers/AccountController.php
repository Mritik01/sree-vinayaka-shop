<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function updateName(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $user = $request->user();
        $user->forceFill(['name' => trim($data['name'])])->save();

        return response()->json(['ok' => true, 'name' => $user->name]);
    }

    // the full-screen "Account Temporarily Restricted" page — see EnsureNotBlocked (which
    // redirects here from every gated customer route) and PhoneAuthController::verifyOtp() (which
    // sends a blocked user straight here right after login instead of celebrating). Deliberately
    // NOT gated by EnsureNotBlocked itself (that would be an infinite redirect loop); anyone who
    // isn't actually a blocked, logged-in customer is bounced home instead of seeing this page.
    public function blocked()
    {
        if (!Auth::check() || !Auth::user()->isBlocked()) {
            return redirect('/');
        }

        return view('account-blocked', ['user' => Auth::user()]);
    }
}
