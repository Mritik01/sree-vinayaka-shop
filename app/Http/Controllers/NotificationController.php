<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'notifications' => $user->notifications()->latest()->take(20)->get()->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->data['title'] ?? 'Notification',
                'message' => $n->data['message'] ?? '',
                'read' => $n->read_at !== null,
                'time' => $n->created_at->diffForHumans(short: true),
            ])->values(),
        ]);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, string $notification)
    {
        $request->user()->notifications()->where('id', $notification)->delete();

        return response()->json(['ok' => true]);
    }

    public function clearAll(Request $request)
    {
        $request->user()->notifications()->delete();

        return response()->json(['ok' => true]);
    }
}
