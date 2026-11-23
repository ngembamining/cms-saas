<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Message;
use App\Events\NewMessage;
use App\Events\UserTyping;
use App\Events\UserOnline;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /**
     * Get all contacts with conversations (users, customers, suppliers)
     */
    public function getContacts(Request $request)
    {
        $user = Auth::user();
        $search = $request->input('search', '');
        
        // Get all contacts that have messages with the current user
        // Users who have sent messages to current user
        $userSenderIds = Message::where('recipient_id', $user->id)
            ->where('recipient_type', 'user')
            ->where('sender_type', 'user')
            ->pluck('sender_id')
            ->unique()
            ->toArray();
        
        // Users who current user has sent messages to
        $userRecipientIds = Message::where('sender_id', $user->id)
            ->where('sender_type', 'user')
            ->where('recipient_type', 'user')
            ->pluck('recipient_id')
            ->unique()
            ->toArray();
        
        // Customers who have sent messages to current user
        $customerSenderIds = Message::where('recipient_id', $user->id)
            ->where('recipient_type', 'user')
            ->where('sender_type', 'customer')
            ->pluck('sender_id')
            ->unique()
            ->toArray();
        
        // Customers who current user has sent messages to
        $customerRecipientIds = Message::where('sender_id', $user->id)
            ->where('sender_type', 'user')
            ->where('recipient_type', 'customer')
            ->pluck('recipient_id')
            ->unique()
            ->toArray();
        
        // Suppliers who have sent messages to current user
        $supplierSenderIds = Message::where('recipient_id', $user->id)
            ->where('recipient_type', 'user')
            ->where('sender_type', 'supplier')
            ->pluck('sender_id')
            ->unique()
            ->toArray();
        
        // Suppliers who current user has sent messages to
        $supplierRecipientIds = Message::where('sender_id', $user->id)
            ->where('sender_type', 'user')
            ->where('recipient_type', 'supplier')
            ->pluck('recipient_id')
            ->unique()
            ->toArray();
        
        // Merge all contact IDs for each type
        $userIds = array_unique(array_merge($userSenderIds, $userRecipientIds));
        $customerIds = array_unique(array_merge($customerSenderIds, $customerRecipientIds));
        $supplierIds = array_unique(array_merge($supplierSenderIds, $supplierRecipientIds));
        
        // Get users with conversations
        $usersQuery = User::whereIn('id', $userIds);
        if ($search) {
            $usersQuery->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('username', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('full_name', 'LIKE', "%{$search}%");
            });
        }
        $users = $usersQuery->get();
        
        // Get customers with conversations
        $customersQuery = Customer::whereIn('id', $customerIds);
        if ($search) {
            $customersQuery->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('contact', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }
        $customers = $customersQuery->get();
        
        // Get suppliers with conversations
        $suppliersQuery = Supplier::whereIn('id', $supplierIds);
        if ($search) {
            $suppliersQuery->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('contact', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }
        $suppliers = $suppliersQuery->get();
        
        // Format contacts
        $contacts = [];
        
        foreach ($users as $contact) {
            $contacts[] = $this->formatContact($contact, 'user', $user);
        }
        
        foreach ($customers as $contact) {
            $contacts[] = $this->formatContact($contact, 'customer', $user);
        }
        
        foreach ($suppliers as $contact) {
            $contacts[] = $this->formatContact($contact, 'supplier', $user);
        }
        
        // Sort by last message time (most recent first)
        usort($contacts, function($a, $b) {
            $timeA = $a['last_message_time_raw'] ?? '1970-01-01 00:00:00';
            $timeB = $b['last_message_time_raw'] ?? '1970-01-01 00:00:00';
            return strtotime($timeB) - strtotime($timeA);
        });
        
        return response()->json([
            'success' => true,
            'data' => $contacts
        ]);
    }

    /**
     * Get available contacts for starting a new chat (excludes existing chat contacts)
     */
    public function getAvailableContacts(Request $request)
    {
        $user = Auth::user();
        $search = $request->input('search', '');
        
        // Get IDs of contacts already in chat list
        $existingUserIds = Message::where('recipient_id', $user->id)
            ->where('recipient_type', 'user')
            ->where('sender_type', 'user')
            ->pluck('sender_id')
            ->merge(
                Message::where('sender_id', $user->id)
                    ->where('sender_type', 'user')
                    ->where('recipient_type', 'user')
                    ->pluck('recipient_id')
            )
            ->unique()
            ->toArray();
        
        $existingCustomerIds = Message::where('recipient_id', $user->id)
            ->where('recipient_type', 'user')
            ->where('sender_type', 'customer')
            ->pluck('sender_id')
            ->merge(
                Message::where('sender_id', $user->id)
                    ->where('sender_type', 'user')
                    ->where('recipient_type', 'customer')
                    ->pluck('recipient_id')
            )
            ->unique()
            ->toArray();
        
        $existingSupplierIds = Message::where('recipient_id', $user->id)
            ->where('recipient_type', 'user')
            ->where('sender_type', 'supplier')
            ->pluck('sender_id')
            ->merge(
                Message::where('sender_id', $user->id)
                    ->where('sender_type', 'user')
                    ->where('recipient_type', 'supplier')
                    ->pluck('recipient_id')
            )
            ->unique()
            ->toArray();
        
        // System users (excluding current and existing)
        $usersQuery = User::where('id', '!=', $user->id);
        if (!empty($existingUserIds)) {
            $usersQuery->whereNotIn('id', $existingUserIds);
        }
        if ($search) {
            $usersQuery->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('username', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('full_name', 'LIKE', "%{$search}%");
            });
        }
        $users = $usersQuery->limit(20)->get();
        
        // Customers (excluding existing)
        $customersQuery = Customer::query();
        if (!empty($existingCustomerIds)) {
            $customersQuery->whereNotIn('id', $existingCustomerIds);
        }
        if ($search) {
            $customersQuery->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('contact', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }
        $customers = $customersQuery->limit(20)->get();
        
        // Suppliers (excluding existing)
        $suppliersQuery = Supplier::query();
        if (!empty($existingSupplierIds)) {
            $suppliersQuery->whereNotIn('id', $existingSupplierIds);
        }
        if ($search) {
            $suppliersQuery->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('contact', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }
        $suppliers = $suppliersQuery->limit(20)->get();
        
        $contacts = [];
        
        foreach ($users as $contact) {
            $contacts[] = [
                'id' => $contact->id,
                'type' => 'user',
                'name' => $contact->full_name ?? $contact->name ?? $contact->username,
                'email' => $contact->email ?? '',
                'avatar' => null,
                'contact' => null,
                'label' => 'User: ' . ($contact->full_name ?? $contact->name ?? $contact->username),
                'is_online' => $this->isUserOnline($contact->id),
            ];
        }
        
        foreach ($customers as $contact) {
            $contacts[] = [
                'id' => $contact->id,
                'type' => 'customer',
                'name' => $contact->name,
                'email' => $contact->email ?? '',
                'avatar' => null,
                'contact' => $contact->contact ?? '',
                'label' => 'Customer: ' . $contact->name,
                'is_online' => false,
            ];
        }
        
        foreach ($suppliers as $contact) {
            $contacts[] = [
                'id' => $contact->id,
                'type' => 'supplier',
                'name' => $contact->name,
                'email' => $contact->email ?? '',
                'avatar' => null,
                'contact' => $contact->contact ?? '',
                'label' => 'Supplier: ' . $contact->name,
                'is_online' => false,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $contacts
        ]);
    }

    /**
     * Format contact data
     */
    private function formatContact($contact, $type, $user)
    {
        $id = $contact->id;
        $name = $contact->full_name ?? $contact->name ?? $contact->username ?? 'Unknown';
        $email = $contact->email ?? '';
        $avatar = null;
        $contactField = null;
        
        if ($type === 'user') {
            $name = $contact->full_name ?? $contact->name ?? $contact->username;
            $email = $contact->email ?? '';
        } elseif ($type === 'customer') {
            $email = $contact->email ?? '';
            $contactField = $contact->contact ?? '';
        } elseif ($type === 'supplier') {
            $email = $contact->email ?? '';
            $contactField = $contact->contact ?? '';
        }

        // Get last message
        $lastMessage = $this->getLastMessage($user->id, $id, $type);
        
        // Get unread count
        $unreadCount = $this->getUnreadCount($user->id, $id, $type);

        return [
            'id' => $id,
            'type' => $type,
            'name' => $name,
            'email' => $email,
            'avatar' => $avatar,
            'contact' => $contactField,
            'is_online' => $type === 'user' ? $this->isUserOnline($id) : false,
            'is_typing' => false,
            'last_message' => $lastMessage ? $lastMessage->body : null,
            'last_message_time' => $lastMessage ? $lastMessage->created_at->diffForHumans() : null,
            'last_message_time_raw' => $lastMessage ? $lastMessage->created_at : null,
            'unread_count' => $unreadCount,
            'messages' => [],
        ];
    }

    /**
     * Get messages with a contact
     */
    public function getMessages(Request $request)
    {
        $user = Auth::user();
        $contactId = $request->input('contact_id');
        $contactType = $request->input('contact_type', 'user');

        if (!$contactId) {
            return response()->json([
                'success' => false,
                'message' => 'Contact ID is required'
            ], 400);
        }

        $messages = Message::where(function ($q) use ($user, $contactId, $contactType) {
            $q->where('sender_id', $user->id)
              ->where('sender_type', 'user')
              ->where('recipient_id', $contactId)
              ->where('recipient_type', $contactType);
        })->orWhere(function ($q) use ($user, $contactId, $contactType) {
            $q->where('sender_id', $contactId)
              ->where('sender_type', $contactType)
              ->where('recipient_id', $user->id)
              ->where('recipient_type', 'user');
        })
        ->orderBy('created_at', 'asc')
        ->get();

        // Mark messages as read
        Message::where('sender_id', $contactId)
            ->where('sender_type', $contactType)
            ->where('recipient_id', $user->id)
            ->where('recipient_type', 'user')
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
            'recipient_id' => 'required',
            'recipient_type' => 'required|in:user,customer,supplier',
            'message' => 'required|string|max:1000'
        ]);

        $user = Auth::user();

        $message = Message::create([
            'sender_id' => $user->id,
            'sender_type' => 'user',
            'recipient_id' => $request->recipient_id,
            'recipient_type' => $request->recipient_type,
            'body' => $request->message,
            'is_read' => false,
        ]);

        // Load the message with sender relationship
        $message->load('sender');

        // Broadcast the message
        try {
            broadcast(new NewMessage($message))->toOthers();
        } catch (\Exception $e) {
            // Silent fail for broadcasting
        }

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
        $contactId = $request->input('contact_id');
        $contactType = $request->input('contact_type', 'user');

        if (!$contactId) {
            return response()->json([
                'success' => false,
                'message' => 'Contact ID is required'
            ], 400);
        }

        $count = Message::where('sender_id', $contactId)
            ->where('sender_type', $contactType)
            ->where('recipient_id', $user->id)
            ->where('recipient_type', 'user')
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
    public function getUpdates(Request $request)
    {
        $user = Auth::user();
        
        // Get all contacts that have conversations
        $userIds = Message::where('recipient_id', $user->id)
            ->where('recipient_type', 'user')
            ->where('sender_type', 'user')
            ->pluck('sender_id')
            ->merge(
                Message::where('sender_id', $user->id)
                    ->where('sender_type', 'user')
                    ->where('recipient_type', 'user')
                    ->pluck('recipient_id')
            )
            ->unique()
            ->toArray();
        
        $customerIds = Message::where('recipient_id', $user->id)
            ->where('recipient_type', 'user')
            ->where('sender_type', 'customer')
            ->pluck('sender_id')
            ->merge(
                Message::where('sender_id', $user->id)
                    ->where('sender_type', 'user')
                    ->where('recipient_type', 'customer')
                    ->pluck('recipient_id')
            )
            ->unique()
            ->toArray();
        
        $supplierIds = Message::where('recipient_id', $user->id)
            ->where('recipient_type', 'user')
            ->where('sender_type', 'supplier')
            ->pluck('sender_id')
            ->merge(
                Message::where('sender_id', $user->id)
                    ->where('sender_type', 'user')
                    ->where('recipient_type', 'supplier')
                    ->pluck('recipient_id')
            )
            ->unique()
            ->toArray();
        
        $updates = [];
        
        // Get user updates
        $users = User::whereIn('id', $userIds)->get();
        foreach ($users as $contact) {
            $updates[] = $this->getContactUpdate($user, $contact, 'user');
        }
        
        // Get customer updates
        $customers = Customer::whereIn('id', $customerIds)->get();
        foreach ($customers as $contact) {
            $updates[] = $this->getContactUpdate($user, $contact, 'customer');
        }
        
        // Get supplier updates
        $suppliers = Supplier::whereIn('id', $supplierIds)->get();
        foreach ($suppliers as $contact) {
            $updates[] = $this->getContactUpdate($user, $contact, 'supplier');
        }

        return response()->json([
            'success' => true,
            'data' => $updates
        ]);
    }

    /**
     * Get contact update data
     */
    private function getContactUpdate($user, $contact, $type)
    {
        $lastMessage = $this->getLastMessage($user->id, $contact->id, $type);
        $unreadCount = $this->getUnreadCount($user->id, $contact->id, $type);

        return [
            'id' => $contact->id,
            'type' => $type,
            'is_online' => $type === 'user' ? $this->isUserOnline($contact->id) : false,
            'last_message' => $lastMessage ? $lastMessage->body : null,
            'last_message_time' => $lastMessage ? $lastMessage->created_at : null,
            'unread_count' => $unreadCount,
        ];
    }

    /**
     * Get last message between user and contact
     */
    private function getLastMessage($userId, $contactId, $contactType)
    {
        return Message::where(function ($q) use ($userId, $contactId, $contactType) {
            $q->where('sender_id', $userId)
              ->where('sender_type', 'user')
              ->where('recipient_id', $contactId)
              ->where('recipient_type', $contactType);
        })->orWhere(function ($q) use ($userId, $contactId, $contactType) {
            $q->where('sender_id', $contactId)
              ->where('sender_type', $contactType)
              ->where('recipient_id', $userId)
              ->where('recipient_type', 'user');
        })
        ->orderBy('created_at', 'desc')
        ->first();
    }

    /**
     * Get unread count for a contact
     */
    private function getUnreadCount($userId, $contactId, $contactType)
    {
        return Message::where('sender_id', $contactId)
            ->where('sender_type', $contactType)
            ->where('recipient_id', $userId)
            ->where('recipient_type', 'user')
            ->where('is_read', false)
            ->count();
    }

    /**
     * Check if user is online
     */
    private function isUserOnline($userId)
    {
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
        
        try {
            broadcast(new UserOnline($user->id, $isOnline))->toOthers();
        } catch (\Exception $e) {
            // Silent fail
        }
        
        return response()->json(['success' => true]);
    }

    /**
     * Get unread count for all contacts (for badge)
     */
    public function getUnreadCounts()
    {
        $user = Auth::user();
        
        $totalUnread = Message::where('recipient_id', $user->id)
            ->where('recipient_type', 'user')
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'total_unread' => $totalUnread
        ]);
    }
}