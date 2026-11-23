@extends('layouts.main')

@section('title', 'Transactions')

@section('content')
<x-page-header title="Transactions" subtitle="Manage all financial and gold transactions" icon="ik ik-repeat"
    :breadcrumbs="['Home' => url('dashboard'), 'Transactions' => null]">
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('transactions.gold.purchases.create') }}" class="btn btn-primary">
            <i class="ik ik-plus"></i> New Purchase
        </a>
        <a href="{{ route('transactions.gold.sales.create') }}" class="btn btn-success">
            <i class="ik ik-plus"></i> New Sale
        </a>
    </div>
</x-page-header>

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

<!-- Statistics -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4 mb-6">
    <x-stat-card value="{{ number_format($stats['total_amount'] ?? 0, 0) }}"
                 label="Total Value"
                 icon="ik ik-dollar-sign"
                 color="primary"
                 subtitle="TZS" />

    <x-stat-card value="{{ number_format($stats['total_count'] ?? 0) }}"
                 label="Total Transactions"
                 icon="ik ik-repeat"
                 color="blue" />

    <x-stat-card value="{{ number_format($stats['gold_sales'] ?? 0, 0) }}"
                 label="Gold Sales"
                 icon="ik ik-trending-up"
                 color="green"
                 subtitle="TZS" />

    <x-stat-card value="{{ number_format($stats['gold_purchases'] ?? 0, 0) }}"
                 label="Gold Purchases"
                 icon="ik ik-shopping-bag"
                 color="amber"
                 subtitle="TZS" />
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form action="{{ route('transactions.index') }}" method="GET"
          class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Code, description..."
                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
            <select name="type"
                    class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                <option value="">All Types</option>
                <option value="gold_purchase" {{ request('type') == 'gold_purchase' ? 'selected' : '' }}>Gold Purchase</option>
                <option value="gold_sale" {{ request('type') == 'gold_sale' ? 'selected' : '' }}>Gold Sale</option>
                <option value="cash_deposit" {{ request('type') == 'cash_deposit' ? 'selected' : '' }}>Cash Deposit</option>
                <option value="cash_withdrawal" {{ request('type') == 'cash_withdrawal' ? 'selected' : '' }}>Cash Withdrawal</option>
                <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>Expense</option>
                <option value="loan_disbursement" {{ request('type') == 'loan_disbursement' ? 'selected' : '' }}>Loan Disbursement</option>
                <option value="loan_repayment" {{ request('type') == 'loan_repayment' ? 'selected' : '' }}>Loan Repayment</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Branch</label>
            <select name="branch_id"
                    class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                <option value="">All Branches</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Start Date</label>
            <input type="date" name="start_date" value="{{ request('start_date') }}"
                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">End Date</label>
            <input type="date" name="end_date" value="{{ request('end_date') }}"
                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
        </div>

        <div class="sm:col-span-2 lg:col-span-5 flex flex-wrap gap-2">
            <button type="submit"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition flex items-center">
                <i class="ik ik-search mr-2"></i> Apply Filters
            </button>
            <a href="{{ route('transactions.index') }}"
               class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition flex items-center">
                <i class="ik ik-rotate-ccw mr-2"></i> Reset
            </a>
            <button type="button" onclick="window.print()"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition flex items-center ml-auto">
                <i class="ik ik-printer mr-2"></i> Print
            </button>
            <a href="{{ route('transactions.export', request()->query()) }}"
               class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition flex items-center">
                <i class="ik ik-download mr-2"></i> Export
            </a>
        </div>
    </form>
</div>

