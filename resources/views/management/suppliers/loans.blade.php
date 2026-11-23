@extends('layouts.main')

@section('title', $supplier->name . ' - Loans')

@section('content')
<x-page-header title="{{ $supplier->name }}" subtitle="Loan history" icon="ik ik-file-text"
    :breadcrumbs="['Home' => url('dashboard'), 'Suppliers' => route('suppliers.index'), $supplier->name => route('suppliers.show', $supplier->id), 'Loans' => null]">
    <a href="{{ route('suppliers.show', $supplier->id) }}" class="btn btn-secondary">
        <i class="ik ik-arrow-left"></i> Back to Supplier
    </a>
</x-page-header>

<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 font-medium">#</th>
                    <th class="px-5 py-3 font-medium">Branch</th>
                    <th class="px-5 py-3 font-medium">Amount</th>
                    <th class="px-5 py-3 font-medium">Interest</th>
                    <th class="px-5 py-3 font-medium">Duration</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                    <th class="px-5 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($loans as $loan)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 text-gray-700">#{{ $loan->id }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $loan->branch->name ?? 'N/A' }}</td>
                    <td class="px-5 py-3 font-semibold text-gray-800">{{ number_format($loan->amount, 0) }} TZS</td>
                    <td class="px-5 py-3 text-gray-700">{{ $loan->interest_rate }}%</td>
                    <td class="px-5 py-3 text-gray-700">{{ $loan->duration_months }} months</td>
                    <td class="px-5 py-3">
                        @if($loan->status == 'pending')
                            <span class="badge badge-amber">Pending</span>
                        @elseif($loan->status == 'approved')
                            <span class="badge badge-success">Approved</span>
                        @elseif($loan->status == 'rejected')
                            <span class="badge badge-danger">Rejected</span>
                        @elseif($loan->status == 'completed')
                            <span class="badge badge-primary">Completed</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-500">
                        @if($loan->created_at)
                            {{ $loan->created_at->format('d/m/Y') }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        <a href="{{ route('loans.show', $loan->id) }}" 
                           class="text-blue-500 hover:text-blue-600">
                            <i class="ik ik-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-5 py-8 text-center text-gray-400">
                        <i class="ik ik-file-text text-3xl block mb-2"></i>
                        No loans found for this supplier.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $loans->links() }}
    </div>
</div>
@endsection