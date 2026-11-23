@extends('layouts.main')
@section('title', 'Reports Dashboard')
@section('content')

<div class="max-w-7xl mx-auto">
    <x-page-header title="{{ __('Reports Dashboard') }}" 
                   subtitle="{{ __('View and generate business reports') }}" 
                   icon="ik ik-bar-chart-2"
                   :breadcrumbs="['Home' => route('dashboard'), 'Reports' => null]" />

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card value="{{ number_format($stats['sales'] ?? 0, 0) }}" 
                     label="{{ __('Total Sales (MTD)') }}" 
                     icon="ik ik-trending-up" 
                     color="green" />
        
        <x-stat-card value="{{ number_format($stats['purchases'] ?? 0, 0) }}" 
                     label="{{ __('Total Purchases (MTD)') }}" 
                     icon="ik ik-shopping-bag" 
                     color="blue" />
        
        <x-stat-card value="{{ number_format($stats['expenses'] ?? 0, 0) }}" 
                     label="{{ __('Total Expenses (MTD)') }}" 
                     icon="ik ik-dollar-sign" 
                     color="red" />
        
        <x-stat-card value="{{ number_format($stats['profit'] ?? 0, 0) }}" 
                     label="{{ __('Net Profit (MTD)') }}" 
                     icon="ik ik-trending-up" 
                     color="purple" />
    </div>

    <!-- Report Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-6">
        <a href="{{ route('reports.sales') }}" class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-lg font-semibold text-gray-700">Sales Report</h4>
                    <p class="text-sm text-gray-400 mt-1">View all gold sales</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="ik ik-trending-up text-green-500 text-xl"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('reports.purchases') }}" class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-lg font-semibold text-gray-700">Purchases Report</h4>
                    <p class="text-sm text-gray-400 mt-1">View all gold purchases</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="ik ik-shopping-bag text-blue-500 text-xl"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('reports.expenses') }}" class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-lg font-semibold text-gray-700">Expenses Report</h4>
                    <p class="text-sm text-gray-400 mt-1">View all expenses</p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="ik ik-dollar-sign text-red-500 text-xl"></i>
                </div>
            </div>
        </a>

        <a href="{{ route('reports.general') }}" class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-lg font-semibold text-gray-700">General Report</h4>
                    <p class="text-sm text-gray-400 mt-1">Business overview summary</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="ik ik-bar-chart-2 text-purple-500 text-xl"></i>
                </div>
            </div>
        </a>
    </div>
</div>

@endsection