<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AppNotification;

class NotificationController extends Controller
{
    public function markAsRead($id, Request $request)
    {
        $user_id = $request->user()->id;
        $notification = AppNotification::where('id', $id)
            ->where('user_id', $user_id)
            ->firstOrFail();

        $notification->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Notification marked as read successfully.'
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $user_id = $request->user()->id;
        AppNotification::where('user_id', $user_id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'All notifications marked as read successfully.'
        ]);
    }
}
