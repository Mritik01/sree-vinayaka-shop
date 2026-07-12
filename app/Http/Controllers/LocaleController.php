<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocaleController extends Controller
{
    // customer side — remembered on the account so it follows them across devices, with a
    // session fallback so it still sticks for a browsing guest who hasn't logged in yet
    public function switchCustomer(Request $request, string $locale)
    {
        abort_unless(in_array($locale, SetLocale::LOCALES, true), 404);

        session(['locale' => $locale]);
        if ($user = $request->user()) {
            $user->forceFill(['locale' => $locale])->save();
        }

        return back();
    }

    public function switchAdmin(Request $request, string $locale)
    {
        abort_unless(in_array($locale, SetLocale::LOCALES, true), 404);

        session(['admin_locale' => $locale]);
        if ($admin = Auth::guard('admin')->user()) {
            $admin->forceFill(['locale' => $locale])->save();
        }

        return back();
    }

    public function switchRider(Request $request, string $locale)
    {
        abort_unless(in_array($locale, SetLocale::LOCALES, true), 404);

        session(['rider_locale' => $locale]);
        if ($rider = Auth::guard('rider')->user()) {
            $rider->forceFill(['locale' => $locale])->save();
        }

        return back();
    }
}
