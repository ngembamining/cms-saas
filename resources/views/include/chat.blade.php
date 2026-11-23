<!-- Chat drawer (toggled by header message icon via the shared `chatOpen` state) -->
<div x-data="chatDrawer()" x-show="chatOpen" x-transition.opacity
     @keydown.escape.window="active ? (active = null) : (chatOpen = false)"
     x-effect="if (! chatOpen) active = null"
     class="fixed inset-0 z-50 bg-black/40" style="display:none"
     @click.self="chatOpen = false">

    {{-- ===================== CHAT LIST DRAWER ===================== --}}
    <aside class="absolute inset-y-0 right-0 flex w-80 max-w-full flex-col bg-white shadow-xl"
           x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="translate-x-full"
           x-transition:enter-end="translate-x-0">

        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 shrink-0">
            <h6 class="font-semibold text-gray-700">{{ __('Chat List') }}</h6>
            <div class="flex items-center gap-2">
                <button @click="openNewChat()" class="text-primary-500 hover:text-primary-600 transition" title="{{ __('New Chat') }}">
                    <i class="ik ik-plus-circle text-xl"></i>
                </button>
                <button @click="chatOpen = false" class="text-gray-400 hover:text-gray-600"><i class="ik ik-x"></i></button>
            </div>
        </div>

        {{-- Search Section --}}
        <div class="border-b border-gray-100 px-4 py-3 shrink-0 relative z-40">
            <div class="relative">
                <i class="ik ik-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" x-model="search" 
                       @input.debounce.300ms="searchContacts()"
                       @focus="showSearchResults = true"
                       @click.away="setTimeout(() => showSearchResults = false, 200)"
                       placeholder="{{ __('Search contacts...') }}"
                       class="w-full rounded-lg border border-gray-200 bg-gray-50 py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-400 focus:bg-white focus:ring-2 focus:ring-primary-100">
                
                {{-- Search Results Dropdown --}}
                <div x-show="search.length > 0 && showSearchResults && searchResults.length > 0" 
                     x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="absolute left-0 right-0 top-full mt-1 rounded-lg border border-gray-200 bg-white shadow-lg z-50 overflow-y-auto"
                     style="max-height: 200px; min-width: 100%;">
                    <template x-for="contact in searchResults" :key="contact.id + '-' + contact.type">
                        <button @click="selectSearchResult(contact)"
                                class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition hover:bg-gray-50 border-b border-gray-50 last:border-0">
                            <span class="relative shrink-0">
                                <img :src="contact.avatar || ''" 
                                     class="h-8 w-8 rounded-full object-cover" alt=""
                                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+PGNpcmNsZSBjeD0iNTAiIGN5PSI1MCIgcj0iNTAiIGZpbGw9IiM0RjQ2RTUiLz48dGV4dCB4PSI1MCIgeT0iNTgiIGZvbnQtc2l6ZT0iNDAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IndoaXRlIiBmb250LWZhbWlseT0iQXJpYWwsIHNhbnMtc2VyaWYiIGZvbnQtd2VpZ2h0PSJib2xkIj5VPC90ZXh0Pjwvc3ZnPg=='">
                                <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full border-2 border-white" 
                                      :class="contact.is_online ? 'bg-green-500' : 'bg-gray-300'"></span>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between">
                                    <p class="truncate text-sm font-medium text-gray-700" x-text="contact.name"></p>
                                    <span class="text-xs text-gray-400 px-2 py-0.5 bg-gray-100 rounded-full shrink-0 ml-2" 
                                          x-text="contact.type"></span>
                                </div>
                                <p class="text-xs text-gray-400 truncate" 
                                   x-text="contact.email || contact.contact || ''"></p>
                            </div>
                        </button>
                    </template>
                </div>
                
                {{-- No results --}}
                <div x-show="search.length > 0 && showSearchResults && searchResults.length === 0" 
                     x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="absolute left-0 right-0 top-full mt-1 rounded-lg border border-gray-200 bg-white shadow-lg p-4 text-center text-sm text-gray-400 z-50">
                    {{ __('No contacts found') }}
                </div>
            </div>
        </div>

        {{-- Contact List --}}
        <div class="flex-1 overflow-y-auto min-h-0" x-ref="contactList">
            {{-- Show loading state --}}
            <div x-show="loading" 
                 class="px-4 py-10 text-center text-sm text-gray-400">
                <i class="ik ik-loader animate-spin mr-2"></i> {{ __('Loading conversations...') }}
            </div>
            
            {{-- Show empty state --}}
            <div x-show="!loading && contacts.length === 0" 
                 class="px-4 py-10 text-center text-sm text-gray-400">
                {{ __('No conversations yet') }}
                <br>
                <button @click="openNewChat()" class="text-primary-500 hover:text-primary-600 mt-2 text-sm font-medium">
                    <i class="ik ik-plus-circle mr-1"></i> {{ __('Start a new chat') }}
                </button>
            </div>
            
            {{-- Show contacts --}}
            <div x-show="contacts.length > 0">
                <template x-for="c in filteredContacts" :key="c.id + '-' + c.type">
                    <button type="button" @click="openChat(c)"
                            class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition hover:bg-gray-50"
                            :class="active && active.id === c.id && active.type === c.type ? 'bg-primary-50' : ''">
                        <span class="relative shrink-0">
                            <img :src="c.avatar || ''" 
                                 class="h-10 w-10 rounded-full object-cover" alt=""
                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+PGNpcmNsZSBjeD0iNTAiIGN5PSI1MCIgcj0iNTAiIGZpbGw9IiM0RjQ2RTUiLz48dGV4dCB4PSI1MCIgeT0iNTgiIGZvbnQtc2l6ZT0iNDAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IndoaXRlIiBmb250LWZhbWlseT0iQXJpYWwsIHNhbnMtc2VyaWYiIGZvbnQtd2VpZ2h0PSJib2xkIj5VPC90ZXh0Pjwvc3ZnPg=='">
                            <span class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-white" 
                                  :class="c.is_online ? 'bg-green-500' : 'bg-gray-300'"></span>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center justify-between gap-2">
                                <span class="truncate text-sm font-semibold text-gray-700" x-text="c.name"></span>
                                <span class="shrink-0 text-[11px] text-gray-400" x-text="c.last_message_time || ''"></span>
                            </span>
                            <span class="flex items-center justify-between gap-2">
                                <span class="truncate text-xs text-gray-400" 
                                      x-text="c.last_message || '{{ __('No messages yet') }}'"></span>
                                <span x-show="c.unread_count > 0" x-cloak 
                                      class="flex h-4 min-w-4 shrink-0 items-center justify-center rounded-full bg-primary-500 px-1 text-[10px] font-semibold text-white" 
                                      x-text="c.unread_count"></span>
                            </span>
                        </span>
                    </button>
                </template>
            </div>
            
            {{-- Show filtered no results --}}
            <p x-show="!loading && filteredContacts.length === 0 && search !== '' && contacts.length > 0" 
               class="px-4 py-10 text-center text-sm text-gray-400">{{ __('No matching contacts found') }}</p>
        </div>
    </aside>

    {{-- ===================== NEW CHAT MODAL ===================== --}}
    <div x-show="showNewChatModal" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="absolute inset-0 flex items-center justify-center bg-black/30 z-[60]">
        <div class="w-96 max-w-full bg-white rounded-2xl shadow-2xl p-6 mx-4 max-h-[90vh] flex flex-col" @click.stop>
            <div class="flex items-center justify-between mb-4 shrink-0">
                <h4 class="text-lg font-semibold text-gray-800">{{ __('New Conversation') }}</h4>
                <button @click="showNewChatModal = false; newChatSearch = ''; availableContacts = [];" 
                        class="text-gray-400 hover:text-gray-600">
                    <i class="ik ik-x"></i>
                </button>
            </div>
            
            <div class="relative mb-4 shrink-0">
                <i class="ik ik-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" x-model="newChatSearch" 
                       @input.debounce.300ms="loadAvailableContacts()"
                       @focus="showNewChatResults = true"
                       @click.away="setTimeout(() => showNewChatResults = false, 200)"
                       placeholder="{{ __('Search users, customers, suppliers...') }}"
                       class="w-full rounded-lg border border-gray-200 bg-gray-50 py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-400 focus:bg-white focus:ring-2 focus:ring-primary-100">
                
                {{-- Search Results Dropdown for New Chat --}}
                <div x-show="newChatSearch.length > 0 && showNewChatResults && availableContacts.length > 0" 
                     x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="absolute left-0 right-0 top-full mt-1 rounded-lg border border-gray-200 bg-white shadow-lg z-50 overflow-y-auto"
                     style="max-height: 200px; min-width: 100%;">
                    <template x-for="contact in availableContacts" :key="contact.id + '-' + contact.type">
                        <button @click="startNewChat(contact)" 
                                class="flex w-full items-center gap-3 px-3 py-2 hover:bg-gray-50 transition border-b border-gray-50 last:border-0">
                            <span class="relative shrink-0">
                                <img :src="contact.avatar || ''" 
                                     class="h-9 w-9 rounded-full object-cover" alt=""
                                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+PGNpcmNsZSBjeD0iNTAiIGN5PSI1MCIgcj0iNTAiIGZpbGw9IiM0RjQ2RTUiLz48dGV4dCB4PSI1MCIgeT0iNTgiIGZvbnQtc2l6ZT0iNDAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IndoaXRlIiBmb250LWZhbWlseT0iQXJpYWwsIHNhbnMtc2VyaWYiIGZvbnQtd2VpZ2h0PSJib2xkIj5VPC90ZXh0Pjwvc3ZnPg=='">
                                <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full border-2 border-white" 
                                      :class="contact.is_online ? 'bg-green-500' : 'bg-gray-300'"></span>
                            </span>
                            <div class="min-w-0 flex-1 text-left">
                                <p class="text-sm font-medium text-gray-700 truncate" x-text="contact.name"></p>
                                <p class="text-xs text-gray-400 truncate" x-text="contact.label"></p>
                            </div>
                            <span class="text-xs text-gray-400 px-2 py-0.5 bg-gray-100 rounded-full shrink-0" 
                                  x-text="contact.type"></span>
                        </button>
                    </template>
                </div>
                
                {{-- No results for New Chat --}}
                <div x-show="newChatSearch.length > 0 && showNewChatResults && availableContacts.length === 0" 
                     x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="absolute left-0 right-0 top-full mt-1 rounded-lg border border-gray-200 bg-white shadow-lg p-4 text-center text-sm text-gray-400 z-50">
                    {{ __('No contacts found') }}
                </div>
            </div>
            
            <div class="flex-1 overflow-y-auto min-h-0 space-y-1">
                <template x-for="contact in availableContacts" :key="contact.id + '-' + contact.type">
                    <button @click="startNewChat(contact)" 
                            class="flex w-full items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-50 transition">
                        <span class="relative shrink-0">
                            <img :src="contact.avatar || ''" 
                                 class="h-9 w-9 rounded-full object-cover" alt=""
                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+PGNpcmNsZSBjeD0iNTAiIGN5PSI1MCIgcj0iNTAiIGZpbGw9IiM0RjQ2RTUiLz48dGV4dCB4PSI1MCIgeT0iNTgiIGZvbnQtc2l6ZT0iNDAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IndoaXRlIiBmb250LWZhbWlseT0iQXJpYWwsIHNhbnMtc2VyaWYiIGZvbnQtd2VpZ2h0PSJib2xkIj5VPC90ZXh0Pjwvc3ZnPg=='">
                            <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full border-2 border-white" 
                                  :class="contact.is_online ? 'bg-green-500' : 'bg-gray-300'"></span>
                        </span>
                        <div class="min-w-0 flex-1 text-left">
                            <p class="text-sm font-medium text-gray-700 truncate" x-text="contact.name"></p>
                            <p class="text-xs text-gray-400 truncate" x-text="contact.label"></p>
                        </div>
                        <span class="text-xs text-gray-400 px-2 py-0.5 bg-gray-100 rounded-full shrink-0" 
                              x-text="contact.type"></span>
                    </button>
                </template>
                <p x-show="availableContacts.length === 0 && newChatSearch === ''" 
                   class="text-center text-sm text-gray-400 py-4">
                    {{ __('Type to search for contacts') }}
                </p>
            </div>
            
            <div class="mt-4 pt-4 border-t border-gray-100 flex justify-end shrink-0">
                <button @click="showNewChatModal = false; newChatSearch = ''; availableContacts = [];" 
                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
                    {{ __('Cancel') }}
                </button>
            </div>
        </div>
    </div>

    {{-- ===================== CHAT PANEL ===================== --}}
    <div x-show="active" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-3"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="absolute inset-x-3 bottom-3 flex h-[26rem] max-h-[calc(100%-1.5rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5
                sm:inset-x-auto sm:bottom-4 sm:right-[21rem] sm:w-72 z-50">

        <div class="flex items-center gap-2.5 border-b border-gray-100 bg-white px-3 py-2.5 shrink-0">
            <span class="relative shrink-0">
                <img :src="active?.avatar || ''" 
                     class="h-8 w-8 rounded-full object-cover" alt=""
                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+PGNpcmNsZSBjeD0iNTAiIGN5PSI1MCIgcj0iNTAiIGZpbGw9IiM0RjQ2RTUiLz48dGV4dCB4PSI1MCIgeT0iNTgiIGZvbnQtc2l6ZT0iNDAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IndoaXRlIiBmb250LWZhbWlseT0iQXJpYWwsIHNhbnMtc2VyaWYiIGZvbnQtd2VpZ2h0PSJib2xkIj5VPC90ZXh0Pjwvc3ZnPg=='">
                <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full border-2 border-white" 
                      :class="active?.is_online ? 'bg-green-500' : 'bg-gray-300'"></span>
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-gray-700" x-text="active?.name"></p>
                <p class="truncate text-[11px]" 
                   :class="active?.is_online ? 'text-green-500' : 'text-gray-400'" 
                   x-text="active?.is_online ? '{{ __('Online') }}' : '{{ __('Offline') }}'"></p>
            </div>
            <div class="flex items-center gap-1">
                <span x-show="active?.is_typing" x-cloak class="text-xs text-gray-400 mr-1">
                    <span class="animate-pulse">•••</span>
                </span>
                <button @click="active = null" class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600" title="{{ __('Close chat') }}">
                    <i class="ik ik-x text-sm"></i>
                </button>
            </div>
        </div>

        <div x-ref="thread" class="flex-1 space-y-2.5 overflow-y-auto bg-gray-50 px-3 py-3 min-h-0">
            <template x-for="(m, i) in (active?.messages || [])" :key="i">
                <div class="flex" :class="m.from === 'me' ? 'justify-end' : 'justify-start'">
                    <div class="max-w-[80%]">
                        <div class="rounded-2xl px-3 py-1.5 text-[13px] leading-snug"
                             :class="m.from === 'me' ? 'rounded-br-sm bg-primary-500 text-white' : 'rounded-bl-sm bg-white text-gray-700 shadow-sm'"
                             x-text="m.text"></div>
                        <p class="mt-0.5 text-[10px] text-gray-400" :class="m.from === 'me' ? 'text-right' : ''" x-text="m.time"></p>
                    </div>
                </div>
            </template>
            <div x-show="active?.messages?.length === 0" class="flex items-center justify-center h-full text-gray-400 text-sm">
                {{ __('No messages yet. Say hello!') }}
            </div>
        </div>

        <form @submit.prevent="sendMessage()" class="flex items-center gap-2 border-t border-gray-100 px-2.5 py-2.5 shrink-0">
            <input type="text" x-model="draft" placeholder="{{ __('Type a message...') }}"
                   @keydown.enter.prevent="sendMessage()"
                   class="flex-1 rounded-full border border-gray-200 bg-gray-50 px-3.5 py-1.5 text-sm outline-none focus:border-primary-400 focus:bg-white focus:ring-2 focus:ring-primary-100">
            <button type="submit" :disabled="! draft.trim() || sending"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-500 text-white transition hover:bg-primary-600 disabled:opacity-40">
                <i class="ik ik-send text-sm" :class="sending ? 'animate-spin' : ''"></i>
            </button>
        </form>
    </div>
</div>

<script>
    function chatDrawer() {
        return {
            search: '',
            draft: '',
            active: null,
            sending: false,
            contacts: [],
            searchResults: [],
            showSearchResults: false,
            showNewChatResults: false,
            pollingInterval: null,
            echoChannel: null,
            showNewChatModal: false,
            newChatSearch: '',
            availableContacts: [],
            searching: false,
            loading: true,
            
            init() {
                this.loadContacts();
                this.setupRealtimeListeners();
                
                this.pollingInterval = setInterval(() => {
                    this.pollUpdates();
                }, 5000);
            },
            
            get filteredContacts() {
                if (!this.search) return this.contacts;
                const q = this.search.toLowerCase();
                return this.contacts.filter(c => 
                    c.name && c.name.toLowerCase().includes(q) || 
                    (c.email && c.email.toLowerCase().includes(q)) ||
                    (c.contact && c.contact.toLowerCase().includes(q))
                );
            },
            
            async searchContacts() {
                if (!this.search.trim()) {
                    this.searchResults = [];
                    return;
                }
                
                this.searching = true;
                
                try {
                    const response = await fetch('/chat/contacts?search=' + encodeURIComponent(this.search));
                    const data = await response.json();
                    if (data.success) {
                        const existingIds = new Set(
                            this.contacts.map(c => c.id + '-' + c.type)
                        );
                        this.searchResults = data.data.filter(c => 
                            !existingIds.has(c.id + '-' + c.type)
                        );
                        this.showSearchResults = true;
                    }
                } catch (error) {
                    console.error('Error searching contacts:', error);
                } finally {
                    this.searching = false;
                }
            },
            
            selectSearchResult(contact) {
                const existing = this.contacts.find(c => c.id === contact.id && c.type === contact.type);
                if (existing) {
                    this.search = '';
                    this.searchResults = [];
                    this.showSearchResults = false;
                    this.openChat(existing);
                    return;
                }
                
                const newContact = {
                    ...contact,
                    last_message: null,
                    last_message_time: null,
                    unread_count: 0,
                    messages: [],
                    is_typing: false,
                };
                this.contacts.unshift(newContact);
                this.search = '';
                this.searchResults = [];
                this.showSearchResults = false;
                this.openChat(newContact);
            },
            
            async loadContacts() {
                this.loading = true;
                try {
                    const response = await fetch('/chat/contacts');
                    const data = await response.json();
                    if (data.success) {
                        this.contacts = data.data;
                        console.log('Contacts loaded:', this.contacts.length);
                    }
                } catch (error) {
                    console.error('Error loading contacts:', error);
                } finally {
                    this.loading = false;
                }
            },
            
            async loadAvailableContacts() {
                try {
                    const response = await fetch('/chat/available?search=' + encodeURIComponent(this.newChatSearch));
                    const data = await response.json();
                    if (data.success) {
                        this.availableContacts = data.data;
                    }
                } catch (error) {
                    console.error('Error loading available contacts:', error);
                }
            },
            
            openNewChat() {
                this.showNewChatModal = true;
                this.newChatSearch = '';
                this.availableContacts = [];
                this.showNewChatResults = false;
                this.$nextTick(() => this.loadAvailableContacts());
            },
            
            async startNewChat(contact) {
                const existing = this.contacts.find(c => c.id === contact.id && c.type === contact.type);
                if (existing) {
                    this.showNewChatModal = false;
                    this.newChatSearch = '';
                    this.availableContacts = [];
                    this.showNewChatResults = false;
                    this.openChat(existing);
                    return;
                }
                
                const newContact = {
                    ...contact,
                    last_message: null,
                    last_message_time: null,
                    unread_count: 0,
                    messages: [],
                    is_typing: false,
                };
                this.contacts.unshift(newContact);
                this.showNewChatModal = false;
                this.newChatSearch = '';
                this.availableContacts = [];
                this.showNewChatResults = false;
                this.openChat(newContact);
            },
            
            setupRealtimeListeners() {
                if (window.Echo) {
                    window.Echo.private('chat')
                        .listen('NewMessage', (e) => {
                            this.handleNewMessage(e);
                        })
                        .listen('UserTyping', (e) => {
                            this.handleTyping(e);
                        })
                        .listen('UserOnline', (e) => {
                            this.handleUserStatus(e);
                        });
                }
            },
            
            handleNewMessage(e) {
                const contact = this.contacts.find(c => 
                    c.id === e.sender_id && c.type === e.sender_type
                );
                
                if (contact) {
                    if (this.active && this.active.id === contact.id && this.active.type === contact.type) {
                        contact.messages.push({
                            from: 'them',
                            text: e.message,
                            time: this.formatTime(e.created_at)
                        });
                        contact.last_message = e.message;
                        contact.last_message_time = this.formatTime(e.created_at);
                        this.scrollDown();
                    } else {
                        contact.last_message = e.message;
                        contact.last_message_time = this.formatTime(e.created_at);
                        contact.unread_count = (contact.unread_count || 0) + 1;
                    }
                }
            },
            
            handleTyping(e) {
                const contact = this.contacts.find(c => c.id === e.user_id && c.type === 'user');
                if (contact) {
                    contact.is_typing = e.is_typing;
                }
            },
            
            handleUserStatus(e) {
                const contact = this.contacts.find(c => c.id === e.user_id && c.type === 'user');
                if (contact) {
                    contact.is_online = e.is_online;
                }
            },
            
            async pollUpdates() {
                try {
                    const response = await fetch('/chat/updates', {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await response.json();
                    if (data.success) {
                        data.data.forEach(update => {
                            const contact = this.contacts.find(c => 
                                c.id === update.id && c.type === update.type
                            );
                            if (contact) {
                                if (update.is_online !== undefined) contact.is_online = update.is_online;
                                if (update.last_message) {
                                    contact.last_message = update.last_message;
                                    contact.last_message_time = this.formatTime(update.last_message_time);
                                }
                                if (update.unread_count !== undefined) contact.unread_count = update.unread_count;
                            }
                        });
                    }
                } catch (error) {
                    // Silent fail
                }
            },
            
            async openChat(contact) {
                this.active = contact;
                this.draft = '';
                contact.unread_count = 0;
                
                await this.markAsRead(contact.id, contact.type);
                await this.loadMessages(contact.id, contact.type);
                
                this.$nextTick(() => this.scrollDown());
            },
            
            async loadMessages(contactId, contactType) {
                try {
                    const response = await fetch('/chat/messages?contact_id=' + contactId + '&contact_type=' + contactType);
                    const data = await response.json();
                    if (data.success && this.active) {
                        this.active.messages = data.data.map(m => ({
                            from: m.sender_id === {{ Auth::id() }} && m.sender_type === 'user' ? 'me' : 'them',
                            text: m.body,
                            time: this.formatTime(m.created_at)
                        }));
                        this.scrollDown();
                    }
                } catch (error) {
                    console.error('Error loading messages:', error);
                }
            },
            
            async sendMessage() {
                const text = this.draft.trim();
                if (!text || !this.active || this.sending) return;
                
                this.sending = true;
                
                try {
                    const response = await fetch('/chat/send', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            recipient_id: this.active.id,
                            recipient_type: this.active.type,
                            message: text
                        })
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        this.active.messages.push({
                            from: 'me',
                            text: text,
                            time: this.formatTime(new Date())
                        });
                        this.active.last_message = text;
                        this.active.last_message_time = this.formatTime(new Date());
                        this.draft = '';
                        this.$nextTick(() => this.scrollDown());
                    } else {
                        alert(data.message || 'Failed to send message');
                    }
                } catch (error) {
                    console.error('Error sending message:', error);
                    alert('Failed to send message. Please try again.');
                }
                
                this.sending = false;
            },
            
            async markAsRead(contactId, contactType) {
                try {
                    await fetch('/chat/mark-read', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ 
                            contact_id: contactId,
                            contact_type: contactType
                        })
                    });
                } catch (error) {
                    // Silent fail
                }
            },
            
            formatTime(date) {
                if (!date) return '';
                const d = new Date(date);
                const now = new Date();
                
                if (d.toDateString() === now.toDateString()) {
                    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                } else if (d.getDate() === now.getDate() - 1) {
                    return 'Yesterday';
                } else {
                    return d.toLocaleDateString([], { day: 'numeric', month: 'short' });
                }
            },
            
            scrollDown() {
                const el = this.$refs.thread;
                if (el) {
                    setTimeout(() => el.scrollTop = el.scrollHeight, 50);
                }
            },
            
            destroy() {
                if (this.pollingInterval) clearInterval(this.pollingInterval);
                if (window.Echo) window.Echo.leave('chat');
            }
        };
    }
</script>