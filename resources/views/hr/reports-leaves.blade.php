@extends('layouts.main')
@section('title', 'Leave Report')
@section('content')

<div class="max-w-7xl mx-auto">

    <x-page-header title="{{ __('Leave Report') }}"
                   subtitle="{{ __('Leave usage and approval analysis') }}"
                   icon="ik ik-sun"
                   :breadcrumbs="['Home' => route('dashboard'), 'HR' => route('hr.index'), 'Reports' => route('hr.reports'), 'Leaves' => null]" />

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
        <form action="{{ route('hr.reports.leaves') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('From') }}</label>
                <input type="date" name="from" value="{{ $from }}" class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('To') }}</label>
                <input type="date" name="to" value="{{ $to }}" class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Branch') }}</label>
                <select name="branch_id" class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    <option value="">{{ __('All Branches') }}</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition">
                <i class="ik ik-filter mr-1"></i>{{ __('Apply') }}
            </button>
            <a href="{{ route('hr.reports.export', ['type' => 'leaves', 'from' => $from, 'to' => $to]) }}"
               class="ml-auto px-4 py-2 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition">
                <i class="ik ik-download mr-1"></i>{{ __('Export CSV') }}
            </a>
        </form>
    </div>

    {{-- Summary --}}
    <div class="grid md:grid-cols-4 gap-4 mb-6">
        @forelse($summary as $s)
            <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-blue-400">
                <p class="text-xs text-gray-500 uppercase tracking-wide">
                    {{ ucfirst(str_replace('_', ' ', $s->leave_type)) }}
                </p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ $s->total_days }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $s->total }} {{ __('request(s)') }}</p>
            </div>
        @empty
            <div class="col-span-4 bg-white rounded-xl shadow-sm p-6 text-center text-gray-400">
                {{ __('No leave data for this period') }}
            </div>
        @endforelse
    </div>

    {{-- Detailed Records --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-700">{{ __('Leave Records') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-6 py-3 font-medium">{{ __('Employee') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Type') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Start') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('End') }}</th>
                        <th class="px-6 py-3 font-medium text-center">{{ __('Days') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($records as $r)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-gray-700">{{ $r->employee->full_name ?? '—' }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ ucfirst(str_replace('_', ' ', $r->leave_type)) }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ \Carbon\Carbon::parse($r->start_date)->format('d M Y') }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ \Carbon\Carbon::parse($r->end_date)->format('d M Y') }}</td>
                            <td class="px-6 py-3 text-center">{{ $r->days }}</td>
                            <td class="px-6 py-3">
                                @php
                                    $badge = match($r->status) {
                                        'approved' => 'bg-green-100 text-green-700',
                                        'pending'  => 'bg-amber-100 text-amber-700',
                                        'rejected' => 'bg-red-100 text-red-700',
                                        default    => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <span class="px-2 py-1 rounded text-xs {{ $badge }}">{{ ucfirst($r->status) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">{{ __('No records') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t">{{ $records->links() }}</div>
    </div>
</div>

@endsection