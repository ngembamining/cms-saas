@extends('layouts.main')

@section('title', 'Monthly Report')

@section('content')
<x-page-header title="Monthly Report" subtitle="View monthly performance" icon="ik ik-pie-chart"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Reports' => route('finance.reports.index'), 'Monthly' => null]" />

<div class="max-w-6xl mx-auto">
    <!-- Filter -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('finance.reports.monthly') }}" class="flex gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Month</label>
                <input type="month" name="month" value="{{ $month }}" 
                       class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            </div>
            <button type="submit" class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-600">
                <i class="ik ik-search"></i> View
            </button>
            <a href="{{ route('finance.reports.monthly') }}" class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-300">
                <i class="ik ik-rotate-ccw"></i> This Month
            </a>
        </form>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4 mb-6">
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
        
        <x-stat-card value="{{ number_format($totalSales - $totalPurchases, 0) }}" 
                     label="Net Profit" 
                     icon="ik ik-bar-chart-2" 
                     color="accent" 
                     subtitle="TZS" />
        
        <x-stat-card value="{{ $transactions->count() }}" 
                     label="Transactions" 
                     icon="ik ik-repeat" 
                     color="blue" />
    </div>

    <!-- Daily Breakdown -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Daily Breakdown - {{ \Carbon\Carbon::parse($startDate)->format('F Y') }}</h3>
        <div class="overflow-x-auto max-h-96 overflow-y-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 sticky top-0 bg-white">
                        <th class="px-3 py-2 font-medium">Date</th>
                        <th class="px-3 py-2 font-medium">Sales (TZS)</th>
                        <th class="px-3 py-2 font-medium">Purchases (TZS)</th>
                        <th class="px-3 py-2 font-medium">Net</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($dailyData as $day)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 text-gray-700">{{ \Carbon\Carbon::parse($day['date'])->format('d M') }}</td>
                        <td class="px-3 py-2 font-semibold text-green-600">{{ number_format($day['sales'], 0) }}</td>
                        <td class="px-3 py-2 font-semibold text-red-600">{{ number_format($day['purchases'], 0) }}</td>
                        <td class="px-3 py-2 font-semibold {{ ($day['sales'] - $day['purchases']) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($day['sales'] - $day['purchases'], 0) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection