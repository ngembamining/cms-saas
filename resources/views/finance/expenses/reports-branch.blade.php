@extends('layouts.main')

@section('title', 'Branch Expense Report')

@section('content')
<x-page-header title="Branch Expense Report" subtitle="View expenses by branch" icon="fas fa-store"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Expenses' => route('finance.expenses.index'), 'Reports' => route('finance.expenses.reports.index'), 'Branch' => null]" />

<div class="max-w-6xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium">Branch</th>
                        <th class="px-5 py-3 font-medium">Location</th>
                        <th class="px-5 py-3 font-medium">Number of Expenses</th>
                        <th class="px-5 py-3 font-medium">Total Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($branches as $branch)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-800">{{ $branch->name }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ $branch->location ?? 'N/A' }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ $branch->expenses->count() }}</td>
                        <td class="px-5 py-3 font-semibold text-red-600">{{ number_format($branch->expenses->sum('amount'), 0) }} TZS</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-5 py-8 text-center text-gray-400">No branches found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection