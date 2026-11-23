@extends('layouts.main')

@section('title', 'Daily Expense Report')

@section('content')
<x-page-header title="Daily Expense Report" subtitle="View daily expenses" icon="ik ik-calendar"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Expenses' => route('finance.expenses.index'), 'Reports' => route('finance.expenses.reports.index'), 'Daily' => null]" />

<div class="max-w-6xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('finance.expenses.reports.daily') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Date</label>
                <input type="date" name="date" value="{{ $date ?? date('Y-m-d') }}" 
                       class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            </div>
            <button type="submit" class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-600">
                <i class="ik ik-search"></i> View
            </button>
            <a href="{{ route('finance.expenses.reports.daily') }}" class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-300">
                <i class="ik ik-rotate-ccw"></i> Today
            </a>
        </form>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3 mb-6">
        <x-stat-card value="{{ number_format($total ?? 0, 0) }}" 
                     label="Total Expenses" 
                     icon="ik ik-dollar-sign" 
                     color="danger" 
                     subtitle="TZS" />
        
        <x-stat-card value="{{ $expenses->count() }}" 
                     label="Number of Expenses" 
                     icon="ik ik-list" 
                     color="primary" />
    </div>

    <!-- Expenses Table -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Expenses for {{ \Carbon\Carbon::parse($date ?? now())->format('d/m/Y') }}</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-3 py-2 font-medium">Category</th>
                        <th class="px-3 py-2 font-medium">Branch</th>
                        <th class="px-3 py-2 font-medium">Amount</th>
                        <th class="px-3 py-2 font-medium">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($expenses as $expense)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 text-gray-700">{{ $expense->category }}</td>
                        <td class="px-3 py-2 text-gray-700">{{ $expense->branch->name ?? 'N/A' }}</td>
                        <td class="px-3 py-2 font-semibold text-red-600">{{ number_format($expense->amount, 0) }} TZS</td>
                        <td class="px-3 py-2 text-gray-700">{{ Str::limit($expense->description, 30) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-3 py-4 text-center text-gray-400">No expenses found for this day</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection