@extends('layouts.main')
@section('title', 'Dashboard')
@section('content')
    <style>
        :root {
            --primary-50: #eff6ff;
            --primary-100: #dbeafe;
            --primary-500: #3b82f6;
            --primary-600: #2563eb;
            --primary-700: #1d4ed8;
        }
        
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        }
        
        .loader {
            border: 3px solid #f3f3f3;
            border-radius: 50%;
            border-top: 3px solid var(--primary-500);
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Skeleton Loading Styles */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 4px;
        }
        
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        
        .skeleton-text {
            height: 14px;
            margin-bottom: 8px;
        }
        
        .skeleton-text-sm {
            height: 12px;
            width: 60%;
        }
        
        .skeleton-text-lg {
            height: 24px;
            width: 80%;
        }
        
        .skeleton-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
        }
        
        .skeleton-rect {
            height: 100px;
            border-radius: 8px;
        }
        
        .skeleton-table-row {
            height: 40px;
            margin-bottom: 4px;
            border-radius: 4px;
        }
        
        .skeleton-badge {
            width: 60px;
            height: 20px;
            border-radius: 12px;
        }
        
        /* Fade in animation for content */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <!-- Loading Overlay -->
    <div x-data="{ loading: false }" 
         @loading-start.window="loading = true" 
         @loading-end.window="loading = false">
        
        <div x-show="loading" 
             x-transition:enter.duration.300ms
             x-transition:leave.duration.200ms
             class="fixed inset-0 z-50 flex items-center justify-center bg-white/80 backdrop-blur-sm">
            <div class="text-center">
                <div class="loader mx-auto"></div>
                <p class="mt-3 text-sm text-gray-500">{{ __('Loading dashboard...') }}</p>
            </div>
        </div>

        <!-- Page Header -->
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ __('Dashboard') }}</h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ __('Welcome back, ' . Auth::user()->full_name ?? Auth::user()->username . '! Here is your ' . $roleLabel . ' overview') }}
                </p>
            </div>
            <div class="flex items-center gap-2 mt-3 md:mt-0">
                <!-- Period Filter Buttons -->
                <div x-data="{ period: '{{ request('period', 'month') }}' }" class="inline-flex rounded-lg border border-gray-200 bg-white p-0.5">
                    <template x-for="p in ['today', 'week', 'month', 'quarter', 'year']" :key="p">
                        <button @click="period = p; window.location.href = '{{ route('dashboard') }}?period=' + p" 
                                :class="period === p ? 'bg-primary-500 text-white' : 'text-gray-500 hover:bg-gray-100'"
                                class="rounded-md px-3 py-1.5 text-sm font-medium transition" 
                                x-text="p.charAt(0).toUpperCase() + p.slice(1)"></button>
                    </template>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex items-center gap-1 ml-2">
                    <button onclick="window.print()" 
                            class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition"
                            title="{{ __('Print') }}">
                        <i class="ik ik-printer"></i>
                    </button>
                    <button onclick="refreshDashboard()" 
                            class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition"
                            title="{{ __('Refresh') }}">
                        <i class="ik ik-rotate-cw"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Cards with Skeleton -->
        <div x-data="{ loading: true }" x-init="setTimeout(() => loading = false, 1000)">
            <!-- Skeleton Loading -->
            <div x-show="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <template x-for="i in 4" :key="i">
                    <div class="bg-white rounded-xl shadow-sm p-5">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="skeleton skeleton-text" style="width: 60%"></div>
                                <div class="skeleton skeleton-text-lg mt-2" style="width: 80%"></div>
                                <div class="skeleton skeleton-text-sm mt-2" style="width: 40%"></div>
                            </div>
                            <div class="skeleton skeleton-circle"></div>
                        </div>
                    </div>
                </template>
            </div>
            
            <!-- Actual Content -->
            <div x-show="!loading" x-transition:enter.duration.500ms x-transition:enter.opacity.delay.300ms class="fade-in">
                @if(isset($summaryStats))
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <!-- Total Revenue -->
                        <div class="stat-card bg-white rounded-xl shadow-sm p-5 hover:shadow-md transition">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="text-sm text-gray-500 font-medium">{{ __('Total Revenue') }}</p>
                                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($summaryStats['total_revenue'] ?? 0, 0) }} TZS</p>
                                    @if(isset($summaryStats['revenue_change']))
                                        <p class="text-xs {{ ($summaryStats['revenue_change'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1 flex items-center">
                                            <i class="ik ik-arrow-{{ ($summaryStats['revenue_change'] ?? 0) >= 0 ? 'up' : 'down' }} text-xs"></i>
                                            <span>{{ $summaryStats['revenue_change'] ?? 0 }}%</span>
                                            <span class="text-gray-400 ml-1">{{ __('from last period') }}</span>
                                        </p>
                                    @endif
                                </div>
                                <div class="w-12 h-12 bg-primary-50 rounded-full flex items-center justify-center text-primary-500 flex-shrink-0">
                                    <i class="ik ik-dollar-sign text-xl"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Total Sales -->
                        <div class="stat-card bg-white rounded-xl shadow-sm p-5 hover:shadow-md transition">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="text-sm text-gray-500 font-medium">{{ __('Total Sales') }}</p>
                                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($summaryStats['total_sales'] ?? 0) }}</p>
                                    @if(isset($summaryStats['sales_change']))
                                        <p class="text-xs {{ ($summaryStats['sales_change'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1 flex items-center">
                                            <i class="ik ik-arrow-{{ ($summaryStats['sales_change'] ?? 0) >= 0 ? 'up' : 'down' }} text-xs"></i>
                                            <span>{{ $summaryStats['sales_change'] ?? 0 }}%</span>
                                            <span class="text-gray-400 ml-1">{{ __('from last period') }}</span>
                                        </p>
                                    @endif
                                </div>
                                <div class="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center text-blue-500 flex-shrink-0">
                                    <i class="ik ik-shopping-bag text-xl"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Active Clients -->
                        <div class="stat-card bg-white rounded-xl shadow-sm p-5 hover:shadow-md transition">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="text-sm text-gray-500 font-medium">{{ __('Active Clients') }}</p>
                                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($summaryStats['active_clients'] ?? 0) }}</p>
                                    @if(isset($summaryStats['clients_change']))
                                        <p class="text-xs {{ ($summaryStats['clients_change'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1 flex items-center">
                                            <i class="ik ik-arrow-{{ ($summaryStats['clients_change'] ?? 0) >= 0 ? 'up' : 'down' }} text-xs"></i>
                                            <span>{{ $summaryStats['clients_change'] ?? 0 }}%</span>
                                            <span class="text-gray-400 ml-1">{{ __('from last period') }}</span>
                                        </p>
                                    @endif
                                </div>
                                <div class="w-12 h-12 bg-green-50 rounded-full flex items-center justify-center text-green-500 flex-shrink-0">
                                    <i class="ik ik-users text-xl"></i>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pending Tasks -->
                        <div class="stat-card bg-white rounded-xl shadow-sm p-5 hover:shadow-md transition">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="text-sm text-gray-500 font-medium">{{ __('Pending Tasks') }}</p>
                                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($summaryStats['pending_tasks'] ?? 0) }}</p>
                                    @if(isset($summaryStats['tasks_change']))
                                        <p class="text-xs {{ ($summaryStats['tasks_change'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }} mt-1 flex items-center">
                                            <i class="ik ik-arrow-{{ ($summaryStats['tasks_change'] ?? 0) >= 0 ? 'up' : 'down' }} text-xs"></i>
                                            <span>{{ $summaryStats['tasks_change'] ?? 0 }}%</span>
                                            <span class="text-gray-400 ml-1">{{ __('from last period') }}</span>
                                        </p>
                                    @endif
                                </div>
                                <div class="w-12 h-12 bg-yellow-50 rounded-full flex items-center justify-center text-yellow-500 flex-shrink-0">
                                    <i class="ik ik-flag text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Filter Section with Skeleton -->
        <div x-data="{ loading: true }" x-init="setTimeout(() => loading = false, 800)">
            <!-- Skeleton Loading -->
            <div x-show="loading" class="bg-white rounded-xl shadow-sm p-3 md:p-4 mb-4 md:mb-6">
                <div class="flex flex-col sm:flex-row flex-wrap gap-3 md:gap-4">
                    <div class="flex-1 min-w-[150px]">
                        <div class="skeleton skeleton-text" style="width: 40%"></div>
                        <div class="skeleton skeleton-text-lg mt-1" style="width: 100%; height: 38px;"></div>
                    </div>
                    <div class="flex-1 min-w-[150px]">
                        <div class="skeleton skeleton-text" style="width: 40%"></div>
                        <div class="skeleton skeleton-text-lg mt-1" style="width: 100%; height: 38px;"></div>
                    </div>
                    <div class="flex-1 min-w-[150px]">
                        <div class="skeleton skeleton-text" style="width: 40%"></div>
                        <div class="skeleton skeleton-text-lg mt-1" style="width: 100%; height: 38px;"></div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <div class="skeleton" style="width: 90px; height: 38px; border-radius: 8px;"></div>
                        <div class="skeleton" style="width: 90px; height: 38px; border-radius: 8px;"></div>
                    </div>
                </div>
            </div>
            
            <!-- Actual Content -->
            <div x-show="!loading" x-transition:enter.duration.500ms>
                <div class="bg-white rounded-xl shadow-sm p-3 md:p-4 mb-4 md:mb-6">
                    <form action="{{ route('dashboard') }}" method="GET" 
                          class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-end gap-3 md:gap-4">
                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Period') }}</label>
                            <select name="period" 
                                    class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                                <option value="today" {{ request('period') == 'today' ? 'selected' : '' }}>{{ __('Today') }}</option>
                                <option value="week" {{ request('period') == 'week' ? 'selected' : '' }}>{{ __('This Week') }}</option>
                                <option value="month" {{ request('period') == 'month' ? 'selected' : '' }}>{{ __('This Month') }}</option>
                                <option value="quarter" {{ request('period') == 'quarter' ? 'selected' : '' }}>{{ __('This Quarter') }}</option>
                                <option value="year" {{ request('period') == 'year' ? 'selected' : '' }}>{{ __('This Year') }}</option>
                                <option value="custom" {{ request('start_date') && request('end_date') ? 'selected' : '' }}>{{ __('Custom') }}</option>
                            </select>
                        </div>
                        
                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Start Date') }}</label>
                            <input type="date" name="start_date" value="{{ request('start_date') }}" 
                                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        </div>
                        
                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('End Date') }}</label>
                            <input type="date" name="end_date" value="{{ request('end_date') }}" 
                                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        </div>

                        @if(isset($branches) && $branches->count() > 1)
                            <div class="flex-1 min-w-[150px]">
                                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Branch') }}</label>
                                <select name="branch_id" 
                                        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                                    <option value="">{{ __('All Branches') }}</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        
                        <div class="flex flex-wrap gap-2">
                            <button type="submit" 
                                    class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition flex items-center">
                                <i class="ik ik-search mr-2"></i> {{ __('Apply') }}
                            </button>
                            <a href="{{ route('dashboard') }}" 
                               class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition flex items-center">
                                <i class="ik ik-rotate-ccw mr-2"></i> {{ __('Reset') }}
                            </a>
                        </div>
                        
                        @if(isset($periodLabel))
                            <span class="text-sm text-gray-400 sm:ml-auto">{{ $periodLabel }}</span>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <!-- Role-based Dashboard Content with Skeleton -->
        <div x-data="{ loading: true }" x-init="setTimeout(() => loading = false, 1200)">
            <!-- Skeleton Loading -->
            <div x-show="loading" class="space-y-4 md:space-y-6">
                <div class="bg-white rounded-xl shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="skeleton skeleton-text-lg" style="width: 30%"></div>
                        <div class="skeleton skeleton-text-sm" style="width: 15%"></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <template x-for="i in 3" :key="i">
                            <div class="rounded-lg p-4">
                                <div class="skeleton skeleton-text" style="width: 50%"></div>
                                <div class="skeleton skeleton-text-lg mt-2" style="width: 70%"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            
            <!-- Actual Content -->
            <div x-show="!loading" x-transition:enter.duration.500ms x-transition:enter.opacity.delay.300ms class="fade-in space-y-4 md:space-y-6">
                @php
                    $roleViews = [
                        'isDirector' => 'director',
                        'isBranchManager' => 'branch-manager',
                        'isAccountant' => 'accountant',
                        'isSecretary' => 'secretary',
                        'isHR' => 'hr',
                        'isStaff' => 'staff'
                    ];
                @endphp

                @foreach($roleViews as $roleKey => $roleView)
                    @if(isset($$roleKey) && $$roleKey)
                        @include("dashboard.partials.{$roleView}")
                    @endif
                @endforeach
            </div>
        </div>

        <!-- Recent Activity Section with Skeleton -->
        <div x-data="{ loading: true }" x-init="setTimeout(() => loading = false, 1500)">
            <!-- Skeleton Loading -->
            <div x-show="loading" class="mt-5">
                <div class="bg-white rounded-xl shadow-sm">
                    <div class="px-5 py-3 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="skeleton skeleton-text-lg" style="width: 200px;"></div>
                                <div class="skeleton skeleton-text-sm mt-1" style="width: 150px;"></div>
                            </div>
                            <div class="skeleton skeleton-text-sm" style="width: 80px;"></div>
                        </div>
                    </div>
                    <div class="overflow-x-auto p-5">
                        <div class="space-y-2">
                            <template x-for="i in 5" :key="i">
                                <div class="flex items-center gap-4">
                                    <div class="skeleton skeleton-text" style="width: 80px;"></div>
                                    <div class="skeleton skeleton-text" style="width: 100px;"></div>
                                    <div class="skeleton skeleton-text" style="width: 120px;"></div>
                                    <div class="skeleton skeleton-text" style="width: 100px;"></div>
                                    <div class="skeleton skeleton-badge"></div>
                                    <div class="skeleton skeleton-text" style="width: 40px;"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actual Content -->
            <div x-show="!loading" x-transition:enter.duration.500ms x-transition:enter.opacity.delay.300ms class="fade-in">
                @if(isset($recentActivities))
                    <div class="mt-5">
                        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition">
                            <div class="px-5 py-3 border-b border-gray-100">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-800">{{ __('Recent Activity') }}</h3>
                                        <p class="text-sm text-gray-500">{{ __('Latest transactions and activities') }}</p>
                                    </div>
                                    @if($recentActivities->count() > 0)
                                        <a href="{{ route('transactions.index') }}" 
                                           class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                                            {{ __('View All') }}
                                            <i class="ik ik-arrow-right ml-1"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                            
                            @if($recentActivities->count() > 0)
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 bg-gray-50">
                                                <th class="px-5 py-3 font-medium">{{ __('Type') }}</th>
                                                <th class="px-5 py-3 font-medium">{{ __('Branch') }}</th>
                                                <th class="px-5 py-3 font-medium">{{ __('Amount') }}</th>
                                                <th class="px-5 py-3 font-medium">{{ __('Date') }}</th>
                                                <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                                                <th class="px-5 py-3 font-medium text-right">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50">
                                            @foreach($recentActivities as $transaction)
                                                <tr class="hover:bg-gray-50 transition">
                                                    <td class="px-5 py-3">
                                                        <span class="inline-flex items-center gap-1 bg-gray-100 px-2 py-1 rounded text-xs">
                                                            <i class="ik ik-{{ $transaction->transaction_type == 'gold_sale' ? 'shopping-bag' : 'shopping-cart' }}"></i>
                                                            {{ str_replace('_', ' ', ucfirst($transaction->transaction_type)) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-5 py-3 text-gray-700">
                                                        {{ $transaction->branch->name ?? 'N/A' }}
                                                    </td>
                                                    <td class="px-5 py-3 font-semibold {{ $transaction->transaction_type == 'gold_sale' ? 'text-green-600' : 'text-red-600' }}">
                                                        {{ number_format($transaction->amount, 0) }} TZS
                                                    </td>
                                                    <td class="px-5 py-3 text-gray-500">
                                                        <span title="{{ $transaction->created_at->format('l, F j, Y g:i A') }}">
                                                            {{ $transaction->created_at->diffForHumans() }}
                                                        </span>
                                                    </td>
                                                    <td class="px-5 py-3">
                                                        @php
                                                            $statusColors = [
                                                                'completed' => 'success',
                                                                'pending' => 'warning',
                                                                'cancelled' => 'danger',
                                                                'failed' => 'danger'
                                                            ];
                                                            $color = $statusColors[$transaction->status] ?? 'gray';
                                                            
                                                            $colorClasses = [
                                                                'gray' => 'bg-gray-100 text-gray-700',
                                                                'success' => 'bg-green-100 text-green-700',
                                                                'warning' => 'bg-yellow-100 text-yellow-700',
                                                                'danger' => 'bg-red-100 text-red-700',
                                                                'info' => 'bg-blue-100 text-blue-700',
                                                            ];
                                                            $badgeClass = $colorClasses[$color] ?? $colorClasses['gray'];
                                                        @endphp
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-{{ $color }}-500 mr-1"></span>
                                                            {{ ucfirst($transaction->status) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-5 py-3 text-right">
                                                        <a href="{{ route('transactions.show', $transaction) }}" 
                                                           class="text-gray-400 hover:text-primary-600 transition">
                                                            <i class="ik ik-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-12">
                                    <div class="text-6xl mb-4">📊</div>
                                    <h3 class="text-lg font-medium text-gray-600">{{ __('No recent activity') }}</h3>
                                    <p class="text-gray-400 text-sm mt-1">{{ __('Transactions will appear here once they are processed') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Quick Actions with Skeleton -->
        @if(isset($quickActions) && count($quickActions) > 0)
            <div x-data="{ loading: true }" x-init="setTimeout(() => loading = false, 600)">
                <!-- Skeleton Loading -->
                <div x-show="loading" class="mt-5">
                    <div class="skeleton skeleton-text" style="width: 120px; height: 20px; margin-bottom: 12px;"></div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                        <template x-for="i in 6" :key="i">
                            <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                                <div class="skeleton skeleton-circle mx-auto mb-2" style="width: 48px; height: 48px;"></div>
                                <div class="skeleton skeleton-text-sm mx-auto" style="width: 60%;"></div>
                            </div>
                        </template>
                    </div>
                </div>
                
                <!-- Actual Content -->
                <div x-show="!loading" x-transition:enter.duration.500ms class="fade-in mt-5">
                    <h4 class="text-sm font-medium text-gray-500 mb-3">{{ __('Quick Actions') }}</h4>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                        @foreach($quickActions as $action)
                            <a href="{{ $action['url'] }}" 
                               class="bg-white rounded-xl shadow-sm p-4 text-center hover:shadow-md transition hover:scale-105">
                                <div class="w-12 h-12 bg-primary-50 rounded-full flex items-center justify-center mx-auto mb-2 text-primary-500">
                                    <i class="{{ $action['icon'] }} text-xl"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-700">{{ $action['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- JavaScript for Refresh and Skeleton Control -->
    <script>
        function refreshDashboard() {
            // Show loading skeleton
            document.querySelectorAll('[x-data]').forEach(el => {
                if (el.__x) {
                    el.__x.$data.loading = true;
                }
            });
            
            window.dispatchEvent(new CustomEvent('loading-start'));
            setTimeout(() => {
                window.location.reload();
            }, 500);
        }
        
        // Auto-refresh dashboard every 5 minutes if not filtered
        @if(!request()->has('start_date') && !request()->has('end_date'))
            let refreshInterval = setInterval(() => {
                if (!document.hidden) {
                    refreshDashboard();
                }
            }, 300000); // 5 minutes
            
            // Clear interval on page unload
            window.addEventListener('beforeunload', () => {
                clearInterval(refreshInterval);
            });
        @endif
        
        // Real-time updates via Laravel Echo
        @if(config('app.env') === 'production' && isset($enableRealtime) && $enableRealtime)
            window.Echo.channel('dashboard-updates')
                .listen('DashboardUpdated', (event) => {
                    // Show notification
                    if (window.showNotification) {
                        window.showNotification('Dashboard updated with new data', 'success');
                    }
                    
                    // Refresh after 2 seconds
                    setTimeout(() => refreshDashboard(), 2000);
                });
        @endif
        
        // Global function to trigger skeleton loading on any page navigation
        document.addEventListener('DOMContentLoaded', function() {
            // Show skeletons on page load
            setTimeout(() => {
                document.querySelectorAll('[x-data]').forEach(el => {
                    if (el.__x && el.__x.$data.loading !== undefined) {
                        el.__x.$data.loading = false;
                    }
                });
            }, 1500);
        });
    </script>
@endsection