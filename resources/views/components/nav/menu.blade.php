@props(['items' => []])

@php
    $user = Auth::user();
    $currentRoute = Route::currentRouteName();
@endphp

@if(!empty($items))
    <ul class="space-y-1 px-3">
        @foreach($items as $item)
            @php
                // Check if user has permission for this item
                $hasPermission = true;
                if (isset($item['permission']) && $item['permission'] !== null) {
                    if (is_array($item['permission'])) {
                        $hasPermission = in_array($user->role, $item['permission']);
                    } else {
                        $hasPermission = $user->role === $item['permission'];
                    }
                }
                
                // Check if this item is active
                $isActive = false;
                if (isset($item['active'])) {
                    $activePatterns = (array) $item['active'];
                    foreach ($activePatterns as $pattern) {
                        if (str_contains($pattern, '*')) {
                            $pattern = str_replace('*', '', $pattern);
                            if (str_starts_with($currentRoute, $pattern)) {
                                $isActive = true;
                                break;
                            }
                        } elseif ($currentRoute === $pattern) {
                            $isActive = true;
                            break;
                        }
                    }
                }
                
                // Check if any child is active
                $hasActiveChild = false;
                if (isset($item['children'])) {
                    foreach ($item['children'] as $child) {
                        if (isset($child['active'])) {
                            $childPatterns = (array) $child['active'];
                            foreach ($childPatterns as $pattern) {
                                if (str_contains($pattern, '*')) {
                                    $pattern = str_replace('*', '', $pattern);
                                    if (str_starts_with($currentRoute, $pattern)) {
                                        $hasActiveChild = true;
                                        break 2;
                                    }
                                } elseif ($currentRoute === $pattern) {
                                    $hasActiveChild = true;
                                    break 2;
                                }
                            }
                        }
                    }
                }
            @endphp

            @if($hasPermission)
                <li>
                    @if(isset($item['children']) && !empty($item['children']))
                        {{-- Parent with children --}}
                        <div x-data="{ open: {{ $isActive || $hasActiveChild ? 'true' : 'false' }} }" class="nav-group">
                            <button @click="open = !open" 
                                    class="flex w-full items-center rounded-lg px-3 py-2.5 text-sm transition-all duration-200 hover:bg-sidebar-hover {{ ($isActive || $hasActiveChild) ? 'bg-sidebar-active text-white' : 'text-sidebar-text' }}">
                                @if(isset($item['icon']))
                                    <i class="{{ $item['icon'] }} mr-3 h-5 w-5 shrink-0"></i>
                                @endif
                                <span class="flex-1 text-left font-medium">{{ __($item['label']) }}</span>
                                <i class="ik ik-chevron-right transition-transform duration-200" :class="open ? 'rotate-90' : ''"></i>
                            </button>
                            
                            <div x-show="open" x-collapse>
                                <ul class="mt-1 space-y-1 border-l-2 border-sidebar-hover/30 pl-4">
                                    @foreach($item['children'] as $child)
                                        @php
                                            // Check child permission
                                            $hasChildPermission = true;
                                            if (isset($child['permission']) && $child['permission'] !== null) {
                                                if (is_array($child['permission'])) {
                                                    $hasChildPermission = in_array($user->role, $child['permission']);
                                                } else {
                                                    $hasChildPermission = $user->role === $child['permission'];
                                                }
                                            }
                                            
                                            // Check if child is active
                                            $isChildActive = false;
                                            if (isset($child['active'])) {
                                                $childPatterns = (array) $child['active'];
                                                foreach ($childPatterns as $pattern) {
                                                    if (str_contains($pattern, '*')) {
                                                        $pattern = str_replace('*', '', $pattern);
                                                        if (str_starts_with($currentRoute, $pattern)) {
                                                            $isChildActive = true;
                                                            break;
                                                        }
                                                    } elseif ($currentRoute === $pattern) {
                                                        $isChildActive = true;
                                                        break;
                                                    }
                                                }
                                            }
                                        @endphp
                                        
                                        @if($hasChildPermission)
                                            <li>
                                                <a href="{{ isset($child['route']) ? route($child['route']) : '#' }}"
                                                   class="flex items-center rounded-lg px-3 py-2 text-sm transition-all duration-200 hover:bg-sidebar-hover {{ $isChildActive ? 'bg-sidebar-active text-white' : 'text-sidebar-text' }}">
                                                    @if(isset($child['icon']))
                                                        <i class="{{ $child['icon'] }} mr-3 h-4 w-4 shrink-0"></i>
                                                    @endif
                                                    <span>{{ __($child['label']) }}</span>
                                                </a>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @else
                        {{-- Single item --}}
                        <a href="{{ isset($item['route']) ? route($item['route']) : '#' }}"
                           class="flex items-center rounded-lg px-3 py-2.5 text-sm transition-all duration-200 hover:bg-sidebar-hover {{ $isActive ? 'bg-sidebar-active text-white' : 'text-sidebar-text' }}">
                            @if(isset($item['icon']))
                                <i class="{{ $item['icon'] }} mr-3 h-5 w-5 shrink-0"></i>
                            @endif
                            <span class="flex-1 font-medium">{{ __($item['label']) }}</span>
                        </a>
                    @endif
                </li>
            @endif
        @endforeach
    </ul>
@endif