<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Frontend\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::where('is_active', true)
            ->where('deleted', false)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['notifications' => $notifications]);
        }

        return view('frontend.notifications.index', compact('notifications'));
    }

    public function unreadCount(Request $request)
    {
        $count = Notification::where('is_active', true)
            ->where('deleted', false)
            ->where('is_read', false)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->count();

        return response()->json(['count' => $count]);
    }

    public function markAsRead(Notification $notification)
    {
        $notification->update(['is_read' => true]);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    public function markAllRead()
    {
        Notification::where('is_read', false)->update(['is_read' => true]);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }
}
