@extends('layouts.main')

@section('title', 'Financial Reports')

@section('content')
<x-page-header title="Financial Reports" subtitle="View financial reports and analytics" icon="ik ik-pie-chart"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Reports' => null]" />

<!-- Statistics Cards -->
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
    
    <x-stat-card value="{{ number_format($totalExpenses, 0) }}" 
                 label="Total Expenses" 
                 icon="ik ik-dollar-sign" 
                 color="danger" 
                 subtitle="TZS" />
    
    <x-stat-card value="{{ number_format($totalSales - $totalPurchases - $totalExpenses, 0) }}" 
                 label="Net Profit" 
                 icon="ik ik-bar-chart-2" 
                 color="accent" 
                 subtitle="TZS" />
</div>

<!-- Filter Section -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form method="GET" action="{{ route('finance.reports.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Branch</label>
            <select name="branch_id" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                <option value="">All Branches</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ $branchFilter == $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
        </div>
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
        <div class="flex items-end gap-2">
            <button type="submit" class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-600">
                <i class="ik ik-search"></i> Apply Filters
            </button>
            <a href="{{ route('finance.reports.index') }}" class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-300">
                <i class="ik ik-rotate-ccw"></i> Reset
            </a>
        </div>
    </form>
</div>

<!-- Quick Reports -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
    <a href="{{ route('finance.reports.daily') }}" class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition">
        <div class="flex items-center gap-3">
            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                <i class="ik ik-calendar text-2xl"></i>
            </span>
            <div>
                <h4 class="font-semibold text-gray-800">Daily Report</h4>
                <p class="text-sm text-gray-500">View today's transactions</p>
            </div>
        </div>
    </a>
    
    <a href="{{ route('finance.reports.weekly') }}" class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition">
        <div class="flex items-center gap-3">
            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-100 text-green-600">
                <i class="ik ik-bar-chart text-2xl"></i>
            </span>
            <div>
                <h4 class="font-semibold text-gray-800">Weekly Report</h4>
                <p class="text-sm text-gray-500">View weekly performance</p>
            </div>
        </div>
    </a>
    
    <a href="{{ route('finance.reports.monthly') }}" class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition">
        <div class="flex items-center gap-3">
            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                <i class="ik ik-pie-chart text-2xl"></i>
            </span>
            <div>
                <h4 class="font-semibold text-gray-800">Monthly Report</h4>
                <p class="text-sm text-gray-500">View monthly performance</p>
            </div>
        </div>
    </a>
    
    <a href="{{ route('finance.reports.branch') }}" class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition">
        <div class="flex items-center gap-3">
            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-100 text-purple-600">
                <i class="fas fa-store text-2xl"></i>
            </span>
            <div>
                <h4 class="font-semibold text-gray-800">Branch Report</h4>
                <p class="text-sm text-gray-500">View branch performance</p>
            </div>
        </div>
    </a>
    
    <a href="{{ route('finance.reports.gold') }}" class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition">
        <div class="flex items-center gap-3">
            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                <i class="fas fa-crown text-2xl"></i>
            </span>
            <div>
                <h4 class="font-semibold text-gray-800">Gold Summary</h4>
                <p class="text-sm text-gray-500">View gold transactions</p>
            </div>
        </div>
    </a>
    
    <a href="{{ route('finance.reports.cash-flow') }}" class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition">
        <div class="flex items-center gap-3">
            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-100 text-red-600">
                <i class="ik ik-dollar-sign text-2xl"></i>
            </span>
            <div>
                <h4 class="font-semibold text-gray-800">Cash Flow</h4>
                <p class="text-sm text-gray-500">View cash flow analysis</p>
            </div>
        </div>
    </a>
</div>
@endsection