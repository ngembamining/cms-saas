@extends('layouts.main')
@section('title', 'Notifications')
@section('content')

<div class="max-w-7xl mx-auto">
    <x-page-header title="{{ __('Notifications') }}"
                   subtitle="{{ __('View all your notifications') }}"
                   icon="ik ik-bell"
                   :breadcrumbs="['Home' => route('dashboard'), 'Communication' => null, 'Notifications' => null]" />

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif
    @if(session('warning'))
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            {{ session('warning') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <!-- Unread Count -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-sm text-gray-600">
                {{ __('You have') }}
                <strong class="text-primary-600">{{ $unreadCount ?? 0 }}</strong>
                {{ __('unread notification(s)') }}
            </span>
        </div>
        <div class="flex items-center gap-3">
            @if(($unreadCount ?? 0) > 0)
                <form action="{{ route('communication.notifications.mark-all-read') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="text-sm text-primary-600 hover:text-primary-700">
                        <i class="ik ik-check-circle mr-1"></i> {{ __('Mark all as read') }}
                    </button>
                </form>
            @endif

            @if(in_array(auth()->user()->role, ['director','branch_manager','accountant'], true))
                <a href="{{ route('communication.notifications.broadcast') }}"
                   class="text-sm text-gray-600 hover:text-gray-800">
                    <i class="ik ik-send mr-1"></i> {{ __('Broadcast') }}
                </a>
            @endif
        </div>
    </div>

    <!-- Notifications List -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="divide-y divide-gray-100">
            @forelse($notifications ?? [] as $notification)
                @php $isRead = (bool) $notification->is_read; @endphp
                <div class="px-6 py-4 hover:bg-gray-50 transition {{ $isRead ? '' : 'bg-primary-50 border-l-4 border-primary-500' }}">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 mt-1">
                            <span class="w-2 h-2 rounded-full inline-block {{ $isRead ? 'bg-gray-300' : 'bg-primary-500' }}"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-800">{{ $notification->message }}</p>
                            <div class="flex items-center gap-3 mt-1">
                                <span class="text-xs text-gray-400"
                                      title="{{ \Carbon\Carbon::parse($notification->created_at)->toDayDateTimeString() }}">
                                    {{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}
                                </span>
                                @if(!empty($notification->type))
                                    <span class="text-xs px-2 py-0.5 bg-gray-100 rounded-full text-gray-500">
                                        {{ ucfirst((string) $notification->type) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="flex-shrink-0 flex items-center gap-2">
                            @if(!$isRead)
                                <button onclick="markAsRead({{ $notification->id }})"
                                        class="text-xs text-primary-600 hover:text-primary-700">
                                    {{ __('Mark read') }}
                                </button>
                            @endif
                            <button onclick="deleteNotification({{ $notification->id }})"
                                    class="text-xs text-gray-400 hover:text-red-600">
                                <i class="ik ik-x"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-20 text-center text-gray-400">
                    <i class="ik ik-bell-off text-4xl block mb-4"></i>
                    {{ __('No notifications found') }}
                </div>
            @endforelse
        </div>

        @if(isset($notifications) && $notifications instanceof \Illuminate\Pagination\LengthAwarePaginator && $notifications->hasPages())
            <div class="px-6 py-3 border-t border-gray-100">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Route URLs injected as JS templates (id substituted at runtime) --}}
<script>
    const URLS = {
        markRead: "{{ route('communication.notifications.mark-read', ['id' => '__ID__']) }}",
        destroy:  "{{ route('communication.notifications.destroy', ['id' => '__ID__']) }}",
        csrf:     "{{ csrf_token() }}",
    };

    async function apiCall(url, options = {}) {
        const res = await fetch(url, {
            ...options,
            headers: {
                'X-CSRF-TOKEN': URLS.csrf,
                'Accept': 'application/json',
                ...(options.headers || {}),
            },
        });

        let data = {};
        const contentType = res.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            data = await res.json();
        } else {
            // likely a CSRF/HTML error page
            data = { success: false, message: `HTTP ${res.status}` };
        }

        if (!res.ok) {
            if (res.status === 419) {
                throw new Error('Session expired. Please refresh the page and try again.');
            }
            throw new Error(data.message || `Request failed (${res.status})`);
        }
        return data;
    }

    async function markAsRead(id) {
        try {
            const data = await apiCall(URLS.markRead.replace('__ID__', id), { method: 'POST' });
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to mark notification as read');
            }
        } catch (err) {
            alert(err.message);
        }
    }

    async function deleteNotification(id) {
        if (!confirm('Delete this notification?')) return;

        try {
            const data = await apiCall(URLS.destroy.replace('__ID__', id), { method: 'DELETE' });
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to delete notification');
            }
        } catch (err) {
            alert(err.message);
        }
    }
</script>

@endsection