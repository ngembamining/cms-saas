@extends('layouts.main')

@section('title', 'Expense Reports')

@section('content')
<x-page-header title="Expense Reports" subtitle="View expense reports and analytics" icon="ik ik-pie-chart"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Expenses' => route('finance.expenses.index'), 'Reports' => null]" />

<div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
    <!-- Daily Report -->
    <a href="{{ route('finance.expenses.reports.daily') }}" class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition">
        <div class="flex items-center gap-3">
            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                <i class="ik ik-calendar text-2xl"></i>
            </span>
            <div>
                <h4 class="font-semibold text-gray-800">Daily Report</h4>
                <p class="text-sm text-gray-500">View today's expenses</p>
            </div>
        </div>
    </a>
    
    <!-- Weekly Report -->
    <a href="{{ route('finance.expenses.reports.weekly') }}" class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition">
        <div class="flex items-center gap-3">
            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-100 text-green-600">
                <i class="ik ik-bar-chart text-2xl"></i>
            </span>
            <div>
                <h4 class="font-semibold text-gray-800">Weekly Report</h4>
                <p class="text-sm text-gray-500">View weekly expenses</p>
            </div>
        </div>
    </a>
    
    <!-- Monthly Report -->
    <a href="{{ route('finance.expenses.reports.monthly') }}" class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition">
        <div class="flex items-center gap-3">
            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                <i class="ik ik-pie-chart text-2xl"></i>
            </span>
            <div>
                <h4 class="font-semibold text-gray-800">Monthly Report</h4>
                <p class="text-sm text-gray-500">View monthly expenses</p>
            </div>
        </div>
    </a>
    
    <!-- Category Report -->
    <a href="{{ route('finance.expenses.reports.category') }}" class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition">
        <div class="flex items-center gap-3">
            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-100 text-purple-600">
                <i class="ik ik-grid text-2xl"></i>
            </span>
            <div>
                <h4 class="font-semibold text-gray-800">Category Report</h4>
                <p class="text-sm text-gray-500">View expenses by category</p>
            </div>
        </div>
    </a>
    
    <!-- Branch Report -->
    <a href="{{ route('finance.expenses.reports.branch') }}" class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition">
        <div class="flex items-center gap-3">
            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-100 text-red-600">
                <i class="fas fa-store text-2xl"></i>
            </span>
            <div>
                <h4 class="font-semibold text-gray-800">Branch Report</h4>
                <p class="text-sm text-gray-500">View expenses by branch</p>
            </div>
        </div>
    </a>
    
    <!-- Export -->
    <a href="{{ route('finance.expenses.reports.export') }}" class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition">
        <div class="flex items-center gap-3">
            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 text-gray-600">
                <i class="ik ik-download text-2xl"></i>
            </span>
            <div>
                <h4 class="font-semibold text-gray-800">Export Report</h4>
                <p class="text-sm text-gray-500">Export expense data</p>
            </div>
        </div>
    </a>
</div>
@endsection