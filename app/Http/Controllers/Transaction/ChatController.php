<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Message;
use App\Models\Chat;
use App\Events\NewMessage;
use App\Events\UserTyping;
use App\Events\UserOnline;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /**
     * Get contacts for the current user
     */
    public function getContacts()
    {
        $user = Auth::user();
        
        // Get all users except the current user
        $contacts = User::where('id', '!=', $user->id)
            ->select('id', 'name', 'email', 'full_name', 'username', 'assigned_branch')
            ->get()
            ->map(function ($contact) use ($user) {
                // Get last message
                $lastMessage = Message::where(function ($q) use ($user, $contact) {
                    $q->where('sender_id', $user->id)->where('recipient_id', $contact->id);
                })->orWhere(function ($q) use ($user, $contact) {
                    $q->where('sender_id', $contact->id)->where('recipient_id', $user->id);
                })->orderBy('created_at', 'desc')->first();
                
                // Get unread count
                $unreadCount = Message::where('sender_id', $contact->id)
                    ->where('recipient_id', $user->id)
                    ->where('is_read', false)
                    ->count();
                
                return [
                    'id' => $contact->id,
                    'name' => $contact->full_name ?? $contact->name ?? $contact->username,
                    'email' => $contact->email,
                    'avatar' => null, // You can add avatar URL here
                    'is_online' => $this->isUserOnline($contact->id),
                    'is_typing' => false,
                    'last_message' => $lastMessage ? $lastMessage->body : null,
                    'last_message_time' => $lastMessage ? $lastMessage->created_at->diffForHumans() : null,
                    'unread_count' => $unreadCount,
                    'messages' => [],
                ];
            })
            ->sortByDesc(function ($contact) {
                return $contact['last_message_time'] ?? null;
            })
            ->values()
            ->toArray();
        
        return response()->json([
            'success' => true,
            'data' => $contacts
        ]);
    }
    
    /**
     * Get messages with a specific user
     */
    public function getMessages(Request $request)
    {
        $user = Auth::user();
        $otherUserId = $request->input('user_id');
        
        if (!$otherUserId) {
            return response()->json([
                'success' => false,
                'message' => 'User ID is required'
            ], 400);
        }
        
        $messages = Message::where(function ($q) use ($user, $otherUserId) {
            $q->where('sender_id', $user->id)->where('recipient_id', $otherUserId);
        })->orWhere(function ($q) use ($user, $otherUserId) {
            $q->where('sender_id', $otherUserId)->where('recipient_id', $user->id);
        })
        ->orderBy('created_at', 'asc')
        ->get();
        
        // Mark messages as read
        Message::where('sender_id', $otherUserId)
            ->where('recipient_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
        
        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }
    
    /**
     * Send a message
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string|max:1000'
        ]);
        
        $user = Auth::user();
        
        $message = Message::create([
            'sender_id' => $user->id,
            'recipient_id' => $request->receiver_id,
            'body' => $request->message,
            'is_read' => false,
        ]);
        
        // Broadcast the message
        broadcast(new NewMessage($message))->toOthers();
        
        return response()->json([
            'success' => true,
            'data' => $message
        ]);
    }
    
    /**
     * Mark messages as read
     */
    public function markAsRead(Request $request)
    {
        $user = Auth::user();
        $otherUserId = $request->input('user_id');
        
        if (!$otherUserId) {
            return response()->json([
                'success' => false,
                'message' => 'User ID is required'
            ], 400);
        }
        
        $count = Message::where('sender_id', $otherUserId)
            ->where('recipient_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
        
        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }
    
    /**
     * Get chat updates (for polling)
     */
    public function getUpdates()
    {
        $user = Auth::user();
        
        $updates = User::where('id', '!=', $user->id)
            ->get()
            ->map(function ($contact) use ($user) {
                $lastMessage = Message::where(function ($q) use ($user, $contact) {
                    $q->where('sender_id', $user->id)->where('recipient_id', $contact->id);
                })->orWhere(function ($q) use ($user, $contact) {
                    $q->where('sender_id', $contact->id)->where('recipient_id', $user->id);
                })->orderBy('created_at', 'desc')->first();
                
                $unreadCount = Message::where('sender_id', $contact->id)
                    ->where('recipient_id', $user->id)
                    ->where('is_read', false)
                    ->count();
                
                return [
                    'id' => $contact->id,
                    'is_online' => $this->isUserOnline($contact->id),
                    'last_message' => $lastMessage ? $lastMessage->body : null,
                    'last_message_time' => $lastMessage ? $lastMessage->created_at : null,
                    'unread_count' => $unreadCount,
                ];
            })
            ->toArray();
        
        return response()->json([
            'success' => true,
            'data' => $updates
        ]);
    }
    
    /**
     * Check if user is online (active in last 5 minutes)
     */
    private function isUserOnline($userId)
    {
        // You can implement this using Redis or session tracking
        // For now, using a simple cache check
        if (function_exists('cache')) {
            return cache()->has('user_online_' . $userId);
        }
        return false;
    }
    
    /**
     * Update user online status
     */
    public function updateStatus(Request $request)
    {
        $user = Auth::user();
        $isOnline = $request->input('is_online', true);
        
        if (function_exists('cache')) {
            if ($isOnline) {
                cache()->put('user_online_' . $user->id, true, now()->addMinutes(5));
            } else {
                cache()->forget('user_online_' . $user->id);
            }
        }
        
        broadcast(new UserOnline($user->id, $isOnline))->toOthers();
        
        return response()->json(['success' => true]);
    }
}