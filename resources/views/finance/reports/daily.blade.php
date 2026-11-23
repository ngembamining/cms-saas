@extends('layouts.main')

@section('title', 'Daily Report')

@section('content')
<x-page-header title="Daily Report" subtitle="View daily transactions and expenses" icon="ik ik-calendar"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Reports' => route('finance.reports.index'), 'Daily' => null]" />

<div class="max-w-6xl mx-auto">
    <!-- Filter -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('finance.reports.daily') }}" class="flex gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Date</label>
                <input type="date" name="date" value="{{ $date }}" 
                       class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            </div>
            <button type="submit" class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-600">
                <i class="ik ik-search"></i> View
            </button>
            <a href="{{ route('finance.reports.daily') }}" class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-300">
                <i class="ik ik-rotate-ccw"></i> Today
            </a>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3 mb-6">
        <x-stat-card value="{{ number_format($totalSales, 0) }}" 
                     label="Total Sales" 
                     icon="ik ik-trending-up" 
                     color="green" 
                     subtitle="TZS" />
        
        <x-stat-card value="{{ number_format($totalPurchases, 0) }}" 
                     label="Total Purchases" 
                     icon="ik ik-shopping-bag" 
                     color="primary" 
                     subtitle="TZS" />
        
        <x-stat-card value="{{ number_format($totalExpenses, 0) }}" 
                     label="Total Expenses" 
                     icon="ik ik-dollar-sign" 
                     color="danger" 
                     subtitle="TZS" />
    </div>

    <!-- Transactions -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Transactions</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-3 py-2 font-medium">Code</th>
                        <th class="px-3 py-2 font-medium">Type</th>
                        <th class="px-3 py-2 font-medium">Amount</th>
                        <th class="px-3 py-2 font-medium">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($transactions as $transaction)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 font-mono text-sm text-gray-600">{{ $transaction->transaction_code }}</td>
                        <td class="px-3 py-2 text-gray-700">{{ ucfirst(str_replace('_', ' ', $transaction->transaction_type)) }}</td>
                        <td class="px-3 py-2 font-semibold {{ $transaction->transaction_type == 'gold_sale' ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($transaction->amount, 0) }} TZS
                        </td>
                        <td class="px-3 py-2 text-gray-500">{{ $transaction->created_at->format('H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-3 py-4 text-center text-gray-400">No transactions found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Expenses -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Expenses</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-3 py-2 font-medium">Category</th>
                        <th class="px-3 py-2 font-medium">Amount</th>
                        <th class="px-3 py-2 font-medium">Description</th>
                        <th class="px-3 py-2 font-medium">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($expenses as $expense)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 text-gray-700">{{ $expense->category }}</td>
                        <td class="px-3 py-2 font-semibold text-red-600">{{ number_format($expense->amount, 0) }} TZS</td>
                        <td class="px-3 py-2 text-gray-700">{{ Str::limit($expense->description, 30) }}</td>
                        <td class="px-3 py-2 text-gray-500">{{ $expense->created_at->format('H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-3 py-4 text-center text-gray-400">No expenses found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection