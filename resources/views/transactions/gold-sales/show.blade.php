@extends('layouts.main')

@section('title', 'Gold Sale Details')

@section('content')
<x-page-header title="Gold Sale Details" subtitle="View sale transaction details" icon="ik ik-eye"
    :breadcrumbs="['Home' => url('dashboard'), 'Transactions' => route('transactions.index'), 'Gold Sales' => route('transactions.gold.sales.index'), 'Details' => null]">
    <div class="flex gap-2">
        <a href="{{ route('transactions.gold.sales.index') }}" class="btn btn-secondary">
            <i class="ik ik-arrow-left"></i> Back
        </a>
        @if($sale->status == 'pending')
            <a href="{{ route('transactions.gold.sales.edit', $sale->id) }}" class="btn btn-warning">
                <i class="ik ik-edit"></i> Edit
            </a>
            <a href="{{ route('transactions.gold.sales.complete', $sale->id) }}" 
               class="btn btn-success"
               onclick="return confirm('Complete this gold sale?')">
                <i class="ik ik-check-circle"></i> Complete
            </a>
        @endif
    </div>
</x-page-header>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Details -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Sale Information</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Transaction Code</p>
                    <p class="font-mono font-semibold">{{ $sale->transaction_code }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Status</p>
                    <p>
                        @if($sale->status == 'pending')
                            <span class="badge badge-amber">Pending</span>
                        @elseif($sale->status == 'completed')
                            <span class="badge badge-success">Completed</span>
                        @elseif($sale->status == 'cancelled')
                            <span class="badge badge-danger">Cancelled</span>
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Date</p>
                    <p class="font-medium">{{ $sale->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Recorded By</p>
                    <p class="font-medium">{{ $sale->user->full_name ?? $sale->user->username ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-6 mt-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Transaction Details</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Branch</p>
                    <p class="font-medium">{{ $sale->branch->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Customer</p>
                    <p class="font-medium">{{ $sale->customer->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total Weight</p>
                    <p class="font-medium text-amber-600">{{ number_format($sale->items->sum('weight') ?? 0, 2) }} g</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total Amount</p>
                    <p class="font-bold text-lg text-green-600">{{ number_format($sale->amount, 0) }} TZS</p>
                </div>
                <div class="col-span-2">
                    <p class="text-sm text-gray-500">Payment Method</p>
                    <p class="font-medium">{{ ucfirst(str_replace('_', ' ', $sale->payment_method ?? 'N/A')) }}</p>
                </div>
                @if($sale->payment_reference)
                <div class="col-span-2">
                    <p class="text-sm text-gray-500">Payment Reference</p>
                    <p class="font-medium">{{ $sale->payment_reference }}</p>
                </div>
                @endif
                @if($sale->description)
                <div class="col-span-2">
                    <p class="text-sm text-gray-500">Description</p>
                    <p class="font-medium">{{ $sale->description }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Items Table -->
        <div class="bg-white rounded-xl shadow-sm p-6 mt-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Gold Items</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-3 py-2 font-medium">#</th>
                            <th class="px-3 py-2 font-medium">Weight (g)</th>
                            <th class="px-3 py-2 font-medium">Purity (%)</th>
                            <th class="px-3 py-2 font-medium">Price (TZS/g)</th>
                            <th class="px-3 py-2 font-medium">Amount (TZS)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($sale->items as $index => $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 text-gray-500">{{ $index + 1 }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ number_format($item->weight, 2) }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ number_format($item->purity, 2) }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ number_format($item->agreed_price, 0) }}</td>
                            <td class="px-3 py-2 font-semibold text-green-600">
                                {{ number_format(($item->weight * $item->purity / 100 * $item->agreed_price) / 12, 0) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 border-gray-200">
                        <tr>
                            <td colspan="4" class="px-3 py-2 text-right font-semibold text-gray-700">Total:</td>
                            <td class="px-3 py-2 font-bold text-green-600">{{ number_format($sale->amount, 0) }} TZS</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-3 py-2 text-right font-semibold text-gray-700">Total Weight:</td>
                            <td class="px-3 py-2 font-bold text-amber-600">{{ number_format($sale->items->sum('weight') ?? 0, 2) }} g</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Summary Card -->
    <div class="lg:col-span-1">
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white">
            <h4 class="text-sm font-medium opacity-90">Sale Summary</h4>
            <div class="mt-4 space-y-3">
                <div>
                    <p class="text-xs opacity-80">Total Weight</p>
                    <p class="text-2xl font-bold">{{ number_format($sale->items->sum('weight') ?? 0, 2) }} g</p>
                </div>
                <div>
                    <p class="text-xs opacity-80">Total Amount</p>
                    <p class="text-2xl font-bold">{{ number_format($sale->amount, 0) }} TZS</p>
                </div>
                <div>
                    <p class="text-xs opacity-80">Customer</p>
                    <p class="text-xl font-bold">{{ $sale->customer->name ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-white/20">
                <p class="text-xs opacity-80">Status</p>
                <p class="font-semibold">
                    @if($sale->status == 'pending')
                        ⏳ Awaiting Completion
                    @elseif($sale->status == 'completed')
                        ✅ Completed
                    @elseif($sale->status == 'cancelled')
                        ❌ Cancelled
                    @endif
                </p>
            </div>
        </div>
    </div>
</div>
@endsection