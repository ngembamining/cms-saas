@extends('layouts.main')

@section('title', 'Cash Transactions')

@section('content')
<x-page-header title="Cash Transactions" subtitle="Manage all cash transactions" icon="ik ik-dollar-sign"
    :breadcrumbs="['Home' => url('dashboard'), 'Transactions' => route('transactions.index'), 'Cash' => null]">
    <div class="flex gap-2">
        <a href="{{ route('transactions.cash.deposit') }}" class="btn btn-success">
            <i class="ik ik-plus"></i> Deposit
        </a>
        <a href="{{ route('transactions.cash.withdraw') }}" class="btn btn-danger">
            <i class="ik ik-minus"></i> Withdraw
        </a>
        <a href="{{ route('transactions.cash.transfers') }}" class="btn btn-primary">
            <i class="ik ik-arrow-right"></i> Transfers
        </a>
    </div>
</x-page-header>

<!-- Statistics -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3 mb-6">
    <x-stat-card value="{{ number_format($totalDeposits, 0) }}" 
                 label="Total Deposits" 
                 icon="ik ik-arrow-up" 
                 color="green" 
                 subtitle="TZS" />
    
    <x-stat-card value="{{ number_format($totalWithdrawals, 0) }}" 
                 label="Total Withdrawals" 
                 icon="ik ik-arrow-down" 
                 color="danger" 
                 subtitle="TZS" />
    
    <x-stat-card value="{{ number_format($totalDeposits - $totalWithdrawals, 0) }}" 
                 label="Net Balance" 
                 icon="ik ik-dollar-sign" 
                 color="primary" 
                 subtitle="TZS" />
</div>

<!-- Transactions Table -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 font-medium">Code</th>
                    <th class="px-5 py-3 font-medium">Type</th>
                    <th class="px-5 py-3 font-medium">Branch</th>
                    <th class="px-5 py-3 font-medium">Amount</th>
                    <th class="px-5 py-3 font-medium">Payment Method</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                    <th class="px-5 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($transactions as $transaction)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-mono text-sm text-gray-600">{{ $transaction->transaction_code }}</td>
                    <td class="px-5 py-3">
                        @if($transaction->transaction_type == 'cash_deposit')
                            <span class="badge badge-success">Deposit</span>
                        @elseif($transaction->transaction_type == 'cash_withdrawal')
                            <span class="badge badge-danger">Withdrawal</span>
                        @else
                            <span class="badge badge-primary">Transfer</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-700">{{ $transaction->branch->name ?? 'N/A' }}</td>
                    <td class="px-5 py-3 font-semibold {{ $transaction->transaction_type == 'cash_deposit' ? 'text-green-600' : 'text-red-600' }}">
                        {{ $transaction->transaction_type == 'cash_deposit' ? '+' : '-' }} {{ number_format($transaction->amount, 0) }} TZS
                    </td>
                    <td class="px-5 py-3 text-gray-700">{{ ucfirst(str_replace('_', ' ', $transaction->payment_method ?? 'N/A')) }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-5 py-3">
                        <a href="#" class="text-blue-500 hover:text-blue-600">
                            <i class="ik ik-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-8 text-center text-gray-400">
                        <i class="ik ik-dollar-sign text-3xl block mb-2"></i>
                        No cash transactions recorded yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $transactions->links() }}
    </div>
</div>
@endsection