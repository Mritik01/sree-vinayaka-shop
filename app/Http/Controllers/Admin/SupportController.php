<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SupportMessage;
use App\Notifications\AdminMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SupportController extends Controller
{
    // inbox — one row per order that has a chat, newest activity first. The page also polls
    // this same route with Accept: application/json to keep rows/badges live without reloads.
    public function index(Request $request)
    {
        $conversations = Order::whereHas('supportMessages')
            ->with('latestSupportMessage')
            ->withCount([
                'supportMessages as unread_count' => fn ($q) => $q->where('sender', 'customer')->whereNull('read_at'),
            ])
            ->get()
            ->sortByDesc(fn ($order) => $order->latestSupportMessage->id)
            ->values()
            ->map(fn ($order) => [
                'order_id' => $order->id,
                'order_number' => $order->orderNumber(),
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'status' => $order->status,
                'snippet' => $order->latestSupportMessage->message !== ''
                    ? Str::limit($order->latestSupportMessage->message, 90)
                    : '📷 Photo',
                'last_sender' => $order->latestSupportMessage->sender,
                'last_at' => $order->latestSupportMessage->created_at->diffForHumans(short: true),
                'unread' => $order->unread_count,
            ]);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'conversations' => $conversations]);
        }

        return view('admin.support.index', ['conversations' => $conversations]);
    }

    public function show(Order $order)
    {
        // opening the thread means the admin has seen everything the customer wrote so far
        $order->supportMessages()->where('sender', 'customer')->whereNull('read_at')->update(['read_at' => now()]);

        return view('admin.support.show', [
            'order' => $order,
            'messagesForJs' => $order->supportMessages()->orderBy('id')->get()->map(fn ($m) => $m->forJs())->values(),
        ]);
    }

    // polled by the open thread (~every 3s) — id-cursor keeps each tick tiny, and having the
    // thread open continuously marks fresh customer messages read (clears the sidebar badge)
    public function messages(Request $request, Order $order)
    {
        $order->supportMessages()->where('sender', 'customer')->whereNull('read_at')->update(['read_at' => now()]);

        $after = (int) $request->query('after', 0);
        $messages = $order->supportMessages()->where('id', '>', $after)->orderBy('id')->get();

        return response()->json([
            'ok' => true,
            'messages' => $messages->map(fn ($m) => $m->forJs())->values(),
        ]);
    }

    public function send(Request $request, Order $order)
    {
        $data = $request->validate([
            'message' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:5120',
        ]);

        if (empty(trim($data['message'] ?? '')) && !$request->hasFile('image')) {
            return response()->json(['ok' => false, 'message' => 'Type a message or attach a photo.'], 422);
        }

        $message = $order->supportMessages()->create([
            'sender' => 'admin',
            'message' => trim($data['message'] ?? ''),
            'image_path' => $request->hasFile('image') ? SupportMessage::storeImage($request->file('image')) : null,
        ]);

        // the customer's chat window (if open) gets this instantly via its own 3s poll — this
        // notification is for the far more common case of them being elsewhere on the site
        // (or the app closed entirely), so the bell still surfaces it next time they're around
        if ($order->user) {
            $order->user->notify(new AdminMessage(
                title: "💬 {$order->orderNumber()}",
                message: $message->message !== '' ? Str::limit($message->message, 120) : '📷 Sent you a photo',
                url: route('orders.show', $order->id).'?chat=1',
            ));
        }

        return response()->json(['ok' => true, 'message' => $message->forJs()]);
    }

    // wipes an entire conversation — for clearing test/spam threads out of the inbox. The
    // order itself is untouched, only its chat history; any uploaded photos are removed from
    // disk too so they don't linger as orphaned files under public/images/support-chat.
    public function destroy(Order $order)
    {
        $order->supportMessages()->whereNotNull('image_path')->pluck('image_path')->each(function ($path) {
            if (file_exists(public_path($path))) {
                @unlink(public_path($path));
            }
        });
        $order->supportMessages()->delete();

        if (request()->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('admin.support.index');
    }
}
