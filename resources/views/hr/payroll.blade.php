@extends('layouts.main')
@section('title', 'Payroll')
@section('content')

<div class="max-w-7xl mx-auto">

    <x-page-header title="{{ __('Payroll') }}"
                   subtitle="{{ __('Manage employee salaries, allowances, and payments') }}"
                   icon="ik ik-credit-card"
                   :breadcrumbs="['Home' => route('dashboard'), 'HR' => route('hr.index'), 'Payroll' => null]" />

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

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-gray-400">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Drafts') }}</p>
                    <p class="text-2xl font-bold text-gray-700 mt-1">{{ $stats['draft'] }}</p>
                </div>
                <i class="ik ik-edit text-3xl text-gray-200"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-blue-400">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Processed') }}</p>
                    <p class="text-2xl font-bold text-blue-600 mt-1">{{ $stats['processed'] }}</p>
                </div>
                <i class="ik ik-check-square text-3xl text-blue-200"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-green-400">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Paid') }}</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['paid'] }}</p>
                </div>
                <i class="ik ik-check-circle text-3xl text-green-200"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-primary-400">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">{{ __('Net Pay (Month)') }}</p>
                    <p class="text-xl font-bold text-primary-600 mt-1">
                        {{ number_format($stats['total_net_pay'], 0) }}
                    </p>
                    <p class="text-[10px] text-gray-400 uppercase">TZS</p>
                </div>
                <i class="ik ik-dollar-sign text-3xl text-primary-200"></i>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
        <form action="{{ route('hr.payroll') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Status') }}</label>
                <select name="status" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    <option value="">{{ __('All Status') }}</option>
                    @foreach(['draft','processed','paid'] as $s)
                        <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Branch') }}</label>
                <select name="branch_id" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    <option value="">{{ __('All Branches') }}</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('From') }}</label>
                <input type="date" name="period_start" value="{{ request('period_start') }}"
                       class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('To') }}</label>
                <input type="date" name="period_end" value="{{ request('period_end') }}"
                       class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm">
            </div>
            <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition">
                <i class="ik ik-filter mr-2"></i> {{ __('Apply') }}
            </button>
            <a href="{{ route('hr.payroll') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="ik ik-rotate-ccw mr-2"></i> {{ __('Reset') }}
            </a>
            <a href="{{ route('hr.payroll.create') }}"
               class="ml-auto px-4 py-2 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition">
                <i class="ik ik-plus mr-1"></i>{{ __('Generate Payroll') }}
            </a>
        </form>
    </div>

    {{-- Payroll Table --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
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
                        <th class="px-4 py-3 font-medium text-right">{{ __('Net Pay') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-4 py-3 font-medium text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($payroll as $row)
                        @php
                            $statusColors = [
                                'draft'     => 'bg-gray-100 text-gray-700',
                                'processed' => 'bg-blue-100 text-blue-700',
                                'paid'      => 'bg-green-100 text-green-700',
                            ];
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <img src="{{ $row->employee->avatar ?? asset('img/default-avatar.png') }}"
                                         onerror="this.src='{{ asset('img/default-avatar.png') }}'"
                                         class="w-7 h-7 rounded-full object-cover" alt="">
                                    <div>
                                        <span class="font-medium text-gray-700 block text-xs">
                                            {{ $row->employee->full_name ?? $row->employee->username ?? '—' }}
                                        </span>
                                        <span class="text-[10px] text-gray-400">
                                            {{ $row->employee->branch->name ?? '' }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($row->period_start)->format('d M') }}
                                <i class="ik ik-arrow-right mx-0.5 text-gray-300"></i>
                                {{ \Carbon\Carbon::parse($row->period_end)->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-600">{{ number_format($row->base_salary, 0) }}</td>
                            <td class="px-4 py-3 text-right text-gray-600">{{ number_format($row->allowances, 0) }}</td>
                            <td class="px-4 py-3 text-right text-gray-600">{{ number_format($row->bonus, 0) }}</td>
                            <td class="px-4 py-3 text-right text-gray-600">{{ number_format($row->overtime_pay, 0) }}</td>
                            <td class="px-4 py-3 text-right text-red-500">-{{ number_format($row->deductions, 0) }}</td>
                            <td class="px-4 py-3 text-right text-red-500">-{{ number_format($row->tax, 0) }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ number_format($row->net_pay, 0) }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded text-[10px] uppercase font-medium {{ $statusColors[$row->status] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $row->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('hr.payroll.show', $row->id) }}"
                                       class="text-blue-500 hover:text-blue-700 p-1" title="{{ __('View') }}">
                                        <i class="ik ik-eye"></i>
                                    </a>

                                    @if($row->status === 'draft')
                                        <form action="{{ route('hr.payroll.process', $row->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button class="text-indigo-500 hover:text-indigo-700 p-1" title="{{ __('Process') }}">
                                                <i class="ik ik-check-square"></i>
                                            </button>
                                        </form>
                                    @endif

                                    @if($row->status === 'processed')
                                        <form action="{{ route('hr.payroll.pay', $row->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button class="text-green-500 hover:text-green-700 p-1" title="{{ __('Mark as Paid') }}">
                                                <i class="ik ik-check-circle"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <form action="{{ route('hr.payroll.destroy', $row->id) }}" method="POST"
                                          onsubmit="return confirm('{{ __('Delete this payroll entry?') }}')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-500 hover:text-red-700 p-1" title="{{ __('Delete') }}">
                                            <i class="ik ik-trash-2"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-6 py-10 text-center text-gray-400">
                                <i class="ik ik-credit-card text-3xl block mb-2"></i>
                                {{ __('No payroll records found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-gray-100">
            {{ $payroll->links() }}
        </div>
    </div>
</div>

@endsection