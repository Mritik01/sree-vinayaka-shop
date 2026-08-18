<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

// two lightweight endpoints hit via navigator.sendBeacon() from resources/js/app.js (never
// admin.js/rider.js — customer-facing only, see TrackPageView's EXCLUDED_PATTERNS for the same
// admin/rider exclusion on the page-view side). Both just forward into ActivityLogger::log(),
// which already no-ops during impersonation and never throws back into the request — these
// actions return 204 unconditionally since a beacon never reads the response body.
class ActivityTrackingController extends Controller
{
    // the short, deliberately-curated list of "meaningful" clicks this app tracks — see
    // ActivityLogger's class doc-comment. Anything not in this list is rejected (422) rather than
    // silently logged, so this endpoint can't become a generic "track any click" backdoor from
    // the client side.
    private const ALLOWED_CLICK_EVENTS = [
        'click:product_card',
        'click:checkout_start',
        'click:call_button',
        'click:whatsapp_button',
        'click:category_nav',
    ];

    // fired once, from a visibilitychange/pagehide listener, when the tab is backgrounded/closed
    // — records the page the visitor was on so "where did they leave from" is a real queryable
    // event instead of only inferable from SiteVisit.last_seen staleness
    public function sessionEnd(Request $request): Response
    {
        $data = $request->validate([
            'path' => ['nullable', 'string', 'max:2048'],
        ]);

        ActivityLogger::log('session_end', $data['path'] ?? null);

        return response()->noContent();
    }

    public function click(Request $request): Response
    {
        $data = $request->validate([
            'event' => ['required', 'string', 'in:'.implode(',', self::ALLOWED_CLICK_EVENTS)],
            'label' => ['nullable', 'string', 'max:255'],
        ]);

        ActivityLogger::log($data['event'], $data['label'] ?? null);

        return response()->noContent();
    }
}
