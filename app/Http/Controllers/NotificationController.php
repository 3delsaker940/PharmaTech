<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AppNotification;

class NotificationController extends Controller
{
    /**
     * Get authenticated user's notifications.
     */
    public function index(Request $request)
    {
        $notifications = AppNotification::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json($notifications);
    }

    /**
     * Mark one notification as read.
     */
    public function markAsRead(AppNotification $notification, Request $request)
    {
        $this->authorize('update', $notification);

        $notification->update([
            'read_at' => now(),
        ]);

        return response()->json([
            'message' => 'Notification marked as read successfully.',
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $user_id = $request->user()->id;

        AppNotification::where('user_id', $user_id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);

        return response()->json([
            'message' => 'All notifications marked as read successfully.',
        ]);
    }
}
