@extends('layouts.main')
@section('title', 'Announcements')
@section('content')

<div class="max-w-7xl mx-auto">

    <x-page-header title="{{ __('Announcements') }}"
                   subtitle="{{ __('Company-wide HR announcements and updates') }}"
                   icon="ik ik-megaphone"
                   :breadcrumbs="['Home' => route('dashboard'), 'HR' => route('hr.index'), 'Announcements' => null]" />

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
            <i class="ik ik-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    <div class="flex justify-end mb-4">
        <button type="button" onclick="document.getElementById('announceModal').classList.remove('hidden')"
                class="px-4 py-2 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition">
            <i class="ik ik-plus mr-1"></i>{{ __('Publish Announcement') }}
        </button>
    </div>

    @if($announcements->count())
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($announcements as $a)
                @php
                    $typeMeta = [
                        'general'  => ['icon' => 'ik-message-circle', 'color' => 'text-blue-500 bg-blue-50', 'label' => 'General'],
                        'policy'   => ['icon' => 'ik-file-text',      'color' => 'text-indigo-500 bg-indigo-50', 'label' => 'Policy'],
                        'event'    => ['icon' => 'ik-calendar',       'color' => 'text-purple-500 bg-purple-50', 'label' => 'Event'],
                        'training' => ['icon' => 'ik-book-open',      'color' => 'text-amber-500 bg-amber-50', 'label' => 'Training'],
                        'holiday'  => ['icon' => 'ik-sun',            'color' => 'text-rose-500 bg-rose-50', 'label' => 'Holiday'],
                    ];
                    $meta = $typeMeta[$a->type] ?? $typeMeta['general'];

                    $priorityMeta = [
                        'low'    => ['class' => 'bg-gray-100 text-gray-600',  'dot' => 'bg-gray-400'],
                        'medium' => ['class' => 'bg-blue-100 text-blue-600',  'dot' => 'bg-blue-400'],
                        'high'   => ['class' => 'bg-red-100 text-red-600',    'dot' => 'bg-red-500'],
                    ];
                    $pm = $priorityMeta[$a->priority] ?? $priorityMeta['medium'];
                @endphp

                <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition flex flex-col">
                    <div class="h-1 {{ $a->priority === 'high' ? 'bg-red-500' : ($a->priority === 'low' ? 'bg-gray-300' : 'bg-blue-400') }}"></div>

                    <div class="p-5 flex-1">
                        <div class="flex items-center justify-between mb-3">
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium {{ $meta['color'] }}">
                                <i class="ik {{ $meta['icon'] }}"></i>
                                {{ $meta['label'] }}
                            </span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium uppercase {{ $pm['class'] }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $pm['dot'] }}"></span>
                                {{ $a->priority }}
                            </span>
                        </div>

                        <h3 class="font-semibold text-gray-800 text-base mb-2 line-clamp-2">
                            {{ $a->title }}
                        </h3>

                        <p class="text-sm text-gray-600 line-clamp-4">
                            {{ Str::limit(strip_tags($a->content), 180) }}
                        </p>

                        @if($a->start_date)
                            <div class="mt-3 text-xs text-gray-400 flex items-center">
                                <i class="ik ik-calendar mr-1"></i>
                                {{ \Carbon\Carbon::parse($a->start_date)->format('d M Y') }}
                                @if($a->end_date)
                                    <span class="mx-1">→</span>
                                    {{ \Carbon\Carbon::parse($a->end_date)->format('d M Y') }}
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="px-5 py-3 border-t border-gray-100 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2 text-gray-400">
                            <i class="ik ik-user"></i>
                            <span>{{ $a->creator->full_name ?? 'System' }}</span>
                            <span>·</span>
                            <span>{{ \Carbon\Carbon::parse($a->created_at)->diffForHumans() }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            @if(!$a->is_published)
                                <span class="px-2 py-0.5 bg-gray-100 text-gray-500 rounded text-[10px] uppercase">
                                    {{ __('Draft') }}
                                </span>
                            @endif
                            <a href="{{ route('hr.announcements.edit', $a->id) }}"
                               class="text-blue-500 hover:text-blue-700 p-1" title="{{ __('Edit') }}">
                                <i class="ik ik-edit"></i>
                            </a>
                            <form action="{{ route('hr.announcements.destroy', $a->id) }}" method="POST"
                                  onsubmit="return confirm('{{ __('Delete this announcement?') }}')" class="inline">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-500 hover:text-red-700 p-1" title="{{ __('Delete') }}">
                                    <i class="ik ik-trash-2"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $announcements->links() }}
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm p-12 text-center text-gray-400">
            <i class="ik ik-megaphone text-5xl block mb-3"></i>
            <p class="text-lg font-medium">{{ __('No announcements yet') }}</p>
            <p class="text-sm mt-1">{{ __('Publish your first announcement to share company updates.') }}</p>
        </div>
    @endif
</div>

{{-- Publish Modal --}}
<div id="announceModal" class="hidden fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <form action="{{ route('hr.announcements.store') }}" method="POST">
            @csrf
            <div class="px-6 py-4 border-b flex items-center justify-between sticky top-0 bg-white">
                <h3 class="text-lg font-semibold">{{ __('Publish Announcement') }}</h3>
                <button type="button" onclick="document.getElementById('announceModal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600">
                    <i class="ik ik-x"></i>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Title') }} *</label>
                    <input type="text" name="title" required maxlength="255" value="{{ old('title') }}"
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Content') }} *</label>
                    <textarea name="content" rows="5" required
                              class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">{{ old('content') }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Type') }} *</label>
                        <select name="type" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                            <option value="general">General</option>
                            <option value="policy">Policy</option>
                            <option value="event">Event</option>
                            <option value="training">Training</option>
                            <option value="holiday">Holiday</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Priority') }} *</label>
                        <select name="priority" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Start Date') }}</label>
                        <input type="date" name="start_date" value="{{ old('start_date') }}"
                               class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('End Date') }}</label>
                        <input type="date" name="end_date" value="{{ old('end_date') }}"
                               class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    </div>
                </div>

                <div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="is_published" value="1" checked
                               class="rounded border-gray-300 text-primary-500 focus:ring-primary-400">
                        {{ __('Publish immediately') }}
                    </label>
                </div>
            </div>

            <div class="px-6 py-4 border-t flex justify-end gap-2 sticky bottom-0 bg-white">
                <button type="button" onclick="document.getElementById('announceModal').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition">
                    <i class="ik ik-send mr-1"></i>{{ __('Publish') }}
                </button>
            </div>
        </form>
    </div>
</div>

@endsection