<!-- Transactions Table -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-4 py-3 font-medium">Code</th>
                    <th class="px-4 py-3 font-medium">Type</th>
                    <th class="px-4 py-3 font-medium">Branch</th>
                    <th class="px-4 py-3 font-medium">Party</th>
                    <th class="px-4 py-3 font-medium">Amount</th>
                    <th class="px-4 py-3 font-medium">Method</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Date</th>
                    <th class="px-4 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @php
                    $canDelete = in_array(auth()->user()->role, ['branch_manager', 'director'], true);
                    $typeColors = [
                        'gold_sale'         => 'success',
                        'gold_purchase'     => 'amber',
                        'cash_deposit'      => 'info',
                        'cash_withdrawal'   => 'danger',
                        'expense'           => 'danger',
                        'fund_transfer'     => 'info',
                        'loan_disbursement' => 'primary',
                        'loan_repayment'    => 'success',
                    ];
                @endphp
                @forelse($transactions as $tx)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-xs text-gray-600">
                        {{ $tx->transaction_code }}
                    </td>
                    <td class="px-4 py-3">
                        @php $tc = $typeColors[$tx->transaction_type] ?? 'gray'; @endphp
                        <span class="inline-flex items-center gap-1 bg-{{ $tc }}-100 text-{{ $tc }}-700 px-2 py-1 rounded text-xs font-medium">
                            <i class="ik ik-{{
                                $tx->transaction_type == 'gold_sale' ? 'trending-up' :
                                ($tx->transaction_type == 'gold_purchase' ? 'shopping-bag' :
                                ($tx->transaction_type == 'expense' ? 'credit-card' :
                                ($tx->transaction_type == 'loan_disbursement' ? 'file-text' :
                                ($tx->transaction_type == 'loan_repayment' ? 'check-circle' :
                                ($tx->transaction_type == 'cash_deposit' ? 'arrow-down' : 'arrow-up')))))
                            }}"></i>
                            {{ ucfirst(str_replace('_', ' ', $tx->transaction_type)) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-700">
                        {{ $tx->branch->name ?? 'N/A' }}
                    </td>
                    <td class="px-4 py-3 text-gray-700">
                        @if($tx->customer)
                            <span class="text-blue-600">
                                <i class="ik ik-user text-xs mr-1"></i>{{ $tx->customer->name }}
                            </span>
                        @elseif($tx->supplier)
                            <span class="text-purple-600">
                                <i class="ik ik-truck text-xs mr-1"></i>{{ $tx->supplier->name }}
                            </span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 font-semibold {{ in_array($tx->transaction_type, ['gold_sale', 'cash_deposit', 'loan_repayment']) ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($tx->amount, 0) }} TZS
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">
                        {{ ucfirst(str_replace('_', ' ', $tx->payment_method ?? 'cash')) }}
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $statusClasses = [
                                'completed' => 'bg-green-100 text-green-700',
                                'pending'   => 'bg-yellow-100 text-yellow-700',
                                'reversed'  => 'bg-purple-100 text-purple-700',
                                'cancelled' => 'bg-red-100 text-red-700',
                            ];
                            $sc = $statusClasses[$tx->status] ?? 'bg-gray-100 text-gray-700';
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $sc }}">
                            {{ ucfirst($tx->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">
                        <span title="{{ $tx->created_at->format('l, F j, Y g:i A') }}">
                            {{ $tx->created_at->format('d/m/Y H:i') }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <a href="{{ route('transactions.show', $tx->id) }}"
                           class="text-blue-500 hover:text-blue-600 mr-2"
                           title="View">
                            <i class="ik ik-eye"></i>
                        </a>

                        @if($tx->status == 'pending')
                            <a href="{{ route('transactions.edit', $tx->id) }}"
                               class="text-green-500 hover:text-green-600 mr-2"
                               title="Edit">
                                <i class="ik ik-edit"></i>
                            </a>
                        @endif

                        @if($canDelete && $tx->status != 'reversed')
                            <form action="{{ route('transactions.destroy', $tx->id) }}"
                                  method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="text-red-500 hover:text-red-600"
                                        title="Delete"
                                        onclick="return confirm('Delete this transaction? Balances will be reversed. Continue?')">
                                    <i class="ik ik-trash-2"></i>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-5 py-12 text-center text-gray-400">
                        <i class="ik ik-repeat text-4xl block mb-3"></i>
                        <p class="text-base font-medium text-gray-500">No transactions found</p>
                        <p class="text-sm mt-1">Try adjusting your filters or create a new transaction.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-3">
        <p class="text-xs text-gray-500">
            Showing
            <span class="font-medium">{{ $transactions->firstItem() ?? 0 }}</span> to
            <span class="font-medium">{{ $transactions->lastItem() ?? 0 }}</span> of
            <span class="font-medium">{{ $transactions->total() }}</span> transactions
        </p>
        {{ $transactions->links() }}
    </div>
</div>
@endsection