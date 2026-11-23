@extends('layouts.main')

@section('title', 'Expenses')

@section('content')
<x-page-header title="Expenses" subtitle="Manage all expenses" icon="ik ik-dollar-sign"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Expenses' => null]">
    <div class="flex gap-2">
        <a href="{{ route('finance.expenses.create') }}" class="btn btn-primary">
            <i class="ik ik-plus"></i> New Expense
        </a>
        <a href="{{ route('finance.expenses.print', request()->all()) }}" class="btn btn-info" target="_blank">
            <i class="ik ik-printer"></i> Print Report
        </a>
        <a href="{{ route('finance.expenses.export', request()->all()) }}" class="btn btn-success">
            <i class="ik ik-download"></i> Export Excel
        </a>
    </div>
</x-page-header>

<!-- Statistics -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4 mb-6">
    <x-stat-card value="TZS {{ number_format($totalExpenses ?? 0, 0) }}" 
                 label="Total Expenses" 
                 icon="ik ik-dollar-sign" 
                 color="danger" />
    
    <x-stat-card value="{{ number_format($expenses->total() ?? 0) }}" 
                 label="Total Records" 
                 icon="ik ik-list" 
                 color="primary" />
    
    <x-stat-card value="{{ number_format($averageExpense ?? 0, 0) }}" 
                 label="Average Expense" 
                 icon="ik ik-bar-chart" 
                 color="info" />
    
    <x-stat-card value="{{ number_format($expenses->max('amount') ?? 0, 0) }}" 
                 label="Highest Expense" 
                 icon="ik ik-trending-up" 
                 color="warning" />
</div>

<!-- Filter Section -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6" x-data="filterExpenses()">
    <div class="flex items-center justify-between mb-3">
        <h4 class="text-sm font-semibold text-gray-700">
            <i class="ik ik-filter mr-2"></i> Filter Expenses
        </h4>
        <div class="flex gap-2">
            <button @click="resetFilters()" class="text-sm text-gray-500 hover:text-gray-700">
                <i class="ik ik-rotate-ccw"></i> Reset
            </button>
            <button @click="applyFilters()" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                <i class="ik ik-search"></i> Apply Filters
            </button>
        </div>
    </div>
    
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
            <select x-model="filters.category" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->name }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Branch</label>
            <select x-model="filters.branch_id" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                <option value="">All Branches</option>
                @foreach($branches ?? [] as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
        
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Min Amount (TZS)</label>
            <input type="number" x-model="filters.min_amount" placeholder="0" 
                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
        </div>
        
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Max Amount (TZS)</label>
            <input type="number" x-model="filters.max_amount" placeholder="No limit" 
                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
        </div>
    </div>
    
    <!-- Date Range Filter -->
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 mt-3 pt-3 border-t border-gray-100">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Start Date</label>
            <input type="date" x-model="filters.start_date" 
                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">End Date</label>
            <input type="date" x-model="filters.end_date" 
                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
        </div>
    </div>
</div>

<!-- Expenses Table -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 font-medium">#</th>
                    <th class="px-5 py-3 font-medium">Category</th>
                    <th class="px-5 py-3 font-medium">Branch</th>
                    <th class="px-5 py-3 font-medium text-right">Amount</th>
                    <th class="px-5 py-3 font-medium">Description</th>
                    <th class="px-5 py-3 font-medium text-center">Date</th>
                    <th class="px-5 py-3 font-medium text-center">Recorded By</th>
                    <th class="px-5 py-3 font-medium text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($expenses as $expense)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 text-gray-700">#{{ $expense->id }}</td>
                    <td class="px-5 py-3">
                        <span class="bg-gray-100 px-2 py-1 rounded text-xs">{{ $expense->category ?? 'Uncategorized' }}</span>
                    </td>
                    <td class="px-5 py-3 text-gray-700">{{ $expense->branch->name ?? 'N/A' }}</td>
                    <td class="px-5 py-3 text-right font-semibold text-red-600">{{ number_format($expense->amount, 0) }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ Str::limit($expense->description ?? 'No description', 30) }}</td>
                    <td class="px-5 py-3 text-center text-gray-500">{{ $expense->created_at->format('d/m/Y') }}</td>
                    <td class="px-5 py-3 text-center text-gray-500">{{ $expense->recordedBy->full_name ?? $expense->recordedBy->username ?? 'N/A' }}</td>
                    <td class="px-5 py-3 text-center">
                        <a href="{{ route('finance.expenses.show', $expense->id) }}" 
                           class="text-blue-500 hover:text-blue-600 mr-2" title="View">
                            <i class="ik ik-eye"></i>
                        </a>
                        <a href="{{ route('finance.expenses.edit', $expense->id) }}" 
                           class="text-green-500 hover:text-green-600 mr-2" title="Edit">
                            <i class="ik ik-edit"></i>
                        </a>
                        <form action="{{ route('finance.expenses.destroy', $expense->id) }}" 
                              method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-600" 
                                    onclick="return confirm('Delete this expense?')" title="Delete">
                                <i class="ik ik-trash-2"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-5 py-8 text-center text-gray-400">
                        <i class="ik ik-dollar-sign text-3xl block mb-2"></i>
                        No expenses recorded yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
            <tfoot class="border-t border-gray-200 bg-gray-50">
                <tr>
                    <td colspan="3" class="px-5 py-3 font-semibold text-gray-700">Total</td>
                    <td class="px-5 py-3 text-right font-semibold text-red-600">{{ number_format($totalExpenses ?? 0, 0) }}</td>
                    <td colspan="4" class="px-5 py-3"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="mt-4">
        {{ $expenses->links() }}
    </div>
</div>

<script>
function filterExpenses() {
    return {
        filters: {
            category: '{{ request('category') ?? '' }}',
            branch_id: '{{ request('branch_id') ?? '' }}',
            min_amount: '{{ request('min_amount') ?? '' }}',
            max_amount: '{{ request('max_amount') ?? '' }}',
            start_date: '{{ request('start_date') ?? '' }}',
            end_date: '{{ request('end_date') ?? '' }}'
        },
        
        resetFilters() {
            this.filters = {
                category: '',
                branch_id: '',
                min_amount: '',
                max_amount: '',
                start_date: '',
                end_date: ''
            };
            this.applyFilters();
        },
        
        applyFilters() {
            const params = new URLSearchParams();
            Object.keys(this.filters).forEach(key => {
                if (this.filters[key]) {
                    params.append(key, this.filters[key]);
                }
            });
            
            const url = window.location.pathname + '?' + params.toString();
            window.location.href = url;
        }
    }
}
</script>
@endsection