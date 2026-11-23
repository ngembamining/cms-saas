@extends('layouts.main')
@section('title', 'Generate Payroll')
@section('content')

<div class="max-w-4xl mx-auto">

    <x-page-header title="{{ __('Generate Payroll') }}"
                   subtitle="{{ __('Create a new payroll entry for an employee') }}"
                   icon="ik ik-credit-card"
                   :breadcrumbs="['Home' => route('dashboard'), 'HR' => route('hr.index'), 'Payroll' => route('hr.payroll'), 'Generate' => null]" />

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
            <ul class="list-disc list-inside text-xs">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('hr.payroll.store') }}" method="POST" class="bg-white rounded-xl shadow-sm overflow-hidden">
        @csrf

        <div class="p-6 space-y-6">

            {{-- Employee & Period --}}
            <div class="grid md:grid-cols-3 gap-4">
                <div class="md:col-span-3">
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
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Period Start') }} *</label>
                    <input type="date" name="period_start" value="{{ old('period_start', now()->startOfMonth()->toDateString()) }}" required
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Period End') }} *</label>
                    <input type="date" name="period_end" value="{{ old('period_end', now()->endOfMonth()->toDateString()) }}" required
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                </div>
                <div></div>
            </div>

            <hr class="border-gray-100">

            {{-- Earnings --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                    <i class="ik ik-trending-up text-green-500 mr-2"></i>{{ __('Earnings') }}
                </h3>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Base Salary') }} *</label>
                        <input type="number" step="0.01" min="0" name="base_salary" id="base_salary"
                               value="{{ old('base_salary', 0) }}" required
                               class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm calc-input">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Allowances') }}</label>
                        <input type="number" step="0.01" min="0" name="allowances" id="allowances"
                               value="{{ old('allowances', 0) }}"
                               class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm calc-input">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Bonus') }}</label>
                        <input type="number" step="0.01" min="0" name="bonus" id="bonus"
                               value="{{ old('bonus', 0) }}"
                               class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm calc-input">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Overtime Pay') }}</label>
                        <input type="number" step="0.01" min="0" name="overtime_pay" id="overtime_pay"
                               value="{{ old('overtime_pay', 0) }}"
                               class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm calc-input">
                    </div>
                </div>
            </div>

            <hr class="border-gray-100">

            {{-- Deductions --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                    <i class="ik ik-trending-down text-red-500 mr-2"></i>{{ __('Deductions') }}
                </h3>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Deductions') }}</label>
                        <input type="number" step="0.01" min="0" name="deductions" id="deductions"
                               value="{{ old('deductions', 0) }}"
                               class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm calc-input">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Tax') }}</label>
                        <input type="number" step="0.01" min="0" name="tax" id="tax"
                               value="{{ old('tax', 0) }}"
                               class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm calc-input">
                    </div>
                </div>
            </div>

            {{-- Net Pay Preview --}}
            <div class="bg-gradient-to-r from-primary-500 to-primary-600 rounded-xl p-4 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-wider opacity-80">{{ __('Net Pay') }}</p>
                        <p class="text-3xl font-bold mt-1" id="net_pay_preview">0</p>
                        <p class="text-xs opacity-75 mt-0.5">TZS</p>
                    </div>
                    <i class="ik ik-wallet text-5xl opacity-30"></i>
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Notes') }}</label>
                <textarea name="notes" rows="2" maxlength="500"
                          class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                          placeholder="{{ __('Optional notes...') }}">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-2">
            <a href="{{ route('hr.payroll') }}"
               class="px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-100 transition">
                {{ __('Cancel') }}
            </a>
            <button type="submit"
                    class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition">
                <i class="ik ik-save mr-1"></i>{{ __('Save Payroll') }}
            </button>
        </div>
    </form>
</div>

<script>
    function recalcNet() {
        const val = id => parseFloat(document.getElementById(id)?.value) || 0;
        const net =
            val('base_salary') + val('allowances') + val('bonus') + val('overtime_pay')
            - val('deductions') - val('tax');
        document.getElementById('net_pay_preview').textContent =
            net.toLocaleString('en-US', { maximumFractionDigits: 2 });
    }
    document.querySelectorAll('.calc-input').forEach(el => el.addEventListener('input', recalcNet));
    recalcNet();
</script>

@endsection