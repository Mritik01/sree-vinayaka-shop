<?php

namespace App\Http\Controllers;

use App\Models\AnnouncementSetting;
use Illuminate\Support\Facades\Auth;

// The page the Announcement Banner's "Shop Now" button sends visitors to once the admin has
// configured a Landing Page Mode (see AnnouncementSetting::hasLandingPage()) — either a
// hand-picked, ordered product list, or every currently-discounted product. Decoupled from the
// banner's own is_enabled toggle: a promo page stays reachable (e.g. shared as a direct link)
// even if the popup itself is switched off, same as /terms staying up regardless of what else
// changes elsewhere on the site.
class PromoLandingController extends Controller
{
    public function show()
    {
        $announcement = AnnouncementSetting::current();

        abort_unless($announcement->hasLandingPage(), 404);

        return view('promo-landing', [
            'announcement' => $announcement,
            'products' => $announcement->landingProducts(),
            'favoritedIds' => Auth::check() ? Auth::user()->favorites()->pluck('products.id')->all() : [],
        ]);
    }
}
