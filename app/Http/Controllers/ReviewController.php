<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReviewController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'photos' => 'sometimes|array|max:3',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $payload = [
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ];

        if ($request->hasFile('photos')) {
            // uploading new photos replaces the previous set (and its files on disk)
            $existing = $product->reviews()->where('user_id', $request->user()->id)->first();
            foreach ($existing->photos ?? [] as $old) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $old));
            }

            $payload['photos'] = collect($request->file('photos'))
                ->map(fn ($file) => '/storage/'.$file->store('reviews', 'public'))
                ->all();
        }

        $review = $product->reviews()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $payload
        );

        $review->load('user');
        ActivityLogger::log('review_submitted', $product->name, ['product_id' => $product->id]);

        return response()->json([
            'ok' => true,
            'review' => [
                'id' => $review->id,
                'user_id' => $review->user_id,
                'user_name' => $review->user->name,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'photos' => $review->photos ?? [],
                'created_at' => $review->created_at->diffForHumans(),
            ],
            'average' => round($product->reviews()->avg('rating'), 1),
            'count' => $product->reviews()->count(),
        ]);
    }
}
