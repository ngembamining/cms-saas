@extends('layouts.main')
@section('title', 'Payroll Report')
@section('content')

<div class="max-w-7xl mx-auto">

    <x-page-header title="{{ __('Payroll Report') }}"
                   subtitle="{{ __('Salary and deduction analytics') }}"
                   icon="ik ik-credit-card"
                   :breadcrumbs="['Home' => route('dashboard'), 'HR' => route('hr.index'), 'Reports' => route('hr.reports'), 'Payroll' => null]" />

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
        <form action="{{ route('hr.reports.payroll') }}" method="GET" class="flex flex-wrap items-end gap-4">
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
            <a href="{{ route('hr.reports.export', ['type' => 'payroll', 'from' => $from, 'to' => $to]) }}"
               class="ml-auto px-4 py-2 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition">
                <i class="ik ik-download mr-1"></i>{{ __('Export CSV') }}
            </a>
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-gray-400">
            <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Entries') }}</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($summary->total_entries ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-green-400">
            <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Gross (Base + Allow. + Bonus)') }}</p>
            <p class="text-xl font-bold text-green-600 mt-1">
                {{ number_format(($summary->total_base ?? 0) + ($summary->total_allowances ?? 0) + ($summary->total_bonus ?? 0) + ($summary->total_overtime ?? 0), 0) }}
            </p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-red-400">
            <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Deductions + Tax') }}</p>
            <p class="text-xl font-bold text-red-500 mt-1">
                {{ number_format(($summary->total_deductions ?? 0) + ($summary->total_tax ?? 0), 0) }}
            </p>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-primary-400">
            <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Net Payout') }}</p>
            <p class="text-xl font-bold text-primary-600 mt-1">
                {{ number_format($summary->total_net ?? 0, 0) }}
            </p>
            <p class="text-[10px] text-gray-400 uppercase mt-0.5">TZS</p>
        </div>
    </div>

    {{-- Records --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-700">{{ __('Payroll Entries') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-4 py-3 font-medium">{{ __('Employee') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Period') }}</th>
                        <th class="px-4 py-3 font-medium text-right">{{ __('Base') }}</th>
                        <th class="px-4 py-3 font-medium text-right">{{ __('Allow.') }}</th>
                        <th class="px-4 py-3 font-medium text-right">{{ __('Bonus') }}</th>
                        <th class="px-4 py-3 font-medium text-right">{{ __('Overtime') }}</th>
                        <th class="px-4 py-3 font-medium text-right">{{ __('Deduct.') }}</th>
                        <th class="px-4 py-3 font-medium text-right">{{ __('Tax') }}</th>
                        <th class="px-4 py-3 font-medium text-right">{{ __('Net') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($records as $r)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-700">{{ $r->employee->full_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600 text-xs whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($r->period_start)->format('d M') }} → {{ \Carbon\Carbon::parse($r->period_end)->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3 text-right">{{ number_format($r->base_salary, 0) }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($r->allowances, 0) }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($r->bonus, 0) }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($r->overtime_pay, 0) }}</td>
                            <td class="px-4 py-3 text-right text-red-500">-{{ number_format($r->deductions, 0) }}</td>
                            <td class="px-4 py-3 text-right text-red-500">-{{ number_format($r->tax, 0) }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ number_format($r->net_pay, 0) }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $badge = match($r->status) {
                                        'paid'      => 'bg-green-100 text-green-700',
                                        'processed' => 'bg-blue-100 text-blue-700',
                                        default     => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <span class="px-2 py-1 rounded text-xs uppercase {{ $badge }}">{{ $r->status }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-6 py-8 text-center text-gray-400">{{ __('No payroll data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t">{{ $records->links() }}</div>
    </div>
</div>

@endsection