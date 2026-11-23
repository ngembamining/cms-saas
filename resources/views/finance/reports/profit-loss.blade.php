@extends('layouts.main')

@section('title', 'Profit & Loss')

@section('content')
<x-page-header title="Profit & Loss Statement" subtitle="View profit and loss summary" icon="ik ik-bar-chart-2"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Reports' => route('finance.reports.index'), 'Profit & Loss' => null]" />

<div class="max-w-4xl mx-auto">
    <!-- Filter -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('finance.reports.profit-loss') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">From Date</label>
                <input type="date" name="from_date" value="{{ $fromDate }}" 
                       class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">To Date</label>
                <input type="date" name="to_date" value="{{ $toDate }}" 
                       class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            </div>
            <button type="submit" class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-600">
                <i class="ik ik-search"></i> View
            </button>
            <a href="{{ route('finance.reports.profit-loss') }}" class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-300">
                <i class="ik ik-rotate-ccw"></i> This Month
            </a>
        </form>
    </div>

    <!-- P&L Statement -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Profit & Loss Statement</h3>
        <p class="text-sm text-gray-500 mb-4">{{ \Carbon\Carbon::parse($fromDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($toDate)->format('d/m/Y') }}</p>
        
        <div class="space-y-3">
            <div class="flex justify-between py-2 border-b border-gray-200">
                <span class="font-medium text-gray-700">Revenue (Gold Sales)</span>
                <span class="font-semibold text-green-600">{{ number_format($revenue, 0) }} TZS</span>
            </div>
            
            <div class="flex justify-between py-2 border-b border-gray-200">
                <span class="font-medium text-gray-700">Cost of Goods Sold (Gold Purchases)</span>
                <span class="font-semibold text-red-600">{{ number_format($costOfGoods, 0) }} TZS</span>
            </div>
            
            <div class="flex justify-between py-2 border-b border-gray-200 bg-gray-50">
                <span class="font-bold text-gray-800">Gross Profit</span>
                <span class="font-bold {{ $grossProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ number_format($grossProfit, 0) }} TZS
                </span>
            </div>
            
            <div class="flex justify-between py-2 border-b border-gray-200">
                <span class="font-medium text-gray-700">Operating Expenses</span>
                <span class="font-semibold text-red-600">{{ number_format($expenses, 0) }} TZS</span>
            </div>
            
            <div class="flex justify-between py-2 border-b-2 border-gray-300 bg-gray-100">
                <span class="font-bold text-gray-800 text-lg">Net Profit</span>
                <span class="font-bold text-lg {{ $netProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ number_format($netProfit, 0) }} TZS
                </span>
            </div>
        </div>
    </div>
</div>
@endsection