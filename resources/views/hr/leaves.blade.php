@extends('layouts.main')
@section('title', 'Leave Management')
@section('content')

<div class="max-w-7xl mx-auto">

    {{-- Page Header --}}
    <x-page-header title="{{ __('Leave Management') }}"
                   subtitle="{{ __('Manage employee leave requests and approvals') }}"
                   icon="ik ik-calendar"
                   :breadcrumbs="['Home' => route('dashboard'), 'HR' => route('hr.index'), 'Leaves' => null]" />

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

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
            <p class="font-medium mb-1"><i class="ik ik-alert-triangle mr-2"></i>{{ __('Please fix the following errors:') }}</p>
            <ul class="list-disc list-inside text-xs">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-amber-400">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Pending') }}</p>
                    <p class="text-2xl font-bold text-amber-600 mt-1">{{ $stats['pending'] }}</p>
                </div>
                <i class="ik ik-clock text-3xl text-amber-200"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-green-400">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Approved') }}</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['approved'] }}</p>
                </div>
                <i class="ik ik-check-circle text-3xl text-green-200"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-red-400">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Rejected') }}</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">{{ $stats['rejected'] }}</p>
                </div>
                <i class="ik ik-x-circle text-3xl text-red-200"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-blue-400">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('On Leave Today') }}</p>
                    <p class="text-2xl font-bold text-blue-600 mt-1">{{ $stats['on_leave_today'] }}</p>
                </div>
                <i class="ik ik-users text-3xl text-blue-200"></i>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
        <form action="{{ route('hr.leaves') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Search') }}</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Employee name..."
                       class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Status') }}</label>
                <select name="status" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    <option value="">{{ __('All Status') }}</option>
                    @foreach(['pending','approved','rejected','cancelled'] as $s)
                        <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>
                            {{ ucfirst($s) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Leave Type') }}</label>
                <select name="type" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    <option value="">{{ __('All Types') }}</option>
                    @foreach(['annual','sick','maternity','paternity','emergency','unpaid','other'] as $t)
                        <option value="{{ $t }}" {{ request('type') == $t ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $t)) }}
                        </option>
                    @endforeach
                </select>
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
            <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition">
                <i class="ik ik-search mr-2"></i> {{ __('Apply') }}
            </button>
            <a href="{{ route('hr.leaves') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="ik ik-rotate-ccw mr-2"></i> {{ __('Reset') }}
            </a>
            <button type="button" onclick="document.getElementById('leaveModal').classList.remove('hidden')"
                    class="ml-auto px-4 py-2 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition">
                <i class="ik ik-plus mr-1"></i>{{ __('New Leave Request') }}
            </button>
        </form>
    </div>

    {{-- Leaves Table --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-6 py-3 font-medium">{{ __('Employee') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Type') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Duration') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Days') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Reason') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Requested') }}</th>
                        <th class="px-6 py-3 font-medium text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($leaves as $leave)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $leave->employee->avatar ?? asset('img/default-avatar.png') }}"
                                         onerror="this.src='{{ asset('img/default-avatar.png') }}'"
                                         class="w-8 h-8 rounded-full object-cover" alt="">
                                    <div>
                                        <span class="font-medium text-gray-700 block">
                                            {{ $leave->employee->full_name ?? $leave->employee->username ?? '—' }}
                                        </span>
                                        <span class="text-xs text-gray-400">
                                            {{ $leave->employee->branch->name ?? '' }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3">
                                @php
                                    $typeColors = [
                                        'annual'    => 'bg-blue-100 text-blue-700',
                                        'sick'      => 'bg-red-100 text-red-700',
                                        'maternity' => 'bg-pink-100 text-pink-700',
                                        'paternity' => 'bg-indigo-100 text-indigo-700',
                                        'emergency' => 'bg-orange-100 text-orange-700',
                                        'unpaid'    => 'bg-gray-100 text-gray-700',
                                        'other'     => 'bg-purple-100 text-purple-700',
                                    ];
                                @endphp
                                <span class="px-2 py-1 rounded text-xs {{ $typeColors[$leave->leave_type] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ ucfirst(str_replace('_', ' ', $leave->leave_type)) }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-gray-600 text-xs">
                                {{ \Carbon\Carbon::parse($leave->start_date)->format('d M Y') }}
                                <i class="ik ik-arrow-right mx-1 text-gray-300"></i>
                                {{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}
                            </td>
                            <td class="px-6 py-3 text-gray-700 font-medium">{{ $leave->days }}</td>
                            <td class="px-6 py-3 text-gray-500 text-xs max-w-xs truncate" title="{{ $leave->reason }}">
                                {{ $leave->reason ?: '—' }}
                            </td>
                            <td class="px-6 py-3">
                                @php
                                    $statusColors = [
                                        'pending'   => 'bg-amber-100 text-amber-700',
                                        'approved'  => 'bg-green-100 text-green-700',
                                        'rejected'  => 'bg-red-100 text-red-700',
                                        'cancelled' => 'bg-gray-100 text-gray-600',
                                    ];
                                @endphp
                                <span class="px-2 py-1 rounded text-xs {{ $statusColors[$leave->status] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ ucfirst($leave->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-gray-500 text-xs">
                                {{ \Carbon\Carbon::parse($leave->created_at)->format('d M Y') }}
                            </td>
                            <td class="px-6 py-3 text-right">
                                @if($leave->status === 'pending')
                                    <div class="flex items-center justify-end gap-2">
                                        <form action="{{ route('hr.leaves.approve', $leave->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button class="text-green-500 hover:text-green-700" title="{{ __('Approve') }}">
                                                <i class="ik ik-check"></i>
                                            </button>
                                        </form>
                                        <button type="button"
                                                onclick="openRejectModal({{ $leave->id }})"
                                                class="text-amber-500 hover:text-amber-700" title="{{ __('Reject') }}">
                                            <i class="ik ik-x"></i>
                                        </button>
                                        <form action="{{ route('hr.leaves.destroy', $leave->id) }}" method="POST"
                                              onsubmit="return confirm('{{ __('Delete this leave request?') }}')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-red-500 hover:text-red-700" title="{{ __('Delete') }}">
                                                <i class="ik ik-trash-2"></i>
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <form action="{{ route('hr.leaves.destroy', $leave->id) }}" method="POST"
                                          onsubmit="return confirm('{{ __('Delete this record?') }}')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-500 hover:text-red-700" title="{{ __('Delete') }}">
                                            <i class="ik ik-trash-2"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-gray-400">
                                <i class="ik ik-calendar text-3xl block mb-2"></i>
                                {{ __('No leave requests found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-gray-100">
            {{ $leaves->links() }}
        </div>
    </div>
</div>

{{-- New Leave Request Modal --}}
<div id="leaveModal" class="hidden fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <form action="{{ route('hr.leaves.store') }}" method="POST">
            @csrf
            <div class="px-6 py-4 border-b flex items-center justify-between sticky top-0 bg-white">
                <h3 class="text-lg font-semibold">{{ __('New Leave Request') }}</h3>
                <button type="button" onclick="document.getElementById('leaveModal').classList.add('hidden')"
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
                            <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                                {{ $emp->full_name ?? $emp->username }}
                                @if($emp->branch) — {{ $emp->branch->name }} @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Leave Type') }} *</label>
                    <select name="leave_type" required
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        <option value="annual">{{ __('Annual Leave') }}</option>
                        <option value="sick">{{ __('Sick Leave') }}</option>
                        <option value="maternity">{{ __('Maternity Leave') }}</option>
                        <option value="paternity">{{ __('Paternity Leave') }}</option>
                        <option value="emergency">{{ __('Emergency Leave') }}</option>
                        <option value="unpaid">{{ __('Unpaid Leave') }}</option>
                        <option value="other">{{ __('Other') }}</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Start Date') }} *</label>
                        <input type="date" name="start_date" value="{{ old('start_date') }}" required
                               class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('End Date') }} *</label>
                        <input type="date" name="end_date" value="{{ old('end_date') }}" required
                               class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Reason') }}</label>
                    <textarea name="reason" rows="3" maxlength="1000"
                              class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                              placeholder="{{ __('Reason for leave request...') }}">{{ old('reason') }}</textarea>
                </div>

                <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 text-xs text-blue-700">
                    <i class="ik ik-info mr-1"></i>
                    {{ __('Leave requests start as "Pending" and must be approved by a manager. Approved leaves will automatically mark attendance as "Leave".') }}
                </div>
            </div>

            <div class="px-6 py-4 border-t flex justify-end gap-2 sticky bottom-0 bg-white">
                <button type="button" onclick="document.getElementById('leaveModal').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition">
                    <i class="ik ik-send mr-1"></i>{{ __('Submit Request') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Reject Modal --}}
<div id="rejectModal" class="hidden fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <form id="rejectForm" method="POST">
            @csrf
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h3 class="text-lg font-semibold text-red-600">
                    <i class="ik ik-x-circle mr-1"></i>{{ __('Reject Leave Request') }}
                </h3>
                <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600">
                    <i class="ik ik-x"></i>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Reason for rejection') }}</label>
                    <textarea name="notes" rows="3" maxlength="500" required
                              class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-red-400 focus:ring-2 focus:ring-red-100"
                              placeholder="{{ __('Explain why this leave is being rejected...') }}"></textarea>
                </div>
            </div>

            <div class="px-6 py-4 border-t flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
                    <i class="ik ik-x mr-1"></i>{{ __('Reject') }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRejectModal(id) {
        const form = document.getElementById('rejectForm');
        form.action = `/hr/leaves/${id}/reject`;
        document.getElementById('rejectModal').classList.remove('hidden');
    }

    // Auto-set end_date to start_date when start_date changes and end_date is empty
    document.querySelector('input[name="start_date"]')?.addEventListener('change', function () {
        const endInput = document.querySelector('input[name="end_date"]');
        if (endInput && !endInput.value) {
            endInput.value = this.value;
        }
    });

    // Close modals on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.getElementById('leaveModal')?.classList.add('hidden');
            document.getElementById('rejectModal')?.classList.add('hidden');
        }
    });

    // Auto-open modal on validation error
    @if($errors->any())
        document.getElementById('leaveModal')?.classList.remove('hidden');
    @endif
</script>

@endsection