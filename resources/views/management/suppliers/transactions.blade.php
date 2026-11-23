@extends('layouts.main')

@section('title', $supplier->name . ' - Transactions')

@section('content')
<x-page-header title="{{ $supplier->name }}" subtitle="Transaction history" icon="ik ik-repeat"
    :breadcrumbs="['Home' => url('dashboard'), 'Suppliers' => route('suppliers.index'), $supplier->name => route('suppliers.show', $supplier->id), 'Transactions' => null]">
    <a href="{{ route('suppliers.show', $supplier->id) }}" class="btn btn-secondary">
        <i class="ik ik-arrow-left"></i> Back to Supplier
    </a>
</x-page-header>

<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 font-medium">Code</th>
                    <th class="px-5 py-3 font-medium">Type</th>
                    <th class="px-5 py-3 font-medium">Branch</th>
                    <th class="px-5 py-3 font-medium">Amount</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                    <th class="px-5 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($transactions as $transaction)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-mono text-sm text-gray-600">{{ $transaction->transaction_code }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ ucfirst(str_replace('_', ' ', $transaction->transaction_type)) }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $transaction->branch->name ?? 'N/A' }}</td>
                    <td class="px-5 py-3 font-semibold {{ $transaction->transaction_type == 'gold_purchase' ? 'text-red-600' : 'text-green-600' }}">
                        {{ number_format($transaction->amount, 0) }} TZS
                    </td>
                    <td class="px-5 py-3">
                        @if($transaction->status == 'pending')
                            <span class="badge badge-amber">Pending</span>
                        @elseif($transaction->status == 'completed')
                            <span class="badge badge-success">Completed</span>
                        @elseif($transaction->status == 'cancelled')
                            <span class="badge badge-danger">Cancelled</span>
                        @else
                            <span class="badge badge-gray">{{ $transaction->status }}</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-500">
                        @if($transaction->created_at)
                            {{ $transaction->created_at->format('d/m/Y H:i') }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        <a href="{{ route('transactions.show', $transaction->id) }}" 
                           class="text-blue-500 hover:text-blue-600">
                            <i class="ik ik-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-8 text-center text-gray-400">
                        <i class="ik ik-repeat text-3xl block mb-2"></i>
                        No transactions found for this supplier.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $transactions->links() }}
    </div>
</div>
@endsection