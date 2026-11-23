<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $notifications = DB::table('notifications')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $notifications->map(fn($n) => [
                'id' => (string) $n->id,
                'message' => $n->message,
                'type' => $n->type,
                'is_read' => (bool) $n->is_read,
                'time' => \Carbon\Carbon::parse($n->created_at)->diffForHumans(),
            ]),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
            ]
        ]);
    }

    public function unread(Request $request)
    {
        $user = $request->user();
        $notifications = DB::table('notifications')
            ->where('user_id', $user->id)
            ->where('is_read', 0)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'status' => true,
            'data' => $notifications->map(fn($n) => [
                'id' => (string) $n->id,
                'message' => $n->message,
                'time' => \Carbon\Carbon::parse($n->created_at)->diffForHumans(),
            ])
        ]);
    }

    public function getUnreadCounts(Request $request)
    {
        $count = DB::table('notifications')
            ->where('user_id', $request->user()->id)
            ->where('is_read', 0)
            ->count();

        return response()->json(['status' => true, 'count' => $count]);
    }

    public function markAsRead(Request $request, $id)
    {
        DB::table('notifications')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->update(['is_read' => 1]);

        return response()->json(['status' => true, 'message' => 'Marked as read']);
    }

    public function markAllAsRead(Request $request)
    {
        DB::table('notifications')
            ->where('user_id', $request->user()->id)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return response()->json(['status' => true, 'message' => 'All marked as read']);
    }

    public function destroy(Request $request, $id)
    {
        DB::table('notifications')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['status' => true, 'message' => 'Deleted']);
    }
}