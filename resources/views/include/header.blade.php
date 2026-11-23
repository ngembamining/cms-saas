<header class="fixed inset-x-0 top-0 z-30 border-b border-black/5 bg-topbar text-topbar-text lg:pl-60">

    <!-- ================= NAVBAR ROW ================= -->
    <div class="flex h-16 items-center justify-between px-4 sm:px-6">

        <!-- Left -->
        <div class="flex items-center gap-2">
            <button type="button" @click="sidebarOpen = ! sidebarOpen"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-topbar-text/70 hover:bg-gray-500/10 lg:hidden">
                <i class="ik ik-menu"></i>
            </button>

            @include('include.topmenu')

            <button type="button" @click="$dispatch('open-command')" title="{{ __('Search') }}"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-topbar-text/70 hover:bg-gray-500/10 md:hidden">
                <i class="ik ik-search"></i>
            </button>

            <button type="button" onclick="document.fullscreenElement ? document.exitFullscreen() : document.documentElement.requestFullscreen()"
                    class="hidden h-9 w-9 items-center justify-center rounded-lg text-topbar-text/70 hover:bg-gray-500/10 sm:flex">
                <i class="ik ik-maximize"></i>
            </button>
        </div>

        <!-- Right -->
        <div class="flex items-center gap-1">

            <!-- Quick create -->
            <x-dropdown width="w-52">
                <x-slot:trigger>
                    <button class="flex h-9 items-center gap-1.5 rounded-lg bg-primary-500/10 px-2.5 text-sm font-medium text-primary-600 transition hover:bg-primary-500/15" title="{{ __('Quick create') }}">
                        <i class="ik ik-plus"></i><span class="hidden sm:inline">{{ __('Create') }}</span>
                    </button>
                </x-slot:trigger>
                <div class="px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ __('Quick create') }}</div>
                
                @php
                    $user = Auth::user();
                    $role = $user->role;
                @endphp

                @if(in_array($role, ['director', 'branch_manager', 'accountant', 'staff']))
                <a href="{{ route('transactions.gold.purchases.create') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    <i class="ik ik-shopping-bag text-gray-400"></i> {{ __('Gold Purchase') }}
                </a>
                @endif
                
                @if(in_array($role, ['director', 'branch_manager', 'accountant', 'staff']))
                <a href="{{ route('transactions.gold.sales.create') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    <i class="ik ik-trending-up text-gray-400"></i> {{ __('Gold Sale') }}
                </a>
                @endif
                
                @if(in_array($role, ['director', 'branch_manager']))
                <a href="{{ route('loans.applications.create') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    <i class="ik ik-file-text text-gray-400"></i> {{ __('Loan Application') }}
                </a>
                @endif
                
                @if(in_array($role, ['director', 'branch_manager', 'accountant']))
                <a href="{{ route('finance.expenses.create') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    <i class="ik ik-dollar-sign text-gray-400"></i> {{ __('Expense') }}
                </a>
                @endif
                
                <div class="my-1 border-t border-gray-100"></div>
                
                <a href="{{ route('suppliers.create') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    <i class="ik ik-truck text-gray-400"></i> {{ __('Supplier') }}
                </a>
                
                <a href="{{ route('customers.create') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    <i class="ik ik-user text-gray-400"></i> {{ __('Customer') }}
                </a>
                
                @if(in_array($role, ['director', 'hr']))
                <div class="my-1 border-t border-gray-100"></div>
                <a href="{{ route('management.users.create') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    <i class="ik ik-user-plus text-gray-400"></i> {{ __('New User') }}
                </a>
                @endif
                
                @if($role == 'director')
                <a href="{{ route('management.branches.create') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-store text-gray-400"></i> {{ __('New Branch') }}
                </a>
                @endif
            </x-dropdown>

            <!-- Notifications -->
            <x-dropdown width="w-80">
                <x-slot:trigger>
                    <button class="relative flex h-9 w-9 items-center justify-center rounded-lg text-topbar-text/70 hover:bg-gray-500/10">
                        <i class="ik ik-bell"></i>
                        @php
                            $unreadCount = DB::table('notifications')
                                ->where('user_id', Auth::id())
                                ->where('is_read', 0)
                                ->count();
                        @endphp
                        @if($unreadCount > 0)
                        <span class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-accent-500 px-1 text-[10px] font-semibold text-white">
                            {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                        </span>
                        @endif
                    </button>
                </x-slot:trigger>

                <div class="border-b border-gray-100 px-4 py-2 text-sm font-semibold text-gray-700">
                    {{ __('Notifications') }}
                    @if($unreadCount > 0)
                        <span class="ml-2 text-xs font-normal text-gray-400">({{ $unreadCount }} unread)</span>
                    @endif
                </div>
                <div class="max-h-80 overflow-y-auto">
                    @php
                        $notifications = DB::table('notifications')
                            ->where('user_id', Auth::id())
                            ->orderBy('created_at', 'desc')
                            ->limit(5)
                            ->get();
                    @endphp
                    @forelse($notifications as $notification)
                        <a href="#" class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 {{ $notification->is_read ? '' : 'bg-primary-50' }}">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary-600">
                                <i class="ik ik-bell"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm text-gray-700">{{ $notification->message }}</span>
                                <span class="block text-xs text-gray-400">{{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}</span>
                            </span>
                        </a>
                    @empty
                        <div class="px-4 py-8 text-center text-gray-400">
                            <i class="ik ik-bell-off text-3xl block mb-2"></i>
                            {{ __('No notifications') }}
                        </div>
                    @endforelse
                </div>
                <a href="{{ route('communication.notifications.index') }}" 
                   class="block border-t border-gray-100 px-4 py-2 text-center text-sm font-medium text-primary-600 hover:bg-gray-50">
                    {{ __('See all notifications') }}
                </a>
            </x-dropdown>

            <!-- Chat drawer toggle -->
            @php
                $chatUnreadCount = DB::table('messages')
                    ->where('recipient_id', Auth::id())
                    ->where('recipient_type', 'user')
                    ->where('is_read', 0)
                    ->count();
            @endphp
            <button type="button" @click="chatOpen = true"
                    class="relative flex h-9 w-9 items-center justify-center rounded-lg text-topbar-text/70 hover:bg-gray-500/10">
                <i class="ik ik-message-square"></i>
                @if($chatUnreadCount > 0)
                <span class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-green-500 px-1 text-[10px] font-semibold text-white">
                    {{ $chatUnreadCount > 99 ? '99+' : $chatUnreadCount }}
                </span>
                @endif
            </button>

            <!-- Dark mode toggle -->
            <button type="button" title="{{ __('Toggle dark mode') }}"
                    x-data="{ dark: document.documentElement.classList.contains('dark') }"
                    @click="dark = ! dark; document.documentElement.classList.toggle('dark', dark); localStorage.setItem('radmin-dark', dark ? '1' : '0')"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-topbar-text/70 hover:bg-gray-500/10">
                <i :class="dark ? 'ik ik-sun' : 'ik ik-moon'"></i>
            </button>

            <!-- User -->
            <x-dropdown width="w-48">
                <x-slot:trigger>
                    <button class="ml-1 flex items-center">
                        <img class="h-9 w-9 rounded-full object-cover ring-2 ring-gray-500/15" 
                             src="{{ Auth::user()->avatar ?? asset('img/user.jpg') }}" 
                             alt="{{ Auth::user()->full_name ?? Auth::user()->username }}">
                    </button>
                </x-slot:trigger>

                <a href="{{ route('profile.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    <i class="ik ik-user text-gray-400"></i> {{ __('Profile') }}
                </a>
                
                <a href="{{ route('email.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    <i class="ik ik-mail text-gray-400"></i> {{ __('Webmail') }}
                </a>
                
                <a href="{{ route('luna.index') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    <i class="fas fa-brain text-purple-500"></i> {{ __('LUNA AI') }}
                </a>
                
                <div class="my-1 border-t border-gray-100"></div>
                <a href="{{ route('clear.cache') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50" title="{{ __('Flush application & view cache') }}">
                    <i class="ik ik-refresh-cw text-gray-400"></i> {{ __('Clear cache') }}
                </a>
                <div class="my-1 border-t border-gray-100"></div>
                <a href="{{ route('logout') }}" 
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();" 
                   class="flex items-center gap-2 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    <i class="ik ik-power text-gray-400"></i> {{ __('Logout') }}
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                    @csrf
                </form>
            </x-dropdown>
        </div>
    </div>
</header>