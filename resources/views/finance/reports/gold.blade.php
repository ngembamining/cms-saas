@extends('layouts.main')

@section('title', 'Gold Summary')

@section('content')
<x-page-header title="Gold Summary" subtitle="View gold transactions summary" icon="fas fa-crown"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Reports' => route('finance.reports.index'), 'Gold' => null]" />

<div class="max-w-6xl mx-auto">
    <!-- Summary -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4 mb-6">
        <x-stat-card value="{{ number_format($totalGoldPurchased, 0) }}" 
                     label="Total Purchased" 
                     icon="ik ik-shopping-bag" 
                     color="primary" 
                     subtitle="TZS" />
        
        <x-stat-card value="{{ number_format($totalGoldSold, 0) }}" 
                     label="Total Sold" 
                     icon="ik ik-trending-up" 
                     color="green" 
                     subtitle="TZS" />
        
        <x-stat-card value="{{ number_format($totalGoldSold - $totalGoldPurchased, 0) }}" 
                     label="Net" 
                     icon="ik ik-bar-chart-2" 
                     color="accent" 
                     subtitle="TZS" />
    </div>

    <!-- Purchases -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Gold Purchases</h3>
        <div class="overflow-x-auto max-h-96 overflow-y-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 sticky top-0 bg-white">
                        <th class="px-3 py-2 font-medium">Date</th>
                        <th class="px-3 py-2 font-medium">Count</th>
                        <th class="px-3 py-2 font-medium">Total Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($purchases as $purchase)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 text-gray-700">{{ \Carbon\Carbon::parse($purchase->date)->format('d/m/Y') }}</td>
                        <td class="px-3 py-2 text-gray-700">{{ $purchase->count }}</td>
                        <td class="px-3 py-2 font-semibold text-red-600">{{ number_format($purchase->total_amount, 0) }} TZS</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-3 py-4 text-center text-gray-400">No gold purchases found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sales -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Gold Sales</h3>
        <div class="overflow-x-auto max-h-96 overflow-y-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 sticky top-0 bg-white">
                        <th class="px-3 py-2 font-medium">Date</th>
                        <th class="px-3 py-2 font-medium">Count</th>
                        <th class="px-3 py-2 font-medium">Total Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($sales as $sale)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 text-gray-700">{{ \Carbon\Carbon::parse($sale->date)->format('d/m/Y') }}</td>
                        <td class="px-3 py-2 text-gray-700">{{ $sale->count }}</td>
                        <td class="px-3 py-2 font-semibold text-green-600">{{ number_format($sale->total_amount, 0) }} TZS</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-3 py-4 text-center text-gray-400">No gold sales found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection