<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = strtolower(trim($data['email']));

        // already on the list — still a warm "you're in" rather than an error, since from the
        // customer's side re-submitting isn't a mistake worth calling out
        if (NewsletterSubscriber::where('email', $email)->exists()) {
            return response()->json(['ok' => true, 'already' => true]);
        }

        NewsletterSubscriber::create(['email' => $email]);

        return response()->json(['ok' => true, 'already' => false]);
    }
}
