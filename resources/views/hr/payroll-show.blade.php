@extends('layouts.main')
@section('title', 'Payroll Slip')
@section('content')

<div class="max-w-3xl mx-auto">

    <x-page-header title="{{ __('Payroll Slip') }}"
                   subtitle="{{ __('Payroll details for') }} {{ $payroll->employee->full_name ?? '—' }}"
                   icon="ik ik-file-text"
                   :breadcrumbs="['Home' => route('dashboard'), 'HR' => route('hr.index'), 'Payroll' => route('hr.payroll'), 'Slip' => null]" />

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="bg-gradient-to-r from-primary-500 to-primary-600 text-white p-6">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <p class="text-xs uppercase tracking-wider opacity-80">{{ __('Employee') }}</p>
                    <p class="text-xl font-bold mt-1">{{ $payroll->employee->full_name ?? $payroll->employee->username ?? '—' }}</p>
                    <p class="text-xs opacity-90 mt-1">{{ $payroll->employee->branch->name ?? '' }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs uppercase tracking-wider opacity-80">{{ __('Period') }}</p>
                    <p class="text-sm font-medium mt-1">
                        {{ \Carbon\Carbon::parse($payroll->period_start)->format('d M Y') }}
                        —
                        {{ \Carbon\Carbon::parse($payroll->period_end)->format('d M Y') }}
                    </p>
                    @php
                        $statusColor = match($payroll->status) {
                            'draft' => 'bg-white/20',
                            'processed' => 'bg-blue-300/40',
                            'paid' => 'bg-green-300/40',
                            default => 'bg-white/20',
                        };
                    @endphp
                    <span class="inline-block mt-2 px-3 py-1 rounded-full text-xs font-medium {{ $statusColor }}">
                        {{ strtoupper($payroll->status) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-6">
            {{-- Earnings --}}
            <div>
                <h3 class="text-xs font-semibold text-green-600 uppercase tracking-wider mb-3">
                    <i class="ik ik-trending-up mr-1"></i>{{ __('Earnings') }}
                </h3>
                <div class="space-y-2">
                    @foreach([
                        'Base Salary' => $payroll->base_salary,
                        'Allowances' => $payroll->allowances,
                        'Bonus' => $payroll->bonus,
                        'Overtime Pay' => $payroll->overtime_pay,
                    ] as $label => $value)
                        <div class="flex justify-between text-sm py-1.5 border-b border-dashed border-gray-100">
                            <span class="text-gray-600">{{ __($label) }}</span>
                            <span class="text-gray-800 font-medium">{{ number_format($value, 2) }}</span>
                        </div>
                    @endforeach
                    <div class="flex justify-between text-sm pt-2">
                        <span class="font-semibold text-gray-700">{{ __('Total Earnings') }}</span>
                        <span class="font-semibold text-green-600">
                            {{ number_format($payroll->base_salary + $payroll->allowances + $payroll->bonus + $payroll->overtime_pay, 2) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Deductions --}}
            <div>
                <h3 class="text-xs font-semibold text-red-600 uppercase tracking-wider mb-3">
                    <i class="ik ik-trending-down mr-1"></i>{{ __('Deductions') }}
                </h3>
                <div class="space-y-2">
                    @foreach([
                        'Deductions' => $payroll->deductions,
                        'Tax' => $payroll->tax,
                    ] as $label => $value)
                        <div class="flex justify-between text-sm py-1.5 border-b border-dashed border-gray-100">
                            <span class="text-gray-600">{{ __($label) }}</span>
                            <span class="text-red-500 font-medium">-{{ number_format($value, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Net Pay --}}
            <div class="bg-gradient-to-r from-primary-500 to-primary-600 rounded-xl p-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-wider opacity-80">{{ __('Net Pay') }}</p>
                        <p class="text-3xl font-bold mt-1">{{ number_format($payroll->net_pay, 2) }}</p>
                        <p class="text-xs opacity-75 mt-0.5">TZS</p>
                    </div>
                    <i class="ik ik-wallet text-5xl opacity-30"></i>
                </div>
            </div>

            {{-- Meta --}}
            <div class="grid grid-cols-2 gap-4 text-xs pt-2">
                <div>
                    <p class="text-gray-400 uppercase tracking-wider mb-1">{{ __('Created') }}</p>
                    <p class="text-gray-700">{{ \Carbon\Carbon::parse($payroll->created_at)->format('d M Y H:i') }}</p>
                </div>
                @if($payroll->payment_date)
                    <div>
                        <p class="text-gray-400 uppercase tracking-wider mb-1">{{ __('Payment Date') }}</p>
                        <p class="text-gray-700">{{ \Carbon\Carbon::parse($payroll->payment_date)->format('d M Y') }}</p>
                    </div>
                @endif
                @if($payroll->notes)
                    <div class="col-span-2">
                        <p class="text-gray-400 uppercase tracking-wider mb-1">{{ __('Notes') }}</p>
                        <p class="text-gray-700">{{ $payroll->notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-2">
            <a href="{{ route('hr.payroll') }}"
               class="px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-100 transition">
                <i class="ik ik-arrow-left mr-1"></i>{{ __('Back') }}
            </a>
            @if($payroll->status === 'draft')
                <form action="{{ route('hr.payroll.process', $payroll->id) }}" method="POST" class="inline">
                    @csrf
                    <button class="px-4 py-2 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 transition">
                        <i class="ik ik-check-square mr-1"></i>{{ __('Process') }}
                    </button>
                </form>
            @endif
            @if($payroll->status === 'processed')
                <form action="{{ route('hr.payroll.pay', $payroll->id) }}" method="POST" class="inline">
                    @csrf
                    <button class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition">
                        <i class="ik ik-check-circle mr-1"></i>{{ __('Mark as Paid') }}
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

@endsection