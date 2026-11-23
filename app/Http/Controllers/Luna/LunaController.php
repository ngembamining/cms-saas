<?php

namespace App\Http\Controllers\Luna;

use App\Http\Controllers\Controller;
use App\Services\LunaCoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LunaController extends Controller
{
    protected $lunaService;

    public function __construct(LunaCoreService $lunaService)
    {
        $this->lunaService = $lunaService;
    }

    /**
     * Show Luna chat interface
     */
    public function index()
    {
        $user = Auth::user();
        $conversations = $this->lunaService->getConversations($user->id);
        
        return view('luna.index', compact('conversations'));
    }

    /**
     * Send a message to Luna
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'conversation_id' => 'nullable|exists:luna_conversations,id',
        ]);

        $user = Auth::user();
        $conversationId = $request->input('conversation_id');

        $response = $this->lunaService->processMessage(
            $user,
            $request->input('message'),
            $conversationId
        );

        return response()->json($response);
    }

    /**
     * Get conversation history
     */
    public function getConversation(Request $request, $id)
    {
        $user = Auth::user();
        $conversation = $this->lunaService->getConversation($user->id, $id);

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $conversation
        ]);
    }

    /**
     * Get all conversations
     */
    public function getConversations()
    {
        $user = Auth::user();
        $conversations = $this->lunaService->getConversations($user->id);

        return response()->json([
            'success' => true,
            'data' => $conversations
        ]);
    }

    /**
     * Delete a conversation
     */
    public function deleteConversation(Request $request, $id)
    {
        $user = Auth::user();
        $deleted = $this->lunaService->deleteConversation($user->id, $id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Conversation deleted successfully'
        ]);
    }

    /**
     * Get suggested questions
     */
    public function getSuggestions()
    {
        $suggestions = [
            'What is the current gold price?',
            'How do I submit a gold purchase request?',
            'What are the safety procedures for mining operations?',
            'Tell me about our investment portfolio.',
            'How can I access the latest geology reports?',
            'What is the SOP for equipment maintenance?',
            'Explain the company\'s gold trading policy.',
            'What are the environmental compliance requirements?',
        ];

        return response()->json([
            'success' => true,
            'data' => $suggestions
        ]);
    }
}