@extends('layouts.main')

@section('title', 'New Expense')

@section('content')
<x-page-header title="New Expense" subtitle="Record a new expense" icon="ik ik-plus"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Expenses' => route('finance.expenses.index'), 'New' => null]" />

<div class="max-w-3xl mx-auto">
    <x-card>
        <x-slot:header>{{ __('Record New Expense') }}</x-slot:header>

        @if(session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <i class="ik ik-alert-circle mr-2"></i>
                {{ session('error') }}
            </div>
        @endif

        @if(session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                <i class="ik ik-check-circle mr-2"></i>
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="mb-1 font-medium"><i class="ik ik-alert-circle mr-2"></i>Please fix the following before submitting:</p>
                <ul class="ml-6 list-disc">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('finance.expenses.store') }}" id="expenseForm">
            @csrf

            <div class="space-y-4">
                <div>
                    <x-form.select name="branch_id" label="Branch" :required="true">
                        <option value="">Select Branch</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </x-form.select>
                </div>

                <div>
                    <x-form.select name="category" label="Category" :required="true">
                        <option value="">Select Category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->name }}" {{ old('category') == $category->name ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </x-form.select>
                </div>

                <div>
                    <x-form.input name="amount" label="Amount (TZS)" type="number" step="0.01" min="0.01" placeholder="Enter amount" :required="true" />
                </div>

                {{-- ============================== --}}
                {{-- EXPENSE DATE (NEW)             --}}
                {{-- ============================== --}}
                <div>
                    <label for="expense_date" class="block text-sm font-medium text-gray-700 mb-1">
                        Expense Date <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="date"
                               id="expense_date"
                               name="expense_date"
                               value="{{ old('expense_date', date('Y-m-d')) }}"
                               max="{{ date('Y-m-d') }}"
                               required
                               class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 pr-10 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="ik ik-calendar"></i>
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        <i class="ik ik-info mr-1"></i>
                        Select the date the expense occurred. <strong>Future dates are not allowed</strong> — the latest allowed is today ({{ date('d/m/Y') }}).
                    </p>
                    @error('expense_date')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-form.textarea name="description" label="Description" rows="3" placeholder="Enter expense description" />
                </div>
            </div>

            {{-- ============================================ --}}
            {{-- SMS NOTIFICATIONS SECTION                    --}}
            {{-- ============================================ --}}
            <div class="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
                <h6 class="font-semibold text-blue-800">
                    <i class="ik ik-message-circle mr-2"></i>SMS Notifications
                </h6>
                <div class="mt-2 space-y-2 text-sm text-blue-700">
                    <div class="flex items-start gap-2">
                        <span class="mt-0.5">📱</span>
                        <div>
                            <strong>Branch Manager:</strong> Approval request with full expense details
                            <span class="block text-xs text-blue-500 mt-0.5">Template: expense_added (always sent)</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="mt-0.5">📱</span>
                        <div>
                            <strong>Directors:</strong> Expense report summary
                            <span class="block text-xs text-blue-500 mt-0.5">Template: expense_director (only for amounts ≥ TZS 1,000,000)</span>
                        </div>
                    </div>
                </div>
                <div class="mt-3 p-2 bg-blue-100 rounded-lg">
                    <p class="text-xs text-blue-700">
                        <i class="ik ik-info mr-1"></i>
                        SMS will be sent automatically after the expense is successfully recorded. Small expenses below TZS 1,000,000 will not trigger director SMS, to reduce noise.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-6">
                <a href="{{ route('finance.expenses.index') }}" class="rounded-lg bg-gray-200 px-6 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-300">
                    <i class="ik ik-x"></i> Cancel
                </a>
                <button type="submit" class="rounded-lg bg-primary-500 px-6 py-2.5 text-sm font-medium text-white transition hover:bg-primary-600">
                    <i class="ik ik-save"></i> Record Expense
                </button>
            </div>
        </form>
    </x-card>
</div>

<script>
    // ============================================
    // Expense Date Guard — block future dates
    // ============================================
    document.addEventListener('DOMContentLoaded', function () {
        const dateInput = document.getElementById('expense_date');
        if (!dateInput) return;

        // Today's date in local timezone (YYYY-MM-DD)
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        const todayStr = `${yyyy}-${mm}-${dd}`;

        // Reinforce max in case server-side differs
        dateInput.setAttribute('max', todayStr);

        function validateDate() {
            if (dateInput.value && dateInput.value > todayStr) {
                alert('Future dates are not allowed. Please select today or an earlier date.');
                dateInput.value = todayStr;
            }
        }

        dateInput.addEventListener('change', validateDate);
        dateInput.addEventListener('input', validateDate);

        // Extra guard on submit
        const form = document.getElementById('expenseForm');
        if (form) {
            form.addEventListener('submit', function (e) {
                if (dateInput.value && dateInput.value > todayStr) {
                    e.preventDefault();
                    alert('Future dates are not allowed. Please correct the expense date.');
                    dateInput.focus();
                    return false;
                }
            });
        }
    });
</script>
@endsection