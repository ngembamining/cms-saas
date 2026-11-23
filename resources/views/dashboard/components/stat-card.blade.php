@props(['icon', 'title', 'value', 'change' => null, 'changeType' => 'increase'])

<div class="stat-card bg-white rounded-xl shadow-sm p-5 hover:shadow-md transition">
    <div class="flex items-center justify-between">
        <div class="flex-1">
            <p class="text-sm text-gray-500 font-medium">{{ $title }}</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ $value }}</p>
            @if($change)
                <p class="text-xs {{ $changeType === 'increase' ? 'text-green-600' : 'text-red-600' }} mt-1 flex items-center">
                    <i class="ik ik-arrow-{{ $changeType === 'increase' ? 'up' : 'down' }} text-xs"></i>
                    <span>{{ $change }}</span>
                    <span class="text-gray-400 ml-1">{{ __('from last period') }}</span>
                </p>
            @endif
        </div>
        <div class="w-12 h-12 bg-primary-50 rounded-full flex items-center justify-center text-primary-500 flex-shrink-0">
            <i class="{{ $icon }} text-xl"></i>
        </div>
    </div>
</div>