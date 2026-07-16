<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Mirrors the customer-facing NotificationController (App\Http\Controllers\NotificationController)
// exactly, just scoped to the admin guard — same JSON shape, same UI pattern (bell + dropdown).
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        return response()->json([
            'unread' => $admin->unreadNotifications()->count(),
            'notifications' => $admin->notifications()->latest()->take(20)->get()->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->data['title'] ?? 'Notification',
                'message' => $n->data['message'] ?? '',
                'url' => $n->data['url'] ?? null,
                'read' => $n->read_at !== null,
                'time' => $n->created_at->diffForHumans(short: true),
            ])->values(),
        ]);
    }

    public function markAllRead(Request $request)
    {
        Auth::guard('admin')->user()->unreadNotifications->markAsRead();

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, string $notification)
    {
        Auth::guard('admin')->user()->notifications()->where('id', $notification)->delete();

        return response()->json(['ok' => true]);
    }

    public function clearAll(Request $request)
    {
        Auth::guard('admin')->user()->notifications()->delete();

        return response()->json(['ok' => true]);
    }
}
