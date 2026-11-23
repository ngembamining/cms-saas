@extends('layouts.main')

@section('title', 'Record Loan Payment')

@section('content')
<x-page-header title="Record Loan Payment" subtitle="Record a new loan payment" icon="ik ik-credit-card"
    :breadcrumbs="['Home' => url('dashboard'), 'Loans' => route('loans.index'), 'Payments' => route('loans.payments.index'), 'New' => null]" />

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h4 class="text-sm font-semibold text-gray-700 mb-4">
            {{ __('Record Loan Payment') }}
        </h4>

        @if(session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <i class="ik ik-alert-circle mr-2"></i>
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('loans.payments.store') }}" id="paymentForm"
              x-data="loanPaymentForm()" x-init="init()">
            @csrf

            <div class="space-y-4">
                <!-- Loan Selection (searchable) -->
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <label for="loan_search" class="block text-sm font-medium text-gray-700 mb-1">Loan *</label>

                    <!-- Hidden field actually submitted with the form -->
                    <input type="hidden" name="loan_id" id="loan_id" x-model="loanId">

                    <div class="relative">
                        <input type="text"
                               id="loan_search"
                               x-model="loanSearch"
                               @focus="open = true"
                               @input="open = true; if (!$event.target.value) clearLoan()"
                               placeholder="Search by supplier, loan #, or amount..."
                               autocomplete="off"
                               class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100 pr-7"
                               :class="{ 'border-red-300': showRequiredError }">
                        <button type="button" x-show="loanSearch" @click="clearLoan()"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <i class="ik ik-x text-xs"></i>
                        </button>
                    </div>

                    <!-- Dropdown results -->
                    <div x-show="open && filteredLoans().length > 0"
                         x-transition
                         class="absolute z-20 mt-1 w-full max-h-64 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg">
                        <template x-for="loan in filteredLoans()" :key="loan.id">
                            <button type="button"
                                    @click="selectLoan(loan); open = false"
                                    class="block w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-primary-50"
                                    :class="{ 'bg-primary-50 font-medium': loanId == loan.id }">
                                <span x-text="'#' + loan.id + ' - ' + loan.supplier + ' (' + loan.amount_formatted + ' TZS)'"></span>
                            </button>
                        </template>
                    </div>

                    <!-- No matches -->
                    <div x-show="open && loanSearch && filteredLoans().length === 0"
                         x-transition
                         class="absolute z-20 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg px-3 py-2 text-sm text-gray-400">
                        No loans match "<span x-text="loanSearch"></span>"
                    </div>
                </div>

                <!-- Loan Details (AJAX loaded) -->
                <div id="loanDetails" class="bg-gray-50 rounded-lg p-4" x-show="loanId" x-cloak>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <span class="text-gray-500">Supplier:</span>
                            <span class="font-medium" id="loanSupplier">-</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Branch:</span>
                            <span class="font-medium" id="loanBranch">-</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Total Payable:</span>
                            <span class="font-medium text-primary-600" id="loanTotalPayable">-</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Remaining Balance:</span>
                            <span class="font-medium text-green-600" id="loanRemainingBalance">-</span>
                        </div>
                    </div>
                </div>

                <!-- Amount -->
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Amount (TZS) *</label>
                    <input type="number" name="amount" id="amount" step="0.01" min="0.01" 
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                           placeholder="Enter amount" required>
                </div>

                <!-- Payment Date -->
                <div>
                    <label for="payment_date" class="block text-sm font-medium text-gray-700 mb-1">Payment Date *</label>
                    <input type="date" name="payment_date" id="payment_date" 
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                           value="{{ date('Y-m-d') }}" required>
                </div>

                <!-- Payment Method -->
                <div>
                    <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-1">Payment Method *</label>
                    <select name="payment_method" id="payment_method" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100" required>
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="mobile_money">Mobile Money</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-400">Cash payments are auto-verified</p>
                </div>

                <!-- Reference -->
                <div>
                    <label for="reference" class="block text-sm font-medium text-gray-700 mb-1">Reference</label>
                    <input type="text" name="reference" id="reference" 
                           class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                           placeholder="Enter payment reference">
                </div>

                <!-- Comments -->
                <div>
                    <label for="comments" class="block text-sm font-medium text-gray-700 mb-1">Comments</label>
                    <textarea name="comments" id="comments" rows="2" 
                              class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                              placeholder="Enter any additional notes"></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-6">
                <a href="{{ route('loans.payments.index') }}" class="rounded-lg bg-gray-200 px-6 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-300">
                    <i class="ik ik-x"></i> Cancel
                </a>
                <button type="submit" class="rounded-lg bg-primary-500 px-6 py-2.5 text-sm font-medium text-white transition hover:bg-primary-600">
                    <i class="ik ik-save"></i> Record Payment
                </button>
            </div>
        </form>
    </div>
