@extends('layouts.main')

@section('title', 'Gold Purchase Details')

@section('content')
<x-page-header title="Gold Purchase Details" subtitle="View purchase transaction details" icon="ik ik-eye"
    :breadcrumbs="['Home' => url('dashboard'), 'Transactions' => route('transactions.index'), 'Gold Purchases' => route('transactions.gold.purchases.index'), 'Details' => null]">
    <div class="flex gap-2">
        <a href="{{ route('transactions.gold.purchases.index') }}" class="btn btn-secondary">
            <i class="ik ik-arrow-left"></i> Back
        </a>
        @if($purchase->status == 'pending')
            <a href="{{ route('transactions.gold.purchases.edit', $purchase->id) }}" class="btn btn-warning">
                <i class="ik ik-edit"></i> Edit
            </a>
            <a href="{{ route('transactions.gold.purchases.receive', $purchase->id) }}" 
               class="btn btn-success"
               onclick="return confirm('Receive this gold purchase? This will update branch inventory.')">
                <i class="ik ik-check-circle"></i> Receive
            </a>
        @endif
    </div>
</x-page-header>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Details -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Purchase Information</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Transaction Code</p>
                    <p class="font-mono font-semibold">{{ $purchase->transaction_code }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Status</p>
                    <p>
                        @if($purchase->status == 'pending')
                            <span class="badge badge-amber">Pending</span>
                        @elseif($purchase->status == 'completed')
                            <span class="badge badge-success">Completed</span>
                        @elseif($purchase->status == 'cancelled')
                            <span class="badge badge-danger">Cancelled</span>
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Date</p>
                    <p class="font-medium">{{ $purchase->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Recorded By</p>
                    <p class="font-medium">{{ $purchase->user->full_name ?? $purchase->user->username ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-6 mt-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Transaction Details</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Branch</p>
                    <p class="font-medium">{{ $purchase->branch->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Supplier</p>
                    <p class="font-medium">{{ $purchase->supplier->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Weight</p>
                    <p class="font-medium text-amber-600">{{ number_format($purchase->items->sum('weight') ?? 0, 2) }} g</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Purity</p>
                    <p class="font-medium">{{ number_format($purchase->items->first()->purity ?? 0, 2) }}%</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Price per Gram</p>
                    <p class="font-medium">{{ number_format($purchase->items->first()->agreed_price ?? 0, 2) }} TZS</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total Amount</p>
                    <p class="font-bold text-lg text-green-600">{{ number_format($purchase->amount, 0) }} TZS</p>
                </div>
                <div class="col-span-2">
                    <p class="text-sm text-gray-500">Payment Method</p>
                    <p class="font-medium">{{ ucfirst(str_replace('_', ' ', $purchase->payment_method ?? 'N/A')) }}</p>
                </div>
                @if($purchase->payment_reference)
                <div class="col-span-2">
                    <p class="text-sm text-gray-500">Payment Reference</p>
                    <p class="font-medium">{{ $purchase->payment_reference }}</p>
                </div>
                @endif
                @if($purchase->description)
                <div class="col-span-2">
                    <p class="text-sm text-gray-500">Description</p>
                    <p class="font-medium">{{ $purchase->description }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Summary Card -->
    <div class="lg:col-span-1">
        <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl p-6 text-white">
            <h4 class="text-sm font-medium opacity-90">Purchase Summary</h4>
            <div class="mt-4 space-y-3">
                <div>
                    <p class="text-xs opacity-80">Total Weight</p>
                    <p class="text-2xl font-bold">{{ number_format($purchase->items->sum('weight') ?? 0, 2) }} g</p>
                </div>
                <div>
                    <p class="text-xs opacity-80">Total Amount</p>
                    <p class="text-2xl font-bold">{{ number_format($purchase->amount, 0) }} TZS</p>
                </div>
                <div>
                    <p class="text-xs opacity-80">Average Price</p>
                    <p class="text-xl font-bold">{{ number_format($purchase->items->first()->agreed_price ?? 0, 2) }} TZS/g</p>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-white/20">
                <p class="text-xs opacity-80">Status</p>
                <p class="font-semibold">
                    @if($purchase->status == 'pending')
                        ⏳ Awaiting Approval
                    @elseif($purchase->status == 'completed')
                        ✅ Completed
                    @elseif($purchase->status == 'cancelled')
                        ❌ Cancelled
                    @endif
                </p>
            </div>
        </div>
    </div>
</div>
@endsection