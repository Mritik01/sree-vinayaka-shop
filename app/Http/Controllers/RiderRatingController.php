<?php

namespace App\Http\Controllers;

use App\Models\RiderRating;
use Illuminate\Http\Request;

class RiderRatingController extends Controller
{
    // one rating per order — updateOrCreate() upserts (a retry/resubmit just updates in place,
    // same convention as ReviewController::store()), while the rider_ratings.order_id unique
    // constraint is the structural guarantee against ever creating a duplicate row
    public function store(Request $request, int $orderId)
    {
        $order = $request->user()->orders()->findOrFail($orderId);

        if ($order->status !== 'delivered' || !$order->rider_id) {
            return response()->json(['ok' => false, 'message' => "This order can't be rated yet."], 422);
        }

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        RiderRating::updateOrCreate(
            ['order_id' => $order->id],
            ['rider_id' => $order->rider_id, 'user_id' => $request->user()->id, ...$data]
        );

        return response()->json(['ok' => true]);
    }
}
