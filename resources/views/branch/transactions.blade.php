@extends('layouts.main')
@section('title', 'Branch Transactions')
@section('content')

<div class="max-w-7xl mx-auto">
    <x-page-header title="{{ $branch->name }} - {{ __('Transactions') }}" 
                   subtitle="{{ __('View branch transactions') }}" 
                   icon="ik ik-repeat"
                   :breadcrumbs="['Home' => route('dashboard'), 'My Branch' => route('branch.dashboard'), 'Transactions' => null]" />

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
        <form action="{{ route('branch.transactions') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Start Date') }}</label>
                <input type="date" name="start_date" value="{{ $startDate }}" 
                       class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('End Date') }}</label>
                <input type="date" name="end_date" value="{{ $endDate }}" 
                       class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Type') }}</label>
                <select name="type" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    <option value="">{{ __('All Types') }}</option>
                    @foreach($transactionTypes ?? [] as $key => $label)
                        <option value="{{ $key }}" {{ $type == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition">
                <i class="ik ik-search mr-2"></i> {{ __('Apply') }}
            </button>
            <a href="{{ route('branch.transactions') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="ik ik-rotate-ccw mr-2"></i> {{ __('Reset') }}
            </a>
        </form>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-green-50 rounded-lg p-4">
            <p class="text-sm text-gray-600">Total Sales</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format($summary['total_sales'] ?? 0, 0) }}</p>
        </div>
        <div class="bg-blue-50 rounded-lg p-4">
            <p class="text-sm text-gray-600">Total Purchases</p>
            <p class="text-2xl font-bold text-blue-600">{{ number_format($summary['total_purchases'] ?? 0, 0) }}</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-sm text-gray-600">Total Transactions</p>
            <p class="text-2xl font-bold text-gray-700">{{ $summary['total_transactions'] ?? 0 }}</p>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-6 py-3 font-medium">{{ __('Type') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Amount') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Reference') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Date') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($transactions ?? [] as $transaction)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3">
                                <span class="px-2 py-1 bg-gray-100 rounded text-xs">
                                    {{ str_replace('_', ' ', ucfirst($transaction->transaction_type)) }}
                                </span>
                            </td>
                            <td class="px-6 py-3 font-medium {{ $transaction->transaction_type == 'gold_sale' ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format($transaction->amount, 0) }}
                            </td>
                            <td class="px-6 py-3 text-gray-500">{{ $transaction->reference_no ?? '-' }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-3">
                                <x-badge color="{{ $transaction->status == 'completed' ? 'success' : 'warning' }}">
                                    {{ ucfirst($transaction->status) }}
                                </x-badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                                {{ __('No transactions found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-gray-100">
            {{ $transactions->links() }}
        </div>
    </div>
</div>

@endsection