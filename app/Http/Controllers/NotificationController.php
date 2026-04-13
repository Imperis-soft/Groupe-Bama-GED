<?php

namespace App\Http\Controllers;

use App\Models\GedNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = GedNotification::where('user_id', auth()->id())
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function markRead(GedNotification $notification)
    {
        if ($notification->user_id !== auth()->id()) abort(403);
        $notification->markAsRead();
        return back();
    }

    public function markAllRead()
    {
        GedNotification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return back()->with('success', 'Toutes les notifications marquées comme lues.');
    }

    public function unreadCount()
    {
        $count = GedNotification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    public function destroy(GedNotification $notification)
    {
        if ($notification->user_id !== auth()->id()) abort(403);
        $notification->delete();
        return back();
    }
}
