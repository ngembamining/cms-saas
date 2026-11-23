@extends('layouts.main')

@section('title', 'Gold Sales')

@section('content')
<x-page-header title="Gold Sales" subtitle="Manage all gold sale transactions" icon="ik ik-trending-up"
    :breadcrumbs="['Home' => url('dashboard'), 'Transactions' => route('transactions.index'), 'Gold Sales' => null]">
    <a href="{{ route('transactions.gold.sales.create') }}" class="btn btn-primary">
        <i class="ik ik-plus"></i> New Sale
    </a>
</x-page-header>

<!-- Statistics -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4 mb-6">
    <x-stat-card value="{{ number_format($totalSales, 0) }}" 
                 label="Total Amount" 
                 icon="ik ik-dollar-sign" 
                 color="green" 
                 subtitle="TZS" />
    
    <x-stat-card value="{{ number_format($totalWeight, 2) . ' g' }}" 
                 label="Total Weight" 
                 icon="fas fa-weight" 
                 color="amber" />
    
    <x-stat-card value="{{ $sales->total() }}" 
                 label="Total Sales" 
                 icon="ik ik-trending-up" 
                 color="primary" />
    
    <x-stat-card value="{{ $pendingCount }}" 
                 label="Pending" 
                 icon="ik ik-clock" 
                 color="amber" />
</div>

<!-- Sales Table -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 font-medium">Code</th>
                    <th class="px-5 py-3 font-medium">Customer</th>
                    <th class="px-5 py-3 font-medium">Branch</th>
                    <th class="px-5 py-3 font-medium">Weight</th>
                    <th class="px-5 py-3 font-medium">Amount</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                    <th class="px-5 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($sales as $sale)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-mono text-sm text-gray-600">{{ $sale->transaction_code }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $sale->customer->name ?? 'N/A' }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $sale->branch->name ?? 'N/A' }}</td>
                    <td class="px-5 py-3 text-amber-600 font-medium">
                        {{ number_format($sale->items->sum('weight') ?? 0, 2) }} g
                    </td>
                    <td class="px-5 py-3 font-semibold text-green-600">
                        {{ number_format($sale->amount, 0) }} TZS
                    </td>
                    <td class="px-5 py-3">
                        @if($sale->status == 'pending')
                            <span class="badge badge-amber">Pending</span>
                        @elseif($sale->status == 'completed')
                            <span class="badge badge-success">Completed</span>
                        @elseif($sale->status == 'cancelled')
                            <span class="badge badge-danger">Cancelled</span>
                        @else
                            <span class="badge badge-gray">{{ $sale->status }}</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-500">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-5 py-3">
                        <a href="{{ route('transactions.gold.sales.show', $sale->id) }}" 
                           class="text-blue-500 hover:text-blue-600 mr-2">
                            <i class="ik ik-eye"></i>
                        </a>
                        @if($sale->status == 'pending')
                            <a href="{{ route('transactions.gold.sales.edit', $sale->id) }}" 
                               class="text-green-500 hover:text-green-600 mr-2">
                                <i class="ik ik-edit"></i>
                            </a>
                            <a href="{{ route('transactions.gold.sales.complete', $sale->id) }}" 
                               class="text-amber-500 hover:text-amber-600 mr-2"
                               onclick="return confirm('Complete this gold sale?')">
                                <i class="ik ik-check-circle"></i>
                            </a>
                            <form action="{{ route('transactions.gold.sales.destroy', $sale->id) }}" 
                                  method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-600" 
                                        onclick="return confirm('Delete this sale?')">
                                    <i class="ik ik-trash-2"></i>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-5 py-8 text-center text-gray-400">
                        <i class="ik ik-trending-up text-3xl block mb-2"></i>
                        No gold sales recorded yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $sales->links() }}
    </div>
</div>
@endsection