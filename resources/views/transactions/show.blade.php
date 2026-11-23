@extends('layouts.main')

@section('title', 'Transaction ' . $transaction->transaction_code)

@section('content')
<x-page-header
    title="{{ $transaction->transaction_code }}"
    subtitle="{{ $transaction->type_label }}"
    icon="ik ik-repeat"
    :breadcrumbs="[
        'Home'         => url('dashboard'),
        'Transactions' => route('transactions.index'),
        'Details'      => null,
    ]">
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('transactions.index') }}" class="btn btn-light">
            <i class="ik ik-arrow-left"></i> Back
        </a>
        <button onclick="window.print()" class="btn btn-light">
            <i class="ik ik-printer"></i> Print
        </button>
        @if($transaction->is_reversible)
            <button type="button" class="btn btn-danger" onclick="openReverseModal()">
                <i class="ik ik-rotate-ccw"></i> Reverse
            </button>
        @endif
    </div>
</x-page-header>

{{-- Flash messages --}}
@if(session('success'))
    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
        <i class="ik ik-check-circle mr-2"></i>{{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <i class="ik ik-alert-circle mr-2"></i>{{ session('error') }}
    </div>
@endif

@if($transaction->status === 'reversed')
    <div class="mb-4 rounded-lg border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">
        <i class="ik ik-alert-triangle mr-2"></i>
        <strong>This transaction has been reversed.</strong>
        @if($transaction->reversal_reason)
            Reason: {{ $transaction->reversal_reason }}
        @endif
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- ============ Main column ============ --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Summary card --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-start justify-between flex-wrap gap-4 mb-5">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400 font-medium">
                        {{ __('Amount') }}
                    </p>
                    <p class="text-3xl font-bold mt-1 {{ $transaction->is_inflow ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($transaction->amount, 0) }}
                        <span class="text-base font-medium text-gray-400">TZS</span>
                    </p>
                </div>

                <div class="text-right space-y-1">
                    @php
                        $statusClasses = [
                            'completed' => 'bg-green-100 text-green-700',
                            'pending'   => 'bg-yellow-100 text-yellow-700',
                            'reversed'  => 'bg-orange-100 text-orange-700',
                            'cancelled' => 'bg-red-100 text-red-700',
                        ];
                        $sc = $statusClasses[$transaction->status] ?? 'bg-gray-100 text-gray-700';
                    @endphp
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $sc }}">
                        {{ ucfirst($transaction->status) }}
                    </span>

                    @php
                        $typeColors = [
                            'gold_sale'         => 'bg-green-100 text-green-700',
                            'gold_purchase'     => 'bg-amber-100 text-amber-700',
                            'cash_deposit'      => 'bg-blue-100 text-blue-700',
                            'cash_withdrawal'   => 'bg-red-100 text-red-700',
                            'expense'           => 'bg-red-100 text-red-700',
                            'fund_transfer'     => 'bg-blue-100 text-blue-700',
                            'loan_disbursement' => 'bg-purple-100 text-purple-700',
                            'loan_repayment'    => 'bg-green-100 text-green-700',
                        ];
                        $tc = $typeColors[$transaction->transaction_type] ?? 'bg-gray-100 text-gray-700';
                    @endphp
                    <div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $tc }}">
                            {{ $transaction->type_label }}
                        </span>
                    </div>
                </div>
            </div>

            <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-4 border-t border-gray-100">
                <div>
                    <dt class="text-xs uppercase text-gray-400 font-medium">Code</dt>
                    <dd class="text-sm font-mono text-gray-700 mt-1">{{ $transaction->transaction_code }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-gray-400 font-medium">Date</dt>
                    <dd class="text-sm text-gray-700 mt-1">
                        {{ $transaction->created_at?->format('d M Y, H:i') }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-gray-400 font-medium">Branch</dt>
                    <dd class="text-sm text-gray-700 mt-1">
                        @if($transaction->branch)
                            {{ $transaction->branch->name }}
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-gray-400 font-medium">Payment Method</dt>
                    <dd class="text-sm text-gray-700 mt-1">
                        {{ ucfirst(str_replace('_', ' ', $transaction->payment_method ?? 'cash')) }}
                    </dd>
                </div>

                @if($transaction->reference_no)
                    <div>
                        <dt class="text-xs uppercase text-gray-400 font-medium">Reference</dt>
                        <dd class="text-sm text-gray-700 mt-1">{{ $transaction->reference_no }}</dd>
                    </div>
                @endif
                @if($transaction->payment_reference)
                    <div>
                        <dt class="text-xs uppercase text-gray-400 font-medium">Payment Ref</dt>
                        <dd class="text-sm text-gray-700 mt-1">{{ $transaction->payment_reference }}</dd>
                    </div>
                @endif
            </dl>

            @if($transaction->description)
                <div class="mt-5 pt-4 border-t border-gray-100">
                    <p class="text-xs uppercase text-gray-400 font-medium mb-1">Description</p>
                    <p class="text-sm text-gray-700">{{ $transaction->description }}</p>
                </div>
            @endif

            @if($transaction->comments)
                <div class="mt-3">
                    <p class="text-xs uppercase text-gray-400 font-medium mb-1">Comments</p>
                    <p class="text-sm text-gray-700">{{ $transaction->comments }}</p>
                </div>
            @endif
        </div>

        {{-- Party card (customer OR supplier) --}}
        @if($transaction->customer || $transaction->supplier)
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-base font-semibold text-gray-800 mb-4">
                    {{ $transaction->customer ? __('Customer') : __('Supplier') }}
                </h3>

                @if($transaction->customer)
                    @php $party = $transaction->customer; @endphp
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 flex-shrink-0">
                            <i class="ik ik-user text-xl"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-gray-800 truncate">{{ $party->name }}</p>
                            @if($party->contact)
                                <p class="text-sm text-gray-500">{{ $party->contact }}</p>
                            @endif
                            @if($party->email)
                                <p class="text-sm text-gray-500 truncate">{{ $party->email }}</p>
                            @endif
                        </div>
                        @if(Route::has('customers.show'))
                            <a href="{{ route('customers.show', $party->id) }}"
                               class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                                View <i class="ik ik-arrow-right ml-1"></i>
                            </a>
                        @endif
                    </div>
                @endif

                @if($transaction->supplier)
                    @php $party = $transaction->supplier; @endphp
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 flex-shrink-0">
                            <i class="ik ik-truck text-xl"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-gray-800 truncate">{{ $party->name }}</p>
                            @if($party->contact)
                                <p class="text-sm text-gray-500">{{ $party->contact }}</p>
                            @endif
                            @if($party->email)
                                <p class="text-sm text-gray-500 truncate">{{ $party->email }}</p>
                            @endif
                        </div>
                        @if(Route::has('suppliers.show'))
                            <a href="{{ route('suppliers.show', $party->id) }}"
                               class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                                View <i class="ik ik-arrow-right ml-1"></i>
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        @endif

        {{-- Loan card --}}
        @if($transaction->loan)
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center justify-between">
                    <span>{{ __('Linked Loan') }}</span>
                    <a href="{{ route('loans.show', $transaction->loan->id) }}"
                       class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                        View Loan <i class="ik ik-arrow-right ml-1"></i>
                    </a>
                </h3>
                <dl class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <dt class="text-xs uppercase text-gray-400 font-medium">Loan ID</dt>
                        <dd class="text-sm text-gray-700 mt-1">#{{ $transaction->loan->id }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-gray-400 font-medium">Amount</dt>
                        <dd class="text-sm text-gray-700 mt-1">
                            {{ number_format($transaction->loan->amount, 0) }} TZS
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-gray-400 font-medium">Status</dt>
                        <dd class="text-sm text-gray-700 mt-1">
                            {{ ucfirst($transaction->loan->status) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-gray-400 font-medium">Branch</dt>
                        <dd class="text-sm text-gray-700 mt-1">
                            {{ $transaction->loan->branch->name ?? '—' }}
                        </dd>
                    </div>
                </dl>
            </div>
        @endif

        {{-- Line items (weights / purity / prices) --}}
        @if($transaction->items->count() > 0)
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">{{ __('Line Items') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 bg-gray-50">
                                <th class="px-5 py-3 font-medium">#</th>
                                <th class="px-5 py-3 font-medium">Weight (g)</th>
                                <th class="px-5 py-3 font-medium">Purity (%)</th>
                                <th class="px-5 py-3 font-medium">Rate (TZS/g)</th>
                                <th class="px-5 py-3 font-medium text-right">Amount (TZS)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($transaction->items as $i => $item)
                                <tr>
                                    <td class="px-5 py-3 text-gray-500">{{ $i + 1 }}</td>
                                    <td class="px-5 py-3 text-amber-600 font-medium">
                                        {{ number_format($item->weight ?? 0, 3) }}
                                    </td>
                                    <td class="px-5 py-3 text-gray-700">
                                        {{ number_format($item->purity ?? 0, 2) }}
                                    </td>
                                    <td class="px-5 py-3 text-gray-700">
                                        {{ number_format($item->price ?? $item->rate ?? 0, 0) }}
                                    </td>
                                    <td class="px-5 py-3 text-right font-semibold text-gray-800">
                                        {{ number_format($item->amount ?? 0, 0) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 border-t border-gray-100">
                            <tr>
                                <td colspan="4" class="px-5 py-3 text-right text-xs uppercase text-gray-500 font-medium">
                                    Total
                                </td>
                                <td class="px-5 py-3 text-right font-bold text-gray-800">
                                    {{ number_format($transaction->items->sum('amount'), 0) }} TZS
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endif

        {{-- Related transactions (same loan, or linked reversal) --}}
        @if($related->count() > 0)
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">{{ __('Related Transactions') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 bg-gray-50">
                                <th class="px-5 py-3 font-medium">Code</th>
                                <th class="px-5 py-3 font-medium">Type</th>
                                <th class="px-5 py-3 font-medium">Amount</th>
                                <th class="px-5 py-3 font-medium">Date</th>
                                <th class="px-5 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($related as $rel)
                                <tr class="hover:bg-gray-50 cursor-pointer"
                                    onclick="window.location='{{ route('transactions.show', $rel->id) }}'">
                                    <td class="px-5 py-3 font-mono text-xs text-gray-600">
                                        {{ $rel->transaction_code }}
                                    </td>
                                    <td class="px-5 py-3 text-gray-700">{{ $rel->type_label }}</td>
                                    <td class="px-5 py-3 font-semibold {{ $rel->is_inflow ? 'text-green-600' : 'text-red-600' }}">
                                        {{ number_format($rel->amount, 0) }} TZS
                                    </td>
                                    <td class="px-5 py-3 text-gray-500 text-xs">
                                        {{ $rel->created_at?->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-5 py-3 text-gray-600 text-xs">{{ ucfirst($rel->status) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>

    {{-- ============ Side column ============ --}}
    <div class="space-y-5">

        {{-- Audit trail --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">{{ __('Audit Trail') }}</h3>

            <div class="space-y-4 text-sm">
                <div>
                    <p class="text-xs uppercase text-gray-400 font-medium mb-1">Recorded By</p>
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 flex-shrink-0 text-xs font-semibold">
                            {{ strtoupper(substr($transaction->user->username ?? '?', 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-gray-800 font-medium">
                                {{ $transaction->user->full_name ?? $transaction->user->username ?? 'Unknown' }}
                            </p>
                            <p class="text-xs text-gray-400">
                                {{ $transaction->created_at?->format('d M Y, H:i') }}
                            </p>
                        </div>
                    </div>
                </div>

                @if($transaction->verifier)
                    <div class="pt-3 border-t border-gray-100">
                        <p class="text-xs uppercase text-gray-400 font-medium mb-1">Verified By</p>
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-600 flex-shrink-0 text-xs font-semibold">
                                {{ strtoupper(substr($transaction->verifier->username ?? '?', 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-gray-800 font-medium">
                                    {{ $transaction->verifier->full_name ?? $transaction->verifier->username }}
                                </p>
                                @if($transaction->updated_at && $transaction->updated_at != $transaction->created_at)
                                    <p class="text-xs text-gray-400">
                                        {{ $transaction->updated_at->format('d M Y, H:i') }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                @if($transaction->status === 'reversed' && $transaction->reversal_reason)
                    <div class="pt-3 border-t border-gray-100">
                        <p class="text-xs uppercase text-gray-400 font-medium mb-1">Reversal Reason</p>
                        <p class="text-gray-700 text-xs bg-orange-50 border border-orange-100 rounded-lg px-3 py-2">
                            {{ $transaction->reversal_reason }}
                        </p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Quick actions --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">{{ __('Actions') }}</h3>
            <div class="space-y-2">
                <a href="{{ route('transactions.index') }}"
                   class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                    <i class="ik ik-list"></i> All Transactions
                </a>
                <a href="{{ route('transactions.export', request()->query()) }}"
                   class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                    <i class="ik ik-download"></i> Export CSV
                </a>
                @if($transaction->customer && Route::has('customers.show'))
                    <a href="{{ route('customers.show', $transaction->customer->id) }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                        <i class="ik ik-user"></i> View Customer
                    </a>
                @endif
                @if($transaction->supplier && Route::has('suppliers.show'))
                    <a href="{{ route('suppliers.show', $transaction->supplier->id) }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                        <i class="ik ik-truck"></i> View Supplier
                    </a>
                @endif
            </div>
        </div>

    </div>
</div>

{{-- Reverse confirmation modal --}}
@if($transaction->is_reversible)
    <div id="reverse-modal"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4">
            <form action="{{ route('transactions.reverse', $transaction->id) }}" method="POST">
                @csrf
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-800">
                        <i class="ik ik-alert-triangle text-orange-500 mr-2"></i>
                        {{ __('Reverse this transaction?') }}
                    </h3>
                    <p class="text-sm text-gray-500 mt-2">
                        This will mark the transaction as reversed and reverse its effect on branch
                        balances. This action cannot be undone.
                    </p>
                </div>
                <div class="p-6">
                    <label class="block text-xs font-medium text-gray-600 mb-1">
                        {{ __('Reason for reversal') }} <span class="text-red-500">*</span>
                    </label>
                    <textarea name="reason" rows="3" required minlength="5" maxlength="500"
                              class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                              placeholder="e.g. Duplicate entry, wrong amount, customer cancelled..."></textarea>
                </div>
                <div class="px-6 py-4 bg-gray-50 flex justify-end gap-2 rounded-b-xl">
                    <button type="button" onclick="closeReverseModal()"
                            class="px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-100 text-sm font-medium">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 text-sm font-medium">
                        <i class="ik ik-rotate-ccw mr-1"></i> Confirm Reversal
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif

<script>
function openReverseModal() {
    const m = document.getElementById('reverse-modal');
    if (m) { m.classList.remove('hidden'); m.classList.add('flex'); }
}
function closeReverseModal() {
    const m = document.getElementById('reverse-modal');
    if (m) { m.classList.add('hidden'); m.classList.remove('flex'); }
}
// ESC to close
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeReverseModal();
});
</script>
@endsection