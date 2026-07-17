<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use Illuminate\Http\Request;

class SupportChatController extends Controller
{
    /**
     * Polled by the customer's chat window (~every 3s while open, slower while closed).
     * `after` is an id-cursor so each tick only transfers messages the client hasn't seen.
     * `read=1` means the window is actually open on screen — only then do the shop's
     * replies get marked read (a background unread-badge poll must NOT clear the badge).
     */
    public function fetch(Request $request, int $orderId)
    {
        $order = $request->user()->orders()->findOrFail($orderId);

        if ($request->boolean('read')) {
            $order->supportMessages()->where('sender', 'admin')->whereNull('read_at')->update(['read_at' => now()]);
        }

        $after = (int) $request->query('after', 0);
        $messages = $order->supportMessages()->where('id', '>', $after)->orderBy('id')->get();

        return response()->json([
            'ok' => true,
            'messages' => $messages->map(fn ($m) => $m->forJs())->values(),
            'unread' => $order->supportMessages()->where('sender', 'admin')->whereNull('read_at')->count(),
        ]);
    }

    public function send(Request $request, int $orderId)
    {
        $order = $request->user()->orders()->findOrFail($orderId);

        $data = $request->validate([
            'message' => 'nullable|string|max:1000',
            // excludes svg/bmp on top of Laravel's base "image" check — an uploaded SVG can carry
            // an embedded <script> and would execute if the stored file is ever opened directly
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:5120',
        ]);

        if (empty(trim($data['message'] ?? '')) && !$request->hasFile('image')) {
            return response()->json(['ok' => false, 'message' => 'Type a message or attach a photo.'], 422);
        }

        $message = $order->supportMessages()->create([
            'sender' => 'customer',
            'message' => trim($data['message'] ?? ''),
            'image_path' => $request->hasFile('image') ? SupportMessage::storeImage($request->file('image')) : null,
        ]);

        return response()->json(['ok' => true, 'message' => $message->forJs()]);
    }
}
