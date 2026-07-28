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
        $setting = AnnouncementSetting::current();

        abort_unless($setting->hasLandingPage(), 404);

        return view('promo-landing', [
            // NOT 'announcement' — AppServiceProvider does View::share('announcement', [camelCase
            // array]) globally, for the site-wide popup partial (partials/announcement-banner.blade.php)
            // included via the layout on every page, this one included. A same-named key passed to
            // a specific view() call overrides the shared one for that whole render tree, which
            // silently broke the popup here: its JS reads buttonText/image/showClose (camelCase),
            // none of which exist on this raw model (button_text/image_path/show_close_button,
            // snake_case) — headline/description happened to survive by pure name coincidence,
            // everything else silently went undefined.
            'announcementSetting' => $setting,
            'products' => $setting->landingProducts(),
            'favoritedIds' => Auth::check() ? Auth::user()->favorites()->pluck('products.id')->all() : [],
        ]);
    }
}
