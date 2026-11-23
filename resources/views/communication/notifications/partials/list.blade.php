@forelse($notifications ?? [] as $notification)
    <div class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 {{ $notification->is_read ? '' : 'bg-primary-50' }}">
        <div class="flex-shrink-0 mt-1">
            @if(!$notification->is_read)
                <span class="w-2 h-2 bg-primary-500 rounded-full inline-block"></span>
            @endif
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm text-gray-800">{{ $notification->message }}</p>
            <p class="text-xs text-gray-400 mt-1">
                {{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}
            </p>
        </div>
    </div>
@empty
    <div class="px-4 py-8 text-center text-gray-400">
        {{ __('No notifications') }}
    </div>
@endforelse