@extends('layouts.main')
@section('title', 'LUNA - AI Assistant')
@section('content')

<style>
    .luna-chat-container {
        height: calc(100vh - 200px);
        min-height: 400px;
        display: flex;
        flex-direction: column;
    }
    .luna-messages {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
    }
    .luna-message {
        margin-bottom: 1rem;
        max-width: 80%;
    }
    .luna-message.user {
        margin-left: auto;
    }
    .luna-message.assistant {
        margin-right: auto;
    }
    .luna-message .bubble {
        padding: 0.75rem 1rem;
        border-radius: 1rem;
        display: inline-block;
    }
    .luna-message.user .bubble {
        background: #4F46E5;
        color: white;
        border-bottom-right-radius: 0.25rem;
    }
    .luna-message.assistant .bubble {
        background: #F3F4F6;
        color: #1F2937;
        border-bottom-left-radius: 0.25rem;
    }
    .luna-message .avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 14px;
        margin-bottom: 0.25rem;
    }
    .luna-message.user .avatar {
        background: #4F46E5;
        color: white;
        margin-left: auto;
    }
    .luna-message.assistant .avatar {
            background: #10B981;
            color: white;
    }
    .luna-typing {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.5rem 1rem;
    }
    .luna-typing span {
        width: 8px;
        height: 8px;
        background: #9CA3AF;
        border-radius: 50%;
        animation: luna-typing 1.4s infinite both;
    }
    .luna-typing span:nth-child(2) { animation-delay: 0.2s; }
    .luna-typing span:nth-child(3) { animation-delay: 0.4s; }
    @keyframes luna-typing {
        0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
        30% { transform: translateY(-8px); opacity: 1; }
    }
    .luna-suggestion {
        padding: 0.5rem 1rem;
        background: #F3F4F6;
        border-radius: 9999px;
        cursor: pointer;
        transition: background 0.2s;
        font-size: 0.875rem;
        white-space: nowrap;
    }
    .luna-suggestion:hover {
        background: #E5E7EB;
    }
    .luna-conversation-item {
        padding: 0.75rem 1rem;
        cursor: pointer;
        transition: background 0.2s;
        border-radius: 0.5rem;
    }
    .luna-conversation-item:hover {
        background: #F3F4F6;
    }
    .luna-conversation-item.active {
        background: #EEF2FF;
    }
</style>

