@extends('layouts.main')
@section('title', 'Expenses Report')
@section('content')

<div class="max-w-7xl mx-auto">
    <x-page-header title="{{ __('Expenses Report') }}" 
                   subtitle="{{ __('Detailed expense analysis') }}" 
                   icon="ik ik-dollar-sign"
                   :breadcrumbs="['Home' => route('dashboard'), 'Reports' => route('reports.index'), 'Expenses' => null]" />

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
        <form action="{{ route('reports.expenses') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Start Date') }}</label>
                <input type="date" name="start_date" value="{{ $startDate ?? now()->startOfMonth()->format('Y-m-d') }}" 
                       class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('End Date') }}</label>
                <input type="date" name="end_date" value="{{ $endDate ?? now()->endOfMonth()->format('Y-m-d') }}" 
                       class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Branch') }}</label>
                <select name="branch_id" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    <option value="">{{ __('All Branches') }}</option>
                    @foreach($branches ?? [] as $branch)
                        <option value="{{ $branch->id }}" {{ ($branchId ?? null) == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Category') }}</label>
                <select name="category" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    <option value="">{{ __('All Categories') }}</option>
                    @foreach($categories ?? [] as $cat)
                        <option value="{{ $cat }}" {{ ($category ?? null) == $cat ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $cat)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition">
                <i class="ik ik-search mr-2"></i> {{ __('Apply') }}
            </button>
            <a href="{{ route('reports.expenses') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="ik ik-rotate-ccw mr-2"></i> {{ __('Reset') }}
            </a>
            <a href="{{ route('reports.export', ['type' => 'expenses', 'start_date' => $startDate ?? now()->startOfMonth()->format('Y-m-d'), 'end_date' => $endDate ?? now()->endOfMonth()->format('Y-m-d')]) }}" 
               class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition ml-auto">
                <i class="ik ik-download mr-2"></i> {{ __('Export') }}
            </a>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card value="{{ number_format($summary['total_expenses'] ?? 0, 0) }}" 
                     label="{{ __('Total Expenses') }}" 
                     icon="ik ik-dollar-sign" 
                     color="red" />
        
        <x-stat-card value="{{ number_format($summary['total_transactions'] ?? 0, 0) }}" 
                     label="{{ __('Transactions') }}" 
                     icon="ik ik-file-text" 
                     color="orange" />
        
        <x-stat-card value="{{ number_format($summary['average_expense'] ?? 0, 0) }}" 
                     label="{{ __('Average Expense') }}" 
                     icon="ik ik-dollar-sign" 
                     color="purple" />
        
        <x-stat-card value="{{ number_format($summary['max_expense'] ?? 0, 0) }}" 
                     label="{{ __('Max Expense') }}" 
                     icon="ik ik-trending-up" 
                     color="red" />
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h5 class="font-semibold text-gray-700 mb-4">{{ __('Expenses by Category') }}</h5>
            <div class="space-y-3 max-h-64 overflow-y-auto">
                @foreach($byCategory ?? [] as $cat)
                    <div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ ucfirst(str_replace('_', ' ', $cat['category'])) }}</span>
                            <span class="font-medium text-gray-800">{{ number_format($cat['total'], 0) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-red-500 rounded-full h-2" style="width: {{ ($summary['total_expenses'] ?? 0) > 0 ? ($cat['total'] / ($summary['total_expenses'] ?? 1)) * 100 : 0 }}%"></div>
                        </div>
                        <div class="text-xs text-gray-400 text-right">{{ number_format($cat['percentage'] ?? 0, 1) }}%</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h5 class="font-semibold text-gray-700 mb-4">{{ __('Expenses by Branch') }}</h5>
            <div class="space-y-3">
                @foreach($byBranch ?? [] as $branch)
                    <div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ $branch['branch'] }}</span>
                            <span class="font-medium text-gray-800">{{ number_format($branch['total'], 0) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-orange-500 rounded-full h-2" style="width: {{ ($summary['total_expenses'] ?? 0) > 0 ? ($branch['total'] / ($summary['total_expenses'] ?? 1)) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Recent Expenses Table -->
    <div class="mt-6">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h5 class="font-semibold text-gray-700">{{ __('Recent Expenses') }}</h5>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-6 py-3 font-medium">{{ __('Date') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Branch') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Category') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('Amount') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Description') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($expenses ?? [] as $expense)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-gray-700">{{ $expense->created_at->format('d M Y') }}</td>
                                <td class="px-6 py-3 text-gray-700">{{ $expense->branch->name ?? 'N/A' }}</td>
                                <td class="px-6 py-3">
                                    <span class="px-2 py-1 bg-gray-100 rounded text-xs">{{ ucfirst(str_replace('_', ' ', $expense->category)) }}</span>
                                </td>
                                <td class="px-6 py-3 text-right font-medium text-red-600">{{ number_format($expense->amount, 0) }}</td>
                                <td class="px-6 py-3 text-gray-500 truncate max-w-xs">{{ $expense->description ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                                    {{ __('No expenses found for the selected period') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t border-gray-200 bg-gray-50">
                        <tr>
                            <td colspan="3" class="px-6 py-3 font-semibold text-gray-700">{{ __('Total') }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-gray-800">{{ number_format($summary['total_expenses'] ?? 0, 0) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection