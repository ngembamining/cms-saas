@extends('layouts.main')
@section('title', 'Attendance')
@section('content')

<div class="max-w-7xl mx-auto">

    {{-- Page Header --}}
    <x-page-header title="{{ __('Attendance') }}"
                   subtitle="{{ __('Track daily employee attendance') }}"
                   icon="ik ik-calendar"
                   :breadcrumbs="['Home' => route('dashboard'), 'HR' => route('hr.index'), 'Attendance' => null]" />

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
            <i class="ik ik-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
            <i class="ik ik-alert-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    {{-- Quick Check-in / Check-out Bar --}}
    <div class="bg-gradient-to-r from-primary-500 to-primary-600 rounded-xl shadow-sm p-5 mb-6 flex flex-wrap items-center justify-between gap-4 text-white">
        <div>
            <p class="text-xs uppercase tracking-wide opacity-80">{{ __('Today') }} — {{ now()->format('l, d M Y') }}</p>
            <p class="text-lg font-semibold mt-1">{{ __('Hello, :name', ['name' => Auth::user()->full_name ?? Auth::user()->username]) }}</p>
            @php $myAtt = Auth::user()->todayAttendance; @endphp
            <p class="text-sm mt-1 opacity-90">
                @if($myAtt && $myAtt->check_in)
                    <i class="ik ik-log-in mr-1"></i> {{ __('In') }}: {{ \Carbon\Carbon::parse($myAtt->check_in)->format('H:i') }}
                @else
                    <i class="ik ik-minus-circle mr-1"></i> {{ __('Not checked in') }}
                @endif
                @if($myAtt && $myAtt->check_out)
                    &nbsp;&nbsp;<i class="ik ik-log-out mr-1"></i> {{ __('Out') }}: {{ \Carbon\Carbon::parse($myAtt->check_out)->format('H:i') }}
                    &nbsp;&nbsp;<span class="px-2 py-0.5 rounded bg-white/20 text-xs">{{ $myAtt->hours_worked }}h</span>
                @endif
            </p>
        </div>
        <div class="flex gap-2">
            <form action="{{ route('hr.attendance.check-in') }}" method="POST">
                @csrf
                <button class="px-4 py-2 bg-white text-primary-600 rounded-lg font-medium hover:bg-gray-100 transition disabled:opacity-50"
                        {{ $myAtt && $myAtt->check_in ? 'disabled' : '' }}>
                    <i class="ik ik-log-in mr-1"></i> {{ __('Check In') }}
                </button>
            </form>
            <form action="{{ route('hr.attendance.check-out') }}" method="POST">
                @csrf
                <button class="px-4 py-2 bg-white/15 border border-white/30 text-white rounded-lg font-medium hover:bg-white/25 transition disabled:opacity-50"
                        {{ ($myAtt && $myAtt->check_out) || !$myAtt || !$myAtt->check_in ? 'disabled' : '' }}>
                    <i class="ik ik-log-out mr-1"></i> {{ __('Check Out') }}
                </button>
            </form>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-4">
            <p class="text-xs text-gray-500">{{ __('Present') }}</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['present'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4">
            <p class="text-xs text-gray-500">{{ __('Late') }}</p>
            <p class="text-2xl font-bold text-amber-500 mt-1">{{ $stats['late'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4">
            <p class="text-xs text-gray-500">{{ __('Absent') }}</p>
            <p class="text-2xl font-bold text-red-500 mt-1">{{ $stats['absent'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4">
            <p class="text-xs text-gray-500">{{ __('On Leave') }}</p>
            <p class="text-2xl font-bold text-gray-600 mt-1">{{ $stats['on_leave'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4">
            <p class="text-xs text-gray-500">{{ __('Total Hours') }}</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($stats['total_hours'], 1) }}h</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4">
            <p class="text-xs text-gray-500">{{ __('Overtime') }}</p>
            <p class="text-2xl font-bold text-purple-600 mt-1">{{ number_format($stats['total_overtime'], 1) }}h</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
        <form action="{{ route('hr.attendance') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Date') }}</label>
                <input type="date" name="date" value="{{ $date }}"
                       class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Search') }}</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Employee name..."
                       class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Branch') }}</label>
                <select name="branch_id" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    <option value="">{{ __('All Branches') }}</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Status') }}</label>
                <select name="status" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    <option value="">{{ __('All Status') }}</option>
                    @foreach(['present','late','absent','half_day','holiday','leave'] as $s)
                        <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $s)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition">
                <i class="ik ik-filter mr-2"></i>{{ __('Apply') }}
            </button>
            <a href="{{ route('hr.attendance') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="ik ik-rotate-ccw mr-2"></i>{{ __('Reset') }}
            </a>
            <button type="button" onclick="document.getElementById('addModal').classList.remove('hidden')"
                    class="ml-auto px-4 py-2 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition">
                <i class="ik ik-plus mr-1"></i>{{ __('Mark Attendance') }}
            </button>
        </form>
    </div>

    {{-- Attendance Table --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-6 py-3 font-medium">{{ __('Employee') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Branch') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Check In') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Check Out') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Hours') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Overtime') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Notes') }}</th>
                        <th class="px-6 py-3 font-medium text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($attendance as $record)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $record->employee->avatar ?: asset('img/default-avatar.png') }}"
                                         class="w-8 h-8 rounded-full object-cover" alt="">
                                    <span class="font-medium text-gray-700">
                                        {{ $record->employee->full_name ?? $record->employee->username ?? '—' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-gray-600">{{ $record->employee->branch->name ?? 'N/A' }}</td>
                            <td class="px-6 py-3 text-gray-700">
                                {{ $record->check_in ? \Carbon\Carbon::parse($record->check_in)->format('H:i') : '—' }}
                            </td>
                            <td class="px-6 py-3 text-gray-700">
                                {{ $record->check_out ? \Carbon\Carbon::parse($record->check_out)->format('H:i') : '—' }}
                            </td>
                            <td class="px-6 py-3 text-gray-700">{{ number_format($record->hours_worked, 2) }}</td>
                            <td class="px-6 py-3 text-purple-600">{{ number_format($record->overtime_hours, 2) }}</td>
                            <td class="px-6 py-3">
                                <span class="px-2 py-1 rounded text-xs {{ $record->status_badge }}">
                                    {{ ucfirst(str_replace('_', ' ', $record->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-gray-500 text-xs max-w-xs truncate" title="{{ $record->notes }}">
                                {{ $record->notes ?: '—' }}
                            </td>
                            <td class="px-6 py-3 text-right">
                                <form action="{{ route('hr.attendance.destroy', $record->id) }}" method="POST"
                                      onsubmit="return confirm('{{ __('Delete this record?') }}')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-500 hover:text-red-600" title="{{ __('Delete') }}">
                                        <i class="ik ik-trash-2"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-gray-400">
                                <i class="ik ik-calendar text-3xl block mb-2"></i>
                                {{ __('No attendance records for this date') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-gray-100">
            {{ $attendance->links() }}
        </div>
    </div>
</div>

{{-- Add / Mark Attendance Modal --}}
<div id="addModal" class="hidden fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg">
        <form action="{{ route('hr.attendance.store') }}" method="POST">
            @csrf
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h3 class="text-lg font-semibold">{{ __('Mark Attendance') }}</h3>
                <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600">
                    <i class="ik ik-x"></i>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Employee') }} *</label>
                    <select name="employee_id" required
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        <option value="">{{ __('Select employee...') }}</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">
                                {{ $emp->full_name ?? $emp->username }}
                                {{ isset($markedIds) && in_array($emp->id, $markedIds) ? ' (already marked)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Date') }} *</label>
                        <input type="date" name="date" value="{{ $date }}" required
                               class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Status') }} *</label>
                        <select name="status" required
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                            <option value="present">{{ __('Present') }}</option>
                            <option value="late">{{ __('Late') }}</option>
                            <option value="absent">{{ __('Absent') }}</option>
                            <option value="half_day">{{ __('Half Day') }}</option>
                            <option value="holiday">{{ __('Holiday') }}</option>
                            <option value="leave">{{ __('Leave') }}</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Check In') }}</label>
                        <input type="time" name="check_in"
                               class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Check Out') }}</label>
                        <input type="time" name="check_out"
                               class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Overtime (h)') }}</label>
                        <input type="number" name="overtime_hours" step="0.25" min="0" max="24" value="0"
                               class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Notes') }}</label>
                    <textarea name="notes" rows="2" maxlength="500"
                              class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                              placeholder="{{ __('Optional notes...') }}"></textarea>
                </div>
            </div>

            <div class="px-6 py-4 border-t flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition">
                    <i class="ik ik-save mr-1"></i>{{ __('Save') }}
                </button>
            </div>
        </form>
    </div>
</div>

@endsection