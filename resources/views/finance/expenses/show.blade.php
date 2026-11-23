@extends('layouts.main')

@section('title', 'Expense Details')

@section('content')
<x-page-header title="Expense Details" subtitle="View expense details" icon="ik ik-eye"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Expenses' => route('finance.expenses.index'), 'Details' => null]">
    <div class="flex gap-2">
        <a href="{{ route('finance.expenses.edit', $expense->id) }}" class="btn btn-warning">
            <i class="ik ik-edit"></i> Edit
        </a>
        <a href="{{ route('finance.expenses.index') }}" class="btn btn-secondary">
            <i class="ik ik-arrow-left"></i> Back
        </a>
    </div>
</x-page-header>

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500">Expense ID</p>
                <p class="font-semibold">#{{ $expense->id }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Category</p>
                <p class="font-medium">
                    <span class="bg-gray-100 px-2 py-1 rounded text-xs">{{ $expense->category }}</span>
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Branch</p>
                <p class="font-medium">{{ $expense->branch->name ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Amount</p>
                <p class="font-bold text-lg text-red-600">{{ number_format($expense->amount, 0) }} TZS</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Recorded By</p>
                <p class="font-medium">{{ $expense->user->full_name ?? $expense->user->username ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Date</p>
                <p class="font-medium">
                    @if($expense->created_at)
                        {{ $expense->created_at->format('d/m/Y H:i') }}
                    @else
                        N/A
                    @endif
                </p>
            </div>
            @if($expense->description)
            <div class="col-span-2">
                <p class="text-sm text-gray-500">Description</p>
                <p class="font-medium">{{ $expense->description }}</p>
            </div>
            @endif
            @if($expense->receipt_path)
            <div class="col-span-2">
                <p class="text-sm text-gray-500">Receipt</p>
                <a href="{{ route('finance.expenses.receipts.download', $expense->id) }}" 
                   class="text-primary-600 hover:text-primary-700">
                    <i class="ik ik-download"></i> Download Receipt
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection