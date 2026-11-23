@extends('layouts.main')

@section('title', 'Category Expense Report')

@section('content')
<x-page-header title="Category Expense Report" subtitle="View expenses by category" icon="ik ik-grid"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Expenses' => route('finance.expenses.index'), 'Reports' => route('finance.expenses.reports.index'), 'Category' => null]" />

<div class="max-w-6xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium">Category</th>
                        <th class="px-5 py-3 font-medium">Description</th>
                        <th class="px-5 py-3 font-medium">Number of Expenses</th>
                        <th class="px-5 py-3 font-medium">Total Amount</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($categories as $category)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-800">{{ $category->name }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ $category->description ?? 'N/A' }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ $category->expenses->count() }}</td>
                        <td class="px-5 py-3 font-semibold text-red-600">{{ number_format($category->expenses->sum('amount'), 0) }} TZS</td>
                        <td class="px-5 py-3">
                            @if($category->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-gray">Inactive</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-8 text-center text-gray-400">No categories found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection