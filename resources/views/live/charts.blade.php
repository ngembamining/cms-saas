@extends('layouts.main')

@section('title', 'Live Charts - Real-time Business Analytics')

@push('head')
    @vite('resources/js/charts.js')
    <style>
        .ct-chart .ct-label { fill: rgba(107, 114, 128, .9); color: rgba(107, 114, 128, .9); font-size: .75rem; }
        
        .ct-primary .ct-series-a .ct-line,
        .ct-primary .ct-series-a .ct-point { stroke: #2563eb; }
        .ct-primary .ct-series-a .ct-bar { stroke: #2563eb; }
        .ct-primary .ct-series-a .ct-area { fill: #2563eb; fill-opacity: .15; }
        
        .ct-success .ct-series-a .ct-line,
        .ct-success .ct-series-a .ct-point { stroke: #38c172; }
        .ct-success .ct-series-a .ct-bar { stroke: #38c172; }
        .ct-success .ct-series-a .ct-area { fill: #38c172; fill-opacity: .15; }
        
        .ct-danger .ct-series-a .ct-line,
        .ct-danger .ct-series-a .ct-point { stroke: #eb525d; }
        .ct-danger .ct-series-a .ct-bar { stroke: #eb525d; }
        .ct-danger .ct-series-a .ct-area { fill: #eb525d; fill-opacity: .15; }
        
        .ct-warning .ct-series-a .ct-line,
        .ct-warning .ct-series-a .ct-point { stroke: #f59e0b; }
        .ct-warning .ct-series-a .ct-bar { stroke: #f59e0b; }
        .ct-warning .ct-series-a .ct-area { fill: #f59e0b; fill-opacity: .15; }
        
        .ct-purple .ct-series-a .ct-line,
        .ct-purple .ct-series-a .ct-point { stroke: #7c3aed; }
        .ct-purple .ct-series-a .ct-bar { stroke: #7c3aed; }
        .ct-purple .ct-series-a .ct-area { fill: #7c3aed; fill-opacity: .15; }
        
        .ct-cyan .ct-series-a .ct-line,
        .ct-cyan .ct-series-a .ct-point { stroke: #06b6d4; }
        .ct-cyan .ct-series-a .ct-bar { stroke: #06b6d4; }
        .ct-cyan .ct-series-a .ct-area { fill: #06b6d4; fill-opacity: .15; }

        .ct-multi .ct-series-a .ct-line,
        .ct-multi .ct-series-a .ct-point { stroke: #2563eb; }
        .ct-multi .ct-series-b .ct-line,
        .ct-multi .ct-series-b .ct-point { stroke: #38c172; }
        .ct-multi .ct-series-c .ct-line,
        .ct-multi .ct-series-c .ct-point { stroke: #eb525d; }
        .ct-multi .ct-series-d .ct-line,
        .ct-multi .ct-series-d .ct-point { stroke: #f59e0b; }
        .ct-multi .ct-series-e .ct-line,
        .ct-multi .ct-series-e .ct-point { stroke: #7c3aed; }
        
        .ct-multi .ct-series-a .ct-area { fill: #2563eb; fill-opacity: .1; }
        .ct-multi .ct-series-b .ct-area { fill: #38c172; fill-opacity: .1; }
        .ct-multi .ct-series-c .ct-area { fill: #eb525d; fill-opacity: .1; }
        .ct-multi .ct-series-d .ct-area { fill: #f59e0b; fill-opacity: .1; }
        .ct-multi .ct-series-e .ct-area { fill: #7c3aed; fill-opacity: .1; }

        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .stat-card .stat-icon {
            opacity: 0.2;
            font-size: 2.5rem;
        }
        
        .live-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #38c172;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
            100% { opacity: 1; transform: scale(1); }
        }
        
        .filter-btn.active {
            background: #2563eb;
            color: white;
        }
        .filter-btn:not(.active):hover {
            background: #f3f4f6;
        }
        
        .table-row:hover {
            background: #f9fafb;
        }
    </style>
@endpush

@section('content')
    <x-page-header 
        title="{{ __('Live Business Analytics') }}" 
        subtitle="{{ __('Real-time performance monitoring across all branches with live transaction data and business intelligence') }}" 
        icon="fas fa-chart-line"
        :breadcrumbs="['Home' => route('dashboard'), 'Live View' => route('live.index'), 'Charts' => null]" 
    />

    {{-- Filters --}}
    <div class="mb-6 flex flex-wrap items-center gap-3 rounded-xl bg-white p-4 shadow-sm">
        <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-gray-500">{{ __('Branch:') }}</span>
            <button type="button" class="filter-btn rounded-lg px-4 py-1.5 text-sm font-medium transition-all active" data-filter="branch" data-value="all">
                {{ __('All Branches') }}
            </button>
            @foreach($branches as $branch)
                <button type="button" class="filter-btn rounded-lg px-4 py-1.5 text-sm font-medium text-gray-600 transition-all hover:bg-gray-100" data-filter="branch" data-value="{{ $branch->id }}">
                    {{ $branch->name }}
                </button>
            @endforeach
        </div>
        
        <div class="ml-auto flex items-center gap-2">
            <span class="text-sm font-medium text-gray-500">{{ __('Period:') }}</span>
            <button type="button" class="filter-btn rounded-lg px-4 py-1.5 text-sm font-medium transition-all active" data-filter="period" data-value="today">
                {{ __('Today') }}
            </button>
            <button type="button" class="filter-btn rounded-lg px-4 py-1.5 text-sm font-medium text-gray-600 transition-all hover:bg-gray-100" data-filter="period" data-value="7days">
                {{ __('7 Days') }}
            </button>
            <button type="button" class="filter-btn rounded-lg px-4 py-1.5 text-sm font-medium text-gray-600 transition-all hover:bg-gray-100" data-filter="period" data-value="30days">
                {{ __('30 Days') }}
            </button>
            <button type="button" class="filter-btn rounded-lg px-4 py-1.5 text-sm font-medium text-gray-600 transition-all hover:bg-gray-100" data-filter="period" data-value="month">
                {{ __('This Month') }}
            </button>
            <button type="button" class="filter-btn rounded-lg px-4 py-1.5 text-sm font-medium text-gray-600 transition-all hover:bg-gray-100" data-filter="period" data-value="quarter">
                {{ __('This Quarter') }}
            </button>
            
            <span class="ml-2 flex items-center gap-2 text-xs text-gray-400">
                <span class="live-indicator"></span>
                <span id="lastUpdated">Live</span>
            </span>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <div class="stat-card rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400">{{ __('Transactions') }}</p>
                    <p class="text-2xl font-bold text-gray-700" id="statTransactions">{{ number_format($stats['total_transactions'] ?? 0) }}</p>
                </div>
                <div class="stat-icon text-blue-500">
                    <i class="fas fa-exchange-alt"></i>
                </div>
            </div>
        </div>
        <div class="stat-card rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400">{{ __('Sales') }}</p>
                    <p class="text-2xl font-bold text-green-600" id="statSales">{{ number_format($stats['total_sales'] ?? 0, 0) }}</p>
                </div>
                <div class="stat-icon text-green-500">
                    <i class="fas fa-trending-up"></i>
                </div>
            </div>
        </div>
        <div class="stat-card rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400">{{ __('Purchases') }}</p>
                    <p class="text-2xl font-bold text-orange-500" id="statPurchases">{{ number_format($stats['total_purchases'] ?? 0, 0) }}</p>
                </div>
                <div class="stat-icon text-orange-500">
                    <i class="fas fa-shopping-bag"></i>
                </div>
            </div>
        </div>
        <div class="stat-card rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400">{{ __('Expenses') }}</p>
                    <p class="text-2xl font-bold text-red-500" id="statExpenses">{{ number_format($stats['total_expenses'] ?? 0, 0) }}</p>
                </div>
                <div class="stat-icon text-red-500">
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>
        </div>
        <div class="stat-card rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400">{{ __('Active Loans') }}</p>
                    <p class="text-2xl font-bold text-purple-600" id="statLoans">{{ number_format($stats['active_loans'] ?? 0) }}</p>
                </div>
                <div class="stat-icon text-purple-500">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
            </div>
        </div>
        <div class="stat-card rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400">{{ __('Profit') }}</p>
                    <p class="text-2xl font-bold text-blue-600" id="statProfit">
                        @php
                            $profit = ($stats['total_sales'] ?? 0) - ($stats['total_purchases'] ?? 0) - ($stats['total_expenses'] ?? 0);
                        @endphp
                        {{ number_format($profit, 0) }}
                    </p>
                </div>
                <div class="stat-icon text-blue-500">
                    <i class="fas fa-chart-pie"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Charts Grid --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
        {{-- Sales Overview --}}
        <x-card>
            <x-slot:header>
                <div class="flex items-center justify-between w-full">
                    <span>{{ __('Sales Overview') }}</span>
                    <a href="{{ route('live.charts.sales') }}" class="text-xs text-primary-500 hover:underline">View Details →</a>
                </div>
            </x-slot:header>
            <div id="ct-sales-chart" class="ct-chart ct-primary h-56"></div>
        </x-card>

        {{-- Purchases Overview --}}
        <x-card>
            <x-slot:header>
                <div class="flex items-center justify-between w-full">
                    <span>{{ __('Purchases Overview') }}</span>
                    <a href="{{ route('live.charts.purchases') }}" class="text-xs text-primary-500 hover:underline">View Details →</a>
                </div>
            </x-slot:header>
            <div id="ct-purchases-chart" class="ct-chart ct-cyan h-56"></div>
        </x-card>

        {{-- Sales vs Purchases --}}
        <x-card>
            <x-slot:header>
                <div class="flex items-center justify-between w-full">
                    <span>{{ __('Sales vs Purchases') }}</span>
                    <a href="{{ route('live.charts.transactions') }}" class="text-xs text-primary-500 hover:underline">View Details →</a>
                </div>
            </x-slot:header>
            <div id="ct-comparison-chart" class="ct-chart ct-multi h-56"></div>
        </x-card>

        {{-- Expenses Overview --}}
        <x-card>
            <x-slot:header>
                <div class="flex items-center justify-between w-full">
                    <span>{{ __('Expenses Overview') }}</span>
                    <a href="{{ route('live.charts.expenses') }}" class="text-xs text-primary-500 hover:underline">View Details →</a>
                </div>
            </x-slot:header>
            <div id="ct-expenses-chart" class="ct-chart ct-danger h-56"></div>
        </x-card>

        {{-- Branch Performance --}}
        <x-card>
            <x-slot:header>
                <div class="flex items-center justify-between w-full">
                    <span>{{ __('Branch Performance') }}</span>
                    <span class="text-xs text-gray-400">{{ __('Click branch for details') }}</span>
                </div>
            </x-slot:header>
            <div id="ct-branch-chart" class="ct-chart ct-success h-56"></div>
        </x-card>

        {{-- Loan Distribution --}}
        <x-card>
            <x-slot:header>
                <div class="flex items-center justify-between w-full">
                    <span>{{ __('Loan Distribution') }}</span>
                    <a href="{{ route('live.charts.loans') }}" class="text-xs text-primary-500 hover:underline">View Details →</a>
                </div>
            </x-slot:header>
            <div id="ct-loans-chart" class="ct-chart ct-purple h-56"></div>
        </x-card>
    </div>

    {{-- Branch Performance Table --}}
    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- Branch Performance Table --}}
        <x-card class="xl:col-span-2">
            <x-slot:header>
                <div class="flex items-center justify-between w-full">
                    <span>{{ __('Branch Performance Details') }}</span>
                    <span class="text-xs text-gray-400">{{ $period ?? 'Today' }}</span>
                </div>
            </x-slot:header>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="py-2 text-left font-medium text-gray-500">{{ __('Branch') }}</th>
                            <th class="py-2 text-right font-medium text-gray-500">{{ __('Sales') }}</th>
                            <th class="py-2 text-right font-medium text-gray-500">{{ __('Purchases') }}</th>
                            <th class="py-2 text-right font-medium text-gray-500">{{ __('Expenses') }}</th>
                            <th class="py-2 text-right font-medium text-gray-500">{{ __('Profit') }}</th>
                            <th class="py-2 text-right font-medium text-gray-500">{{ __('Loans') }}</th>
                            <th class="py-2 text-right font-medium text-gray-500">{{ __('Cash Balance') }}</th>
                        </tr>
                    </thead>
                    <tbody id="branchTableBody">
                        @foreach($branchPerformance ?? [] as $branch)
                            <tr class="table-row cursor-pointer border-b border-gray-50 transition-colors" data-branch-id="{{ $branch['id'] }}" onclick="window.location.href='{{ route('live.charts.branch', $branch['id']) }}'">
                                <td class="py-2 font-medium text-gray-700">{{ $branch['name'] }}</td>
                                <td class="py-2 text-right text-green-600">{{ number_format($branch['sales'] ?? 0, 0) }}</td>
                                <td class="py-2 text-right text-orange-500">{{ number_format($branch['purchases'] ?? 0, 0) }}</td>
                                <td class="py-2 text-right text-red-500">{{ number_format($branch['expenses'] ?? 0, 0) }}</td>
                                <td class="py-2 text-right font-semibold {{ ($branch['profit'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-500' }}">
                                    {{ number_format($branch['profit'] ?? 0, 0) }}
                                </td>
                                <td class="py-2 text-right text-purple-600">{{ number_format($branch['loan_amount'] ?? 0, 0) }}</td>
                                <td class="py-2 text-right text-blue-600">{{ number_format($branch['cash_balance'] ?? 0, 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        {{-- Quick Stats / Recent Activity --}}
        <x-card>
            <x-slot:header>
                <div class="flex items-center justify-between w-full">
                    <span>{{ __('Recent Activity') }}</span>
                    <button type="button" onclick="refreshData()" class="text-xs text-primary-500 hover:underline">
                        <i class="fas fa-sync-alt mr-1"></i> Refresh
                    </button>
                </div>
            </x-slot:header>
            <div class="space-y-3">
                @foreach($recentTransactions ?? [] as $tx)
                    <div class="flex items-start gap-3 rounded-lg bg-gray-50 p-3">
                        <div class="mt-0.5">
                            @if($tx->transaction_type == 'gold_sale')
                                <span class="inline-block h-2 w-2 rounded-full bg-green-500"></span>
                            @elseif($tx->transaction_type == 'gold_purchase')
                                <span class="inline-block h-2 w-2 rounded-full bg-orange-500"></span>
                            @elseif($tx->transaction_type == 'expense')
                                <span class="inline-block h-2 w-2 rounded-full bg-red-500"></span>
                            @else
                                <span class="inline-block h-2 w-2 rounded-full bg-blue-500"></span>
                            @endif
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-700">
                                <span class="font-medium">{{ $tx->transaction_code }}</span>
                                {{ $tx->description ?? str_replace('_', ' ', $tx->transaction_type) }}
                            </p>
                            <p class="text-xs text-gray-400">
                                {{ $tx->branch->name ?? 'N/A' }} • {{ $tx->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-semibold {{ $tx->transaction_type == 'gold_sale' ? 'text-green-600' : 'text-gray-700' }}">
                                {{ number_format($tx->amount, 0) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>
    </div>

    {{-- Quick Links --}}
    <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
        <a href="{{ route('live.charts.transactions') }}" class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 transition hover:shadow-md">
            <div class="rounded-lg bg-blue-50 p-2">
                <i class="fas fa-exchange-alt text-blue-500"></i>
            </div>
            <span class="text-sm font-medium text-gray-700">{{ __('Transactions') }}</span>
        </a>
        <a href="{{ route('live.charts.sales') }}" class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 transition hover:shadow-md">
            <div class="rounded-lg bg-green-50 p-2">
                <i class="fas fa-trending-up text-green-500"></i>
            </div>
            <span class="text-sm font-medium text-gray-700">{{ __('Sales') }}</span>
        </a>
        <a href="{{ route('live.charts.purchases') }}" class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 transition hover:shadow-md">
            <div class="rounded-lg bg-orange-50 p-2">
                <i class="fas fa-shopping-bag text-orange-500"></i>
            </div>
            <span class="text-sm font-medium text-gray-700">{{ __('Purchases') }}</span>
        </a>
        <a href="{{ route('live.charts.expenses') }}" class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 transition hover:shadow-md">
            <div class="rounded-lg bg-red-50 p-2">
                <i class="fas fa-dollar-sign text-red-500"></i>
            </div>
            <span class="text-sm font-medium text-gray-700">{{ __('Expenses') }}</span>
        </a>
        <a href="{{ route('live.charts.loans') }}" class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 transition hover:shadow-md">
            <div class="rounded-lg bg-purple-50 p-2">
                <i class="fas fa-hand-holding-usd text-purple-500"></i>
            </div>
            <span class="text-sm font-medium text-gray-700">{{ __('Loans') }}</span>
        </a>
        <a href="{{ route('live.charts.realtime') }}" class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 transition hover:shadow-md">
            <div class="rounded-lg bg-cyan-50 p-2">
                <i class="fas fa-sync-alt text-cyan-500"></i>
            </div>
            <span class="text-sm font-medium text-gray-700">{{ __('Real-time') }}</span>
        </a>
    </div>

    @push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Chart data
            var salesData = {!! json_encode($chartData['sales'] ?? []) !!};
            var purchasesData = {!! json_encode($chartData['purchases'] ?? []) !!};
            var expensesData = {!! json_encode($chartData['expenses'] ?? []) !!};
            var labels = {!! json_encode($chartData['labels'] ?? []) !!};
            var branchData = {!! json_encode($branchPerformance ?? []) !!};
            var loanLabels = {!! json_encode($chartData['loans']['labels'] ?? ['Pending', 'Approved', 'Completed', 'Rejected']) !!};
            var loanValues = {!! json_encode($chartData['loans']['values'] ?? [0, 0, 0, 0]) !!};

            function initCharts() {
                // Sales Chart
                var salesChart = document.getElementById('ct-sales-chart');
                if (salesChart && typeof Chartist !== 'undefined') {
                    new Chartist.Line('#ct-sales-chart', {
                        labels: labels,
                        series: [salesData]
                    }, {
                        fullWidth: true,
                        chartPadding: { right: 20 },
                        showArea: true,
                        low: 0,
                    });
                }

                // Purchases Chart
                var purchasesChart = document.getElementById('ct-purchases-chart');
                if (purchasesChart && typeof Chartist !== 'undefined') {
                    new Chartist.Line('#ct-purchases-chart', {
                        labels: labels,
                        series: [purchasesData]
                    }, {
                        fullWidth: true,
                        chartPadding: { right: 20 },
                        showArea: true,
                        low: 0,
                    });
                }

                // Comparison Chart
                var comparisonChart = document.getElementById('ct-comparison-chart');
                if (comparisonChart && typeof Chartist !== 'undefined') {
                    new Chartist.Line('#ct-comparison-chart', {
                        labels: labels,
                        series: [
                            { name: 'Sales', data: salesData },
                            { name: 'Purchases', data: purchasesData }
                        ]
                    }, {
                        fullWidth: true,
                        chartPadding: { right: 20 },
                        showArea: true,
                        low: 0,
                    });
                }

                // Expenses Chart
                var expensesChart = document.getElementById('ct-expenses-chart');
                if (expensesChart && typeof Chartist !== 'undefined') {
                    new Chartist.Line('#ct-expenses-chart', {
                        labels: labels,
                        series: [expensesData]
                    }, {
                        fullWidth: true,
                        chartPadding: { right: 20 },
                        showArea: true,
                        low: 0,
                    });
                }

                // Branch Performance Chart
                var branchChart = document.getElementById('ct-branch-chart');
                if (branchChart && typeof Chartist !== 'undefined') {
                    var branchLabels = branchData.map(function(b) { return b.name; });
                    var branchSales = branchData.map(function(b) { return b.sales || 0; });
                    
                    new Chartist.Bar('#ct-branch-chart', {
                        labels: branchLabels,
                        series: [branchSales]
                    }, {
                        chartPadding: { right: 20 },
                        axisX: {
                            labelInterpolationFnc: function(value) {
                                return value.length > 10 ? value.substring(0, 8) + '...' : value;
                            }
                        }
                    });
                }

                // Loans Chart
                var loansChart = document.getElementById('ct-loans-chart');
                if (loansChart && typeof Chartist !== 'undefined') {
                    new Chartist.Pie('#ct-loans-chart', {
                        labels: loanLabels,
                        series: loanValues
                    }, {
                        labelInterpolationFnc: function(value) {
                            return value > 0 ? value : '';
                        }
                    });
                }
            }

            // Initialize charts
            if (typeof Chartist !== 'undefined') {
                initCharts();
            } else {
                document.addEventListener('charts:loaded', initCharts);
            }

            // Auto-refresh every 30 seconds
            setInterval(function() {
                refreshData();
            }, 30000);

            // Filters
            document.querySelectorAll('.filter-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var filter = this.dataset.filter;
                    var value = this.dataset.value;
                    
                    document.querySelectorAll('.filter-btn[data-filter="' + filter + '"]').forEach(function(b) {
                        b.classList.remove('active');
                        b.classList.add('text-gray-600');
                    });
                    this.classList.add('active');
                    this.classList.remove('text-gray-600');
                    
                    var params = new URLSearchParams();
                    document.querySelectorAll('.filter-btn.active').forEach(function(active) {
                        params.set(active.dataset.filter, active.dataset.value);
                    });
                    
                    window.location.href = window.location.pathname + '?' + params.toString();
                });
            });

            // Update last updated time
            var lastUpdated = document.getElementById('lastUpdated');
            if (lastUpdated) {
                lastUpdated.textContent = new Date().toLocaleTimeString();
            }
            
            setInterval(function() {
                var el = document.getElementById('lastUpdated');
                if (el) {
                    el.textContent = new Date().toLocaleTimeString();
                }
            }, 60000);
        });

        // Refresh data function
        function refreshData() {
            var btn = document.querySelector('[onclick="refreshData()"]');
            if (btn) {
                var original = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Refreshing...';
                btn.disabled = true;
                
                var params = new URLSearchParams();
                document.querySelectorAll('.filter-btn.active').forEach(function(active) {
                    params.set(active.dataset.filter, active.dataset.value);
                });
                
                fetch(window.location.pathname + '?' + params.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(function(response) { return response.text(); })
                .then(function(html) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(html, 'text/html');
                    
                    var statTransactions = doc.getElementById('statTransactions');
                    var statSales = doc.getElementById('statSales');
                    var statPurchases = doc.getElementById('statPurchases');
                    var statExpenses = doc.getElementById('statExpenses');
                    var statLoans = doc.getElementById('statLoans');
                    var statProfit = doc.getElementById('statProfit');
                    var branchTableBody = doc.getElementById('branchTableBody');
                    
                    if (statTransactions) document.getElementById('statTransactions').textContent = statTransactions.textContent;
                    if (statSales) document.getElementById('statSales').textContent = statSales.textContent;
                    if (statPurchases) document.getElementById('statPurchases').textContent = statPurchases.textContent;
                    if (statExpenses) document.getElementById('statExpenses').textContent = statExpenses.textContent;
                    if (statLoans) document.getElementById('statLoans').textContent = statLoans.textContent;
                    if (statProfit) document.getElementById('statProfit').textContent = statProfit.textContent;
                    
                    if (branchTableBody) {
                        document.getElementById('branchTableBody').innerHTML = branchTableBody.innerHTML;
                    }
                    
                    var lastUpdated = document.getElementById('lastUpdated');
                    if (lastUpdated) {
                        lastUpdated.textContent = new Date().toLocaleTimeString();
                    }
                    
                    // Reinitialize charts
                    if (typeof Chartist !== 'undefined') {
                        document.dispatchEvent(new Event('charts:loaded'));
                    }
                })
                .catch(function() {
                    window.location.reload();
                })
                .finally(function() {
                    btn.innerHTML = original;
                    btn.disabled = false;
                });
            } else {
                window.location.reload();
            }
        }
    </script>
    @endpush
@endsection