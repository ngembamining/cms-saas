@extends('layouts.main')

@section('title', 'Balance Sheet')

@section('content')
<x-page-header title="Balance Sheet" subtitle="View balance sheet summary" icon="ik ik-pie-chart"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Reports' => route('finance.reports.index'), 'Balance Sheet' => null]" />

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('finance.reports.balance-sheet') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">As At Date</label>
                <input type="date" name="as_at_date" value="{{ $asAtDate }}" 
                       class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            </div>
            <button type="submit" class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-600">
                <i class="ik ik-search"></i> View
            </button>
            <a href="{{ route('finance.reports.balance-sheet') }}" class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-300">
                <i class="ik ik-rotate-ccw"></i> Today
            </a>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Assets -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Assets</h3>
            <p class="text-sm text-gray-500 mb-4">As at {{ \Carbon\Carbon::parse($asAtDate)->format('d/m/Y') }}</p>
            
            <div class="space-y-3">
                <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="font-medium text-gray-700">Cash Balance</span>
                    <span class="font-semibold text-green-600">{{ number_format($totalCash, 0) }} TZS</span>
                </div>
                
                <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="font-medium text-gray-700">Gold Inventory</span>
                    <span class="font-semibold text-amber-600">{{ number_format($totalGold, 2) }} g</span>
                </div>
                
                <div class="flex justify-between py-2 border-b-2 border-gray-300 bg-gray-50">
                    <span class="font-bold text-gray-800">Total Assets</span>
                    <span class="font-bold text-primary-600">{{ number_format($totalAssets, 0) }} TZS</span>
                </div>
            </div>
        </div>

        <!-- Liabilities & Equity -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Liabilities & Equity</h3>
            <p class="text-sm text-gray-500 mb-4">As at {{ \Carbon\Carbon::parse($asAtDate)->format('d/m/Y') }}</p>
            
            <div class="space-y-3">
                <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="font-medium text-gray-700">Loans (Liabilities)</span>
                    <span class="font-semibold text-red-600">{{ number_format($totalLoans, 0) }} TZS</span>
                </div>
                
                <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="font-medium text-gray-700">Equity</span>
                    <span class="font-semibold text-blue-600">{{ number_format($equity, 0) }} TZS</span>
                </div>
                
                <div class="flex justify-between py-2 border-b-2 border-gray-300 bg-gray-50">
                    <span class="font-bold text-gray-800">Total Liabilities & Equity</span>
                    <span class="font-bold text-primary-600">{{ number_format($totalLiabilitiesEquity, 0) }} TZS</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection