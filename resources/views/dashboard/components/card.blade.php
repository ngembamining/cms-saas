@props(['noPadding' => false, 'hover' => false])

<div class="bg-white rounded-xl shadow-sm {{ $hover ? 'hover:shadow-md transition' : '' }} {{ $noPadding ? '' : 'p-5' }}">
    @if(isset($header))
        <div class="{{ $noPadding ? 'px-5 py-3 border-b border-gray-100' : 'mb-4' }}">
            {{ $header }}
        </div>
    @endif
    
    <div class="{{ $noPadding ? '' : '' }}">
        {{ $slot }}
    </div>
    
    @if(isset($footer))
        <div class="{{ $noPadding ? 'px-5 py-3 border-t border-gray-100' : 'mt-4' }}">
            {{ $footer }}
        </div>
    @endif
</div>