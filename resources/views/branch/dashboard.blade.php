@extends('layouts.main')
@section('title', 'My Branch Dashboard')
@section('content')

<div class="max-w-7xl mx-auto">
    <x-page-header title="{{ $branch->name }}" 
                   subtitle="{{ __('Branch Dashboard') }}" 
                   icon="fas fa-store"
                   :breadcrumbs="['Home' => route('dashboard'), 'My Branch' => null]" />

    <!-- Stats -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card value="{{ number_format($stats['total_cash'] ?? 0, 0) }}" 
                     label="{{ __('Cash Balance') }}" 
                     icon="ik ik-dollar-sign" 
                     color="green" />
        
        <x-stat-card value="{{ number_format($stats['total_gold'] ?? 0, 2) . ' g' }}" 
                     label="{{ __('Gold Balance') }}" 
                     icon="fas fa-crown" 
                     color="amber" />
        
        <x-stat-card value="{{ number_format($stats['monthly_sales'] ?? 0, 0) }}" 
                     label="{{ __('Monthly Sales') }}" 
                     icon="ik ik-trending-up" 
                     color="blue" />
        
        <x-stat-card value="{{ number_format($stats['monthly_expenses'] ?? 0, 0) }}" 
                     label="{{ __('Monthly Expenses') }}" 
                     icon="ik ik-dollar-sign" 
                     color="red" />
    </div>

    <!-- More Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Monthly Purchases</p>
                    <p class="text-2xl font-bold text-blue-600">{{ number_format($stats['monthly_purchases'] ?? 0, 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="ik ik-shopping-bag text-blue-500 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Pending Loans</p>
                    <p class="text-2xl font-bold text-amber-600">{{ $stats['pending_loans'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center">
                    <i class="ik ik-file-text text-amber-500 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Staff Members</p>
                    <p class="text-2xl font-bold text-purple-600">{{ $staffCount ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="ik ik-users text-purple-500 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="mt-6">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h5 class="font-semibold text-gray-700">{{ __('Recent Transactions') }}</h5>
                <a href="{{ route('branch.transactions') }}" class="text-sm text-primary-600 hover:text-primary-700">
                    {{ __('View All') }} <i class="ik ik-arrow-right ml-1"></i>
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-6 py-3 font-medium">{{ __('Type') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Amount') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Date') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($recentTransactions ?? [] as $transaction)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3">
                                    <span class="px-2 py-1 bg-gray-100 rounded text-xs">
                                        {{ str_replace('_', ' ', ucfirst($transaction->transaction_type)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 font-medium {{ $transaction->transaction_type == 'gold_sale' ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format($transaction->amount, 0) }}
                                </td>
                                <td class="px-6 py-3 text-gray-500">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-3">
                                    <x-badge color="{{ $transaction->status == 'completed' ? 'success' : 'warning' }}">
                                        {{ ucfirst($transaction->status) }}
                                    </x-badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                                    {{ __('No recent transactions') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pending Loans -->
    @if(isset($pendingLoans) && $pendingLoans->count() > 0)
    <div class="mt-6">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h5 class="font-semibold text-gray-700">{{ __('Pending Loans') }}</h5>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-6 py-3 font-medium">{{ __('Amount') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Supplier') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Applied') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($pendingLoans as $loan)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-medium text-amber-600">{{ number_format($loan->amount, 0) }}</td>
                                <td class="px-6 py-3 text-gray-700">{{ $loan->supplier->name ?? 'N/A' }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ $loan->created_at->format('d/m/Y') }}</td>
                                <td class="px-6 py-3">
                                    <a href="{{ route('loans.approvals.review', $loan->id) }}" 
                                       class="text-primary-600 hover:text-primary-700 text-sm">
                                        {{ __('Review') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

@endsection