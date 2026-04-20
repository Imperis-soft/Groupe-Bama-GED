<?php

namespace App\Http\Controllers;

use App\Models\GedNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = GedNotification::where('user_id', auth()->id())->latest();

        // Filtre par type
        if ($type = $request->input('type')) {
            $query->where('type', 'LIKE', "%{$type}%");
        }

        // Filtre lu/non-lu
        if ($request->input('unread') === '1') {
            $query->where('is_read', false);
        }

        $notifications = $query->paginate(20)->withQueryString();
        $unreadCount   = GedNotification::where('user_id', auth()->id())->where('is_read', false)->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function markRead(GedNotification $notification)
    {
        if ($notification->user_id !== auth()->id()) abort(403);
        $notification->markAsRead();
        if (request()->expectsJson()) return response()->json(['ok' => true]);
        return back();
    }

    public function markAllRead()
    {
        GedNotification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
        return back()->with('success', 'Toutes les notifications marquées comme lues.');
    }

    public function destroyRead()
    {
        GedNotification::where('user_id', auth()->id())
            ->where('is_read', true)
            ->delete();
        return back()->with('success', 'Notifications lues supprimées.');
    }

    public function unreadCount()
    {
        return response()->json([
            'count' => GedNotification::where('user_id', auth()->id())->where('is_read', false)->count()
        ]);
    }

    public function destroy(GedNotification $notification)
    {
        if ($notification->user_id !== auth()->id()) abort(403);
        $notification->delete();
        if (request()->expectsJson()) return response()->json(['ok' => true]);
        return back();
    }
}
