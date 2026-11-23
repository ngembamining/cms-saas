<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\User;
use App\Models\Branch;

class NotificationController extends Controller
{
    /* ============================================================
     |  INDEX
     * ============================================================ */

    public function index()
    {
        $user = Auth::user();

        $unreadCount         = $this->getUnreadCount($user->id);
        $notifications       = $this->getNotifications($user->id, 20);
        $unreadNotifications = $this->getUnreadNotifications($user->id, 10);

        return view('communication.notifications.index', compact(
            'notifications',
            'unreadCount',
            'unreadNotifications'
        ));
    }

    /* ============================================================
     |  BROADCAST FORM (was missing — caused the BadMethodCallException)
     * ============================================================ */

    public function showBroadcastForm()
    {
        $user = Auth::user();

        /* Only directors / managers / accountants may broadcast */
        if (!in_array($user->role, ['director', 'branch_manager', 'accountant'], true)) {
            abort(403, 'You do not have permission to broadcast notifications.');
        }

        $branches = Branch::query()
            ->when(
                $user->role === 'branch_manager',
                fn ($q) => $q->where('id', $user->assigned_branch)
            )
            ->orderBy('name')
            ->get();

        return view('communication.notifications.broadcast', compact('branches'));
    }

    /* ============================================================
     |  AJAX — unread list + count
     * ============================================================ */

    public function unread()
    {
        $user          = Auth::user();
        $notifications = $this->getUnreadNotifications($user->id, 10);
        $count         = $this->getUnreadCount($user->id);

        return response()->json([
            'success' => true,
            'data'    => $notifications,
            'count'   => $count,
            'html'    => view('communication.notifications.partials.list', compact('notifications'))->render(),
        ]);
    }

    /* ============================================================
     |  MARK ONE AS READ
     * ============================================================ */

    public function markAsRead(Request $request, $id)
    {
        $user = Auth::user();

        try {
            $affected = DB::table('notifications')
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->where('is_read', 0)
                ->update(['is_read' => 1]);

            if ($affected === 0) {
                // Either it doesn't belong to this user, doesn't exist, or was already read
                $exists = DB::table('notifications')
                    ->where('id', $id)
                    ->where('user_id', $user->id)
                    ->exists();

                return response()->json([
                    'success' => $exists,
                    'message' => $exists ? 'Already read' : 'Notification not found',
                ], $exists ? 200 : 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read: ' . $e->getMessage(),
            ], 500);
        }
    }

    /* ============================================================
     |  MARK ALL AS READ
     * ============================================================ */

    public function markAllAsRead()
    {
        $user = Auth::user();

        try {
            DB::table('notifications')
                ->where('user_id', $user->id)
                ->where('is_read', 0)
                ->update(['is_read' => 1]);

            return redirect()->route('communication.notifications.index')
                ->with('success', '✅ All notifications marked as read');
        } catch (\Throwable $e) {
            return back()->with('error', '❌ Failed to mark all notifications as read: ' . $e->getMessage());
        }
    }

    /* ============================================================
     |  DELETE
     * ============================================================ */

