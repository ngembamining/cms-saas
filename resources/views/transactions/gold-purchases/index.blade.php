@extends('layouts.main')

@section('title', 'Gold Purchases')

@section('content')
<x-page-header title="Gold Purchases" subtitle="Manage all gold purchase transactions" icon="ik ik-shopping-bag"
    :breadcrumbs="['Home' => url('dashboard'), 'Transactions' => route('transactions.index'), 'Gold Purchases' => null]">
    <a href="{{ route('transactions.gold.purchases.create') }}" class="btn btn-primary">
        <i class="ik ik-plus"></i> New Purchase
    </a>
</x-page-header>

@if(session('error'))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <i class="ik ik-alert-circle mr-2"></i>
        {{ session('error') }}
    </div>
@endif

@if(session('success'))
    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
        <i class="ik ik-check-circle mr-2"></i>
        {{ session('success') }}
    </div>
@endif

<!-- Statistics -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4 mb-6">
    <x-stat-card value="{{ number_format($totalPurchases, 0) }}"
                 label="Total Amount"
                 icon="ik ik-dollar-sign"
                 color="primary"
                 subtitle="TZS" />

    <x-stat-card value="{{ number_format($totalWeight, 2) . ' g' }}"
                 label="Total Weight Purchased"
                 icon="fas fa-weight"
                 color="amber" />

    <x-stat-card value="{{ $purchases->total() }}"
                 label="Total Purchases"
                 icon="ik ik-shopping-bag"
                 color="green" />

    <x-stat-card value="{{ $pendingCount }}"
                 label="Pending Approval"
                 icon="ik ik-clock"
                 color="amber" />
</div>

<!-- Purchases Table -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 font-medium">Code</th>
                    <th class="px-5 py-3 font-medium">Supplier</th>
                    <th class="px-5 py-3 font-medium">Branch</th>
                    <th class="px-5 py-3 font-medium">Weight</th>
                    <th class="px-5 py-3 font-medium">Purity</th>
                    <th class="px-5 py-3 font-medium">Amount</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                    <th class="px-5 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @php
                    $canDelete = in_array(auth()->user()->role, ['branch_manager', 'director'], true);
                @endphp
                @forelse($purchases as $purchase)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-mono text-sm text-gray-600">{{ $purchase->transaction_code }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $purchase->supplier->name ?? 'N/A' }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $purchase->branch->name ?? 'N/A' }}</td>
                    <td class="px-5 py-3 text-amber-600 font-medium">
                        {{ number_format($purchase->items->sum('weight') ?? 0, 2) }} g
                    </td>
                    <td class="px-5 py-3 text-gray-700">
                        {{ number_format($purchase->items->first()->purity ?? 0, 2) }}%
                    </td>
                    <td class="px-5 py-3 font-semibold text-green-600">
                        {{ number_format($purchase->amount, 0) }} TZS
                    </td>
                    <td class="px-5 py-3">
                        @if($purchase->status == 'pending')
                            <span class="badge badge-amber">Pending</span>
                        @elseif($purchase->status == 'completed')
                            <span class="badge badge-success">Completed</span>
                        @elseif($purchase->status == 'cancelled')
                            <span class="badge badge-danger">Cancelled</span>
                        @else
                            <span class="badge badge-gray">{{ $purchase->status }}</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-500">{{ $purchase->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-5 py-3">
                        {{-- 1. View --}}
                        <a href="{{ route('transactions.gold.purchases.show', $purchase->id) }}"
                           class="text-blue-500 hover:text-blue-600 mr-2"
                           title="View">
                            <i class="ik ik-eye"></i>
                        </a>

                        @if($purchase->status == 'pending')
                            {{-- 2. Edit (pending only) --}}
                            <a href="{{ route('transactions.gold.purchases.edit', $purchase->id) }}"
                               class="text-green-500 hover:text-green-600 mr-2"
                               title="Edit">
                                <i class="ik ik-edit"></i>
                            </a>

                            {{-- 3. Receive (pending only) --}}
                            <a href="{{ route('transactions.gold.purchases.receive', $purchase->id) }}"
                               class="text-amber-500 hover:text-amber-600 mr-2"
                               title="Receive"
                               onclick="return confirm('Receive this gold purchase? This will update branch inventory.')">
                                <i class="ik ik-check-circle"></i>
                            </a>
                        @endif

                        {{-- 4. Delete — allowed for branch_manager and director on any status --}}
                        @if($canDelete)
                            <form action="{{ route('transactions.gold.purchases.destroy', $purchase->id) }}"
                                  method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="text-red-500 hover:text-red-600"
                                        title="Delete"
                                        onclick="return confirm('Delete this purchase? Balances will be reversed. Continue?')">
                                    <i class="ik ik-trash-2"></i>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-5 py-8 text-center text-gray-400">
                        <i class="ik ik-shopping-bag text-3xl block mb-2"></i>
                        No gold purchases recorded yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $purchases->links() }}
    </div>
</div>
@endsection