<div class="max-w-7xl mx-auto">
    <x-page-header title="{{ __('LUNA - AI Assistant') }}" 
                   subtitle="{{ __('Your intelligent assistant for Ngemba Mining operations') }}" 
                   icon="fas fa-brain"
                   :breadcrumbs="['Home' => route('dashboard'), 'LUNA' => null]" />

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Left Sidebar - Conversations -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="flex items-center justify-between mb-4">
                    <h5 class="font-semibold text-gray-700">{{ __('Conversations') }}</h5>
                    <button onclick="newConversation()" class="text-primary-500 hover:text-primary-600">
                        <i class="ik ik-plus-circle text-xl"></i>
                    </button>
                </div>
                <div id="conversation-list" class="space-y-1 max-h-[400px] overflow-y-auto">
                    @forelse($conversations as $conv)
                        <div class="luna-conversation-item {{ $loop->first ? 'active' : '' }}" 
                             data-id="{{ $conv->id }}"
                             onclick="loadConversation({{ $conv->id }})">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700 truncate">{{ $conv->title }}</span>
                                <span class="text-xs text-gray-400">{{ $conv->messages_count }} msgs</span>
                            </div>
                            <div class="text-xs text-gray-400">
                                {{ $conv->updated_at->diffForHumans() }}
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-gray-400 py-4 text-sm">
                            {{ __('No conversations yet') }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Main Chat Area -->
        <div class="lg:col-span-3">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <!-- Chat Header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-primary-500 flex items-center justify-center text-white font-bold text-sm">
                            L
                        </div>
                        <div>
                            <h5 class="font-semibold text-gray-700">Luna</h5>
                            <p class="text-xs text-gray-400">{{ __('AI Assistant • Ngemba Mining') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="flex items-center gap-1 text-xs text-green-500">
                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                            {{ __('Online') }}
                        </span>
                        <button onclick="clearChat()" class="text-gray-400 hover:text-gray-600">
                            <i class="ik ik-trash-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Messages -->
                <div class="luna-chat-container">
                    <div id="luna-messages" class="luna-messages">
                        <!-- Welcome Message -->
                        <div class="luna-message assistant">
                            <div class="avatar">L</div>
                            <div class="bubble">
                                <p class="font-semibold">Hello! I'm Luna 👋</p>
                                <p class="text-sm mt-1">I'm your AI assistant for Ngemba Mining. I can help you with:</p>
                                <ul class="text-sm mt-1 list-disc list-inside space-y-0.5">
                                    <li>⛏️ Mining operations and geology</li>
                                    <li>💰 Gold trade and prices</li>
                                    <li>📈 Investment and finance</li>
                                    <li>📋 Company policies and SOPs</li>
                                    <li>📄 Reports and documentation</li>
                                </ul>
                                <p class="text-sm mt-2 text-gray-500">How can I assist you today?</p>
                            </div>
                        </div>
                    </div>

                    <!-- Typing Indicator -->
                    <div id="luna-typing" class="luna-typing" style="display: none;">
                        <span></span><span></span><span></span>
                        <span class="text-sm text-gray-400 ml-2">Luna is thinking...</span>
                    </div>

                    <!-- Suggestions -->
                    <div id="luna-suggestions" class="px-4 py-2 border-t border-gray-100 flex gap-2 overflow-x-auto">
                        <button class="luna-suggestion" onclick="sendSuggestion('What is the current gold price?')">
                            💰 Gold price
                        </button>
                        <button class="luna-suggestion" onclick="sendSuggestion('How do I submit a gold purchase request?')">
                            📝 Gold purchase
                        </button>
                        <button class="luna-suggestion" onclick="sendSuggestion('What are the safety procedures?')">
                            🦺 Safety
                        </button>
                        <button class="luna-suggestion" onclick="sendSuggestion('Tell me about our investment portfolio.')">
                            📊 Investment
                        </button>
                    </div>

                    <!-- Input -->
                    <div class="p-4 border-t border-gray-100 bg-gray-50">
                        <form id="luna-form" class="flex gap-2">
                            <input type="text" id="luna-input" 
                                   placeholder="{{ __('Ask Luna anything...') }}"
                                   class="flex-1 rounded-lg border border-gray-200 px-4 py-2 focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                            <button type="submit" id="luna-submit"
                                    class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition disabled:opacity-50">
                                <i class="ik ik-send"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentConversationId = null;
let isProcessing = false;

document.addEventListener('DOMContentLoaded', function() {
    // Load first conversation if exists
    const firstConv = document.querySelector('.luna-conversation-item.active');
    if (firstConv) {
        loadConversation(parseInt(firstConv.dataset.id));
    }

    // Handle form submission
    document.getElementById('luna-form').addEventListener('submit', function(e) {
        e.preventDefault();
        sendMessage();
    });

    // Handle Enter key
    document.getElementById('luna-input').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
});

function sendMessage() {
    const input = document.getElementById('luna-input');
    const message = input.value.trim();
    
    if (!message || isProcessing) return;
    
    input.value = '';
    isProcessing = true;
    document.getElementById('luna-submit').disabled = true;
    
    // Add user message
    addMessage('user', message);
    
    // Show typing indicator
    document.getElementById('luna-typing').style.display = 'flex';
    document.getElementById('luna-suggestions').style.display = 'none';
    
    // Send to server
    fetch('{{ route("luna.send") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            message: message,
            conversation_id: currentConversationId
        })
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('luna-typing').style.display = 'none';
        document.getElementById('luna-suggestions').style.display = 'flex';
        
        if (data.success) {
            currentConversationId = data.conversation_id;
            addMessage('assistant', data.message);
            updateConversationList(data.conversation_id);
        } else {
            addMessage('assistant', 'I apologize, but I encountered an issue. Please try again.');
        }
    })
    .catch(error => {
        document.getElementById('luna-typing').style.display = 'none';
        document.getElementById('luna-suggestions').style.display = 'flex';
        addMessage('assistant', 'I apologize, but I encountered a network issue. Please check your connection and try again.');
        console.error('Error:', error);
    })
    .finally(() => {
        isProcessing = false;
        document.getElementById('luna-submit').disabled = false;
    });
}