    public function destroy($id)
    {
        $user = Auth::user();

        try {
            $affected = DB::table('notifications')
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->delete();

            if ($affected === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification: ' . $e->getMessage(),
            ], 500);
        }
    }

    /* ============================================================
     |  BROADCAST
     * ============================================================ */

    public function broadcast(Request $request)
    {
        $user = Auth::user();

        /* Authorization: only director / branch_manager / accountant */
        if (!in_array($user->role, ['director', 'branch_manager', 'accountant'], true)) {
            abort(403, 'You do not have permission to broadcast notifications.');
        }

        $request->validate([
            'message'        => 'required|string|max:500',
            'recipient_type' => 'required|in:all,managers,staff,branch',
            'branch_id'      => 'nullable|exists:branches,id',
        ]);

        /* A branch_manager can only broadcast to their own branch */
        if ($user->role === 'branch_manager') {
            if ($request->recipient_type !== 'branch') {
                return back()->withInput()
                    ->with('error', '❌ Branch managers can only broadcast to their own branch.');
            }

            if ((int) $request->branch_id !== (int) $user->assigned_branch) {
                return back()->withInput()
                    ->with('error', '❌ You can only broadcast to your assigned branch.');
            }
        }

        try {
            $recipients = $this->getRecipients($request->recipient_type, $request->branch_id);

            if ($recipients->isEmpty()) {
                return back()->withInput()
                    ->with('warning', '⚠️ No recipients matched your selection.');
            }

            /* Batch insert — one SQL query */
            $now     = now();
            $payload = [];
            foreach ($recipients as $recipient) {
                $payload[] = [
                    'user_id'    => $recipient->id,
                    'message'    => $request->message,
                    'type'       => 'broadcast',
                    'related_id' => null,
                    'is_read'    => 0,
                    'created_at' => $now,
                ];
            }

            DB::table('notifications')->insert($payload);

            return redirect()->route('communication.notifications.index')
                ->with('success', '✅ Notification sent to ' . count($payload) . ' user(s)');

        } catch (\Throwable $e) {
            return back()->withInput()
                ->with('error', '❌ Failed to broadcast notification: ' . $e->getMessage());
        }
    }

    /* ============================================================
     |  PRIVATE HELPERS
     * ============================================================ */

    private function getNotifications($userId, $limit = 20)
    {
        try {
            return DB::table('notifications')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->paginate($limit);
        } catch (\Throwable $e) {
            // Return an empty paginator so the blade's ->links() never crashes
            return new LengthAwarePaginator([], 0, $limit);
        }
    }

    private function getUnreadNotifications($userId, $limit = 10)
    {
        try {
            return DB::table('notifications')
                ->where('user_id', $userId)
                ->where('is_read', 0)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    private function getUnreadCount($userId)
    {
        try {
            return DB::table('notifications')
                ->where('user_id', $userId)
                ->where('is_read', 0)
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function getRecipients($type, $branchId = null)
    {
        $query = User::where('id', '!=', Auth::id());

        switch ($type) {
            case 'managers':
                $query->where('role', 'branch_manager');
                break;

            case 'staff':
                $query->whereIn('role', ['staff', 'secretary', 'accountant']);
                break;

            case 'branch':
                if ($branchId) {
                    $query->where('assigned_branch', $branchId);
                }
                break;

            case 'all':
            default:
                // everyone except self
                break;
        }

        return $query->get();
    }

    /* ============================================================
     |  STATIC CREATORS (used by other controllers)
     * ============================================================ */

    public static function create($userId, $message, $type = 'general', $relatedId = null)
    {
        try {
            return DB::table('notifications')->insert([
                'user_id'    => $userId,
                'message'    => $message,
                'type'       => $type,
                'related_id' => $relatedId,
                'is_read'    => 0,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Failed to create notification: ' . $e->getMessage());
            return false;
        }
    }

    public static function createBulk($userIds, $message, $type = 'general', $relatedId = null)
    {
        try {
            $now     = now();
            $payload = [];

            foreach ($userIds as $userId) {
                $payload[] = [
                    'user_id'    => $userId,
                    'message'    => $message,
                    'type'       => $type,
                    'related_id' => $relatedId,
                    'is_read'    => 0,
                    'created_at' => $now,
                ];
            }

            if (empty($payload)) return false;

            return DB::table('notifications')->insert($payload);
        } catch (\Throwable $e) {
            \Log::error('Failed to create bulk notifications: ' . $e->getMessage());
            return false;
        }
    }

    /* ============================================================
     |  BADGE COUNT (header poller)
     * ============================================================ */

    public function getUnreadCounts()
    {
        $user  = Auth::user();
        $count = $this->getUnreadCount($user->id);

        return response()->json([
            'success' => true,
            'count'   => $count,
        ]);
    }
}