</div>

@php
    // Build this in plain PHP first — an inline multi-line @json(...fn()...)
    // call in the script block below can trip up Blade's directive parser.
    $loansForSearch = $loans->map(function ($loan) {
        return [
            'id' => $loan->id,
            'supplier' => $loan->supplier->name ?? 'N/A',
            'amount_formatted' => number_format($loan->amount, 0),
        ];
    })->values();
@endphp
<script>
function loanPaymentForm() {
    return {
        // Full loan list passed from the server, used for client-side search
        loans: @json($loansForSearch),

        loanId: @json($selectedLoan->id ?? ''),
        loanSearch: '',
        showRequiredError: false,

        init() {
            // Pre-fill the search box if a loan came pre-selected from the server
            if (this.loanId) {
                const match = this.loans.find(l => l.id == this.loanId);
                if (match) {
                    this.loanSearch = '#' + match.id + ' - ' + match.supplier + ' (' + match.amount_formatted + ' TZS)';
                }
                this.fetchLoanDetails(this.loanId);
            }

            // Prevent submit if no loan was actually selected
            this.$el.addEventListener('submit', (e) => {
                if (!this.loanId) {
                    e.preventDefault();
                    this.showRequiredError = true;
                    document.getElementById('loan_search').focus();
                }
            });
        },

        // Returns loans matching the current search text (id, supplier, or amount), capped to 20
        filteredLoans() {
            const q = this.loanSearch.trim().toLowerCase();
            if (!q) return this.loans.slice(0, 20);
            return this.loans
                .filter(l =>
                    l.supplier.toLowerCase().includes(q) ||
                    String(l.id).includes(q) ||
                    l.amount_formatted.toLowerCase().includes(q)
                )
                .slice(0, 20);
        },

        selectLoan(loan) {
            this.loanId = loan.id;
            this.loanSearch = '#' + loan.id + ' - ' + loan.supplier + ' (' + loan.amount_formatted + ' TZS)';
            this.showRequiredError = false;
            this.fetchLoanDetails(loan.id);
        },

        clearLoan() {
            this.loanId = '';
            this.loanSearch = '';
            document.getElementById('loanSupplier').textContent = '-';
            document.getElementById('loanBranch').textContent = '-';
            document.getElementById('loanTotalPayable').textContent = '-';
            document.getElementById('loanRemainingBalance').textContent = '-';
        },

        fetchLoanDetails(loanId) {
            if (!loanId) return;

            fetch('/loans/api/loan-details/' + loanId)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loanSupplier').textContent = data.supplier;
                    document.getElementById('loanBranch').textContent = data.branch;
                    document.getElementById('loanTotalPayable').textContent = 'TZS ' + Number(data.total_payable).toLocaleString();
                    document.getElementById('loanRemainingBalance').textContent = 'TZS ' + Number(data.remaining_balance).toLocaleString();
                })
                .catch(error => {
                    console.error('Error fetching loan details:', error);
                });
        }
    }
}
</script>
@endsection