function addMessage(role, content) {
    const container = document.getElementById('luna-messages');
    const div = document.createElement('div');
    div.className = `luna-message ${role}`;
    
    const avatar = document.createElement('div');
    avatar.className = 'avatar';
    avatar.textContent = role === 'user' ? 'U' : 'L';
    
    const bubble = document.createElement('div');
    bubble.className = 'bubble';
    
    // Process content (support markdown-like formatting)
    const lines = content.split('\n');
    lines.forEach(line => {
        if (line.trim()) {
            const p = document.createElement('p');
            p.innerHTML = line;
            bubble.appendChild(p);
        }
    });
    
    div.appendChild(avatar);
    div.appendChild(bubble);
    container.appendChild(div);
    
    // Scroll to bottom
    container.scrollTop = container.scrollHeight;
}

function sendSuggestion(text) {
    document.getElementById('luna-input').value = text;
    sendMessage();
}

function newConversation() {
    currentConversationId = null;
    document.getElementById('luna-messages').innerHTML = `
        <div class="luna-message assistant">
            <div class="avatar">L</div>
            <div class="bubble">
                <p class="font-semibold">Hello! I'm Luna 👋</p>
                <p class="text-sm mt-1">I'm your AI assistant for Ngemba Mining. How can I help you today?</p>
            </div>
        </div>
    `;
    document.getElementById('luna-input').focus();
}

function loadConversation(id) {
    currentConversationId = id;
    
    // Highlight active conversation
    document.querySelectorAll('.luna-conversation-item').forEach(el => {
        el.classList.remove('active');
    });
    const activeEl = document.querySelector(`.luna-conversation-item[data-id="${id}"]`);
    if (activeEl) activeEl.classList.add('active');
    
    // Load messages
    fetch(`/luna/conversation/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('luna-messages');
                container.innerHTML = '';
                data.data.messages.forEach(msg => {
                    addMessage(msg.role, msg.content);
                });
            }
        })
        .catch(error => console.error('Error loading conversation:', error));
}

function updateConversationList(conversationId) {
    // Refresh conversation list via AJAX
    fetch('{{ route("luna.conversations") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const list = document.getElementById('conversation-list');
                list.innerHTML = '';
                data.data.forEach(conv => {
                    const div = document.createElement('div');
                    div.className = `luna-conversation-item${conv.id === conversationId ? ' active' : ''}`;
                    div.dataset.id = conv.id;
                    div.onclick = function() { loadConversation(conv.id); };
                    div.innerHTML = `
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700 truncate">${conv.title}</span>
                            <span class="text-xs text-gray-400">${conv.messages_count} msgs</span>
                        </div>
                        <div class="text-xs text-gray-400">${new Date(conv.updated_at).toLocaleDateString()}</div>
                    `;
                    list.appendChild(div);
                });
            }
        })
        .catch(error => console.error('Error updating conversation list:', error));
}

function clearChat() {
    if (currentConversationId && confirm('Delete this conversation?')) {
        fetch(`/luna/conversation/${currentConversationId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                newConversation();
                updateConversationList(null);
            }
        })
        .catch(error => console.error('Error deleting conversation:', error));
    }
}
</script>

@endsection