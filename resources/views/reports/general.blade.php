@extends('layouts.main')
@section('title', 'General Report')
@section('content')

<div class="max-w-7xl mx-auto">
    <x-page-header title="{{ __('General Report') }}" 
                   subtitle="{{ __('Business overview summary') }}" 
                   icon="ik ik-bar-chart-2"
                   :breadcrumbs="['Home' => route('dashboard'), 'Reports' => route('reports.index'), 'General' => null]" />

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
        <form action="{{ route('reports.general') }}" method="GET" class="flex flex-wrap items-end gap-4">
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
            <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition">
                <i class="ik ik-search mr-2"></i> {{ __('Apply') }}
            </button>
            <a href="{{ route('reports.general') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="ik ik-rotate-ccw mr-2"></i> {{ __('Reset') }}
            </a>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card value="{{ number_format($data['sales']['total'] ?? 0, 0) }}" 
                     label="{{ __('Total Sales') }}" 
                     icon="ik ik-trending-up" 
                     color="green" />
        
        <x-stat-card value="{{ number_format($data['purchases']['total'] ?? 0, 0) }}" 
                     label="{{ __('Total Purchases') }}" 
                     icon="ik ik-shopping-bag" 
                     color="blue" />
        
        <x-stat-card value="{{ number_format($data['expenses']['total'] ?? 0, 0) }}" 
                     label="{{ __('Total Expenses') }}" 
                     icon="ik ik-dollar-sign" 
                     color="red" />
        
        <x-stat-card value="{{ number_format($data['profit'] ?? 0, 0) }}" 
                     label="{{ __('Net Profit') }}" 
                     icon="ik ik-trending-up" 
                     color="purple" />
    </div>

    <!-- Detailed Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
        <!-- Sales Details -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h5 class="font-semibold text-gray-700 mb-4 flex items-center">
                <i class="ik ik-trending-up text-green-500 mr-2"></i> Sales
            </h5>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Total Amount</span>
                    <span class="font-medium text-gray-800">{{ number_format($data['sales']['total'] ?? 0, 0) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Total Gold Sold</span>
                    <span class="font-medium text-gray-800">{{ number_format($data['sales']['gold'] ?? 0, 2) }} g</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Transactions</span>
                    <span class="font-medium text-gray-800">{{ $data['sales']['count'] ?? 0 }}</span>
                </div>
            </div>
        </div>

        <!-- Purchases Details -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h5 class="font-semibold text-gray-700 mb-4 flex items-center">
                <i class="ik ik-shopping-bag text-blue-500 mr-2"></i> Purchases
            </h5>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Total Amount</span>
                    <span class="font-medium text-gray-800">{{ number_format($data['purchases']['total'] ?? 0, 0) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Total Gold Purchased</span>
                    <span class="font-medium text-gray-800">{{ number_format($data['purchases']['gold'] ?? 0, 2) }} g</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Transactions</span>
                    <span class="font-medium text-gray-800">{{ $data['purchases']['count'] ?? 0 }}</span>
                </div>
            </div>
        </div>

        <!-- Loans Details -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h5 class="font-semibold text-gray-700 mb-4 flex items-center">
                <i class="ik ik-file-text text-orange-500 mr-2"></i> Loans
            </h5>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Total Loans</span>
                    <span class="font-medium text-gray-800">{{ number_format($data['loans']['total'] ?? 0, 0) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Total Count</span>
                    <span class="font-medium text-gray-800">{{ $data['loans']['count'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Pending</span>
                    <span class="font-medium text-amber-600">{{ $data['loans']['pending'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Approved</span>
                    <span class="font-medium text-green-600">{{ $data['loans']['approved'] ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Profit/Loss Summary -->
    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h5 class="font-semibold text-gray-700 mb-4">{{ __('Revenue vs Expenses') }}</h5>
            <div class="space-y-3">
                <div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Revenue (Sales)</span>
                        <span class="font-medium text-green-600">{{ number_format($data['sales']['total'] ?? 0, 0) }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                        <div class="bg-green-500 rounded-full h-2" style="width: {{ ($data['sales']['total'] ?? 0) > 0 ? 100 : 0 }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Expenses</span>
                        <span class="font-medium text-red-600">{{ number_format($data['expenses']['total'] ?? 0, 0) }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                        <div class="bg-red-500 rounded-full h-2" style="width: {{ ($data['sales']['total'] ?? 0) > 0 ? (($data['expenses']['total'] ?? 0) / ($data['sales']['total'] ?? 1)) * 100 : 0 }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm font-semibold">
                        <span class="text-gray-700">Net Profit</span>
                        <span class="{{ ($data['profit'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($data['profit'] ?? 0, 0) }}
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                        <div class="{{ ($data['profit'] ?? 0) >= 0 ? 'bg-green-500' : 'bg-red-500' }} rounded-full h-2" 
                             style="width: {{ ($data['sales']['total'] ?? 0) > 0 ? min(abs(($data['profit'] ?? 0) / ($data['sales']['total'] ?? 1) * 100), 100) : 0 }}%">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h5 class="font-semibold text-gray-700 mb-4">{{ __('Key Metrics') }}</h5>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-green-600">
                        {{ ($data['sales']['count'] ?? 0) + ($data['purchases']['count'] ?? 0) }}
                    </div>
                    <div class="text-xs text-gray-500">Total Transactions</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-blue-600">
                        {{ number_format(($data['sales']['gold'] ?? 0) + ($data['purchases']['gold'] ?? 0), 2) }}g
                    </div>
                    <div class="text-xs text-gray-500">Total Gold</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-purple-600">
                        {{ number_format(($data['sales']['total'] ?? 0) / max(($data['sales']['count'] ?? 1), 1), 0) }}
                    </div>
                    <div class="text-xs text-gray-500">Avg Sale Amount</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-orange-600">
                        {{ $data['loans']['pending'] ?? 0 }}
                    </div>
                    <div class="text-xs text-gray-500">Pending Loans</div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection