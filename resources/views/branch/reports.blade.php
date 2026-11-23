@extends('layouts.main')
@section('title', 'Branch Reports')
@section('content')

<div class="max-w-7xl mx-auto">
    <x-page-header title="{{ $branch->name }} - {{ __('Reports') }}" 
                   subtitle="{{ __('View branch reports and performance') }}" 
                   icon="ik ik-pie-chart"
                   :breadcrumbs="['Home' => route('dashboard'), 'My Branch' => route('branch.dashboard'), 'Reports' => null]" />

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
        <form action="{{ route('branch.reports') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Month') }}</label>
                <select name="month" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    @foreach($months ?? range(1, 12) as $m)
                        <option value="{{ $m }}" {{ ($month ?? now()->month) == $m ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Year') }}</label>
                <select name="year" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    @foreach($years ?? range(now()->year - 5, now()->year) as $y)
                        <option value="{{ $y }}" {{ ($year ?? now()->year) == $y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition">
                <i class="ik ik-search mr-2"></i> {{ __('Apply') }}
            </button>
            <a href="{{ route('branch.reports') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="ik ik-rotate-ccw mr-2"></i> {{ __('Reset') }}
            </a>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card value="{{ number_format($summary['total_sales'] ?? 0, 0) }}" 
                     label="{{ __('Total Sales') }}" 
                     icon="ik ik-trending-up" 
                     color="green" />
        
        <x-stat-card value="{{ number_format($summary['total_purchases'] ?? 0, 0) }}" 
                     label="{{ __('Total Purchases') }}" 
                     icon="ik ik-shopping-bag" 
                     color="blue" />
        
        <x-stat-card value="{{ number_format($summary['total_expenses'] ?? 0, 0) }}" 
                     label="{{ __('Total Expenses') }}" 
                     icon="ik ik-dollar-sign" 
                     color="red" />
        
        <x-stat-card value="{{ number_format($summary['net_profit'] ?? 0, 0) }}" 
                     label="{{ __('Net Profit') }}" 
                     icon="ik ik-trending-up" 
                     color="{{ ($summary['net_profit'] ?? 0) >= 0 ? 'purple' : 'red' }}" />
    </div>

    <!-- Daily Summary Table -->
    <div class="mt-6">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h5 class="font-semibold text-gray-700">{{ __('Daily Summary') }}</h5>
                <span class="text-sm text-gray-500">
                    {{ date('F', mktime(0, 0, 0, $month ?? now()->month, 1)) }} {{ $year ?? now()->year }}
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-6 py-3 font-medium">{{ __('Date') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('Sales') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('Purchases') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('Expenses') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('Net') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($dailySummary ?? [] as $day)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-gray-700">{{ \Carbon\Carbon::parse($day['date'])->format('d M Y') }}</td>
                                <td class="px-6 py-3 text-right font-medium text-green-600">{{ number_format($day['sales'], 0) }}</td>
                                <td class="px-6 py-3 text-right font-medium text-blue-600">{{ number_format($day['purchases'], 0) }}</td>
                                <td class="px-6 py-3 text-right font-medium text-red-600">{{ number_format($day['expenses'], 0) }}</td>
                                <td class="px-6 py-3 text-right font-medium {{ ($day['sales'] - $day['expenses']) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format($day['sales'] - $day['expenses'], 0) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                                    {{ __('No data found for the selected period') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t border-gray-200 bg-gray-50">
                        <tr>
                            <td class="px-6 py-3 font-semibold text-gray-700">{{ __('Total') }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-green-600">{{ number_format($summary['total_sales'] ?? 0, 0) }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-blue-600">{{ number_format($summary['total_purchases'] ?? 0, 0) }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-red-600">{{ number_format($summary['total_expenses'] ?? 0, 0) }}</td>
                            <td class="px-6 py-3 text-right font-semibold {{ ($summary['net_profit'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format($summary['net_profit'] ?? 0, 0) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Stats Summary -->
    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h5 class="font-semibold text-gray-700 mb-2">{{ __('Transactions') }}</h5>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Sales</span>
                    <span class="font-medium text-gray-800">{{ $summary['sales_count'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Purchases</span>
                    <span class="font-medium text-gray-800">{{ $summary['purchases_count'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Expenses</span>
                    <span class="font-medium text-gray-800">{{ $summary['expenses_count'] ?? 0 }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h5 class="font-semibold text-gray-700 mb-2">{{ __('Averages') }}</h5>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Avg Sale</span>
                    <span class="font-medium text-gray-800">
                        {{ ($summary['sales_count'] ?? 0) > 0 ? number_format(($summary['total_sales'] ?? 0) / ($summary['sales_count'] ?? 1), 0) : 0 }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Avg Purchase</span>
                    <span class="font-medium text-gray-800">
                        {{ ($summary['purchases_count'] ?? 0) > 0 ? number_format(($summary['total_purchases'] ?? 0) / ($summary['purchases_count'] ?? 1), 0) : 0 }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Avg Expense</span>
                    <span class="font-medium text-gray-800">
                        {{ ($summary['expenses_count'] ?? 0) > 0 ? number_format(($summary['total_expenses'] ?? 0) / ($summary['expenses_count'] ?? 1), 0) : 0 }}
                    </span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h5 class="font-semibold text-gray-700 mb-2">{{ __('Profitability') }}</h5>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Gross Profit</span>
                    <span class="font-medium text-green-600">{{ number_format(($summary['total_sales'] ?? 0) - ($summary['total_purchases'] ?? 0), 0) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Net Profit</span>
                    <span class="font-medium {{ ($summary['net_profit'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($summary['net_profit'] ?? 0, 0) }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Profit Margin</span>
                    <span class="font-medium text-gray-800">
                        {{ ($summary['total_sales'] ?? 0) > 0 ? number_format((($summary['net_profit'] ?? 0) / ($summary['total_sales'] ?? 1)) * 100, 1) : 0 }}%
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection