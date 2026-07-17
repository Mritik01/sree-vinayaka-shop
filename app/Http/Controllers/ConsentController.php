<?php

namespace App\Http\Controllers;

use App\Models\LegalDocumentVersion;
use App\Models\UserConsent;
use Illuminate\Http\Request;

class ConsentController extends Controller
{
    // hit by the site-wide re-consent gate (partials/reconsent-gate.blade.php) when a logged-in
    // customer's last accepted Terms/Privacy version is older than what's currently published
    public function accept(Request $request)
    {
        $request->validate(['agree_terms' => 'required|accepted']);

        $user = $request->user();

        UserConsent::record(
            $user->id,
            'reconsent',
            LegalDocumentVersion::current('terms')?->version,
            LegalDocumentVersion::current('privacy')?->version,
            $request->ip(),
            (string) $request->userAgent()
        );

        return response()->json(['ok' => true]);
    }
}
