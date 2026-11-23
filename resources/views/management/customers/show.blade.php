@extends('layouts.main')

@section('title', $customer->name)

@section('content')
<x-page-header title="{{ $customer->name }}" subtitle="Customer details" icon="ik ik-user"
    :breadcrumbs="['Home' => url('dashboard'), 'Customers' => route('customers.index'), $customer->name => null]">
    <div class="flex gap-2">
        <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-warning">
            <i class="ik ik-edit"></i> Edit
        </a>
        <a href="{{ route('customers.index') }}" class="btn btn-secondary">
            <i class="ik ik-arrow-left"></i> Back
        </a>
    </div>
</x-page-header>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Customer Info -->
    <div class="lg:col-span-1">
        <x-card>
            <x-slot:header>{{ __('Customer Information') }}</x-slot:header>
            <div class="space-y-3 text-sm">
                <div>
                    <p class="text-gray-500">Name</p>
                    <p class="font-medium">{{ $customer->name }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Contact</p>
                    <p class="font-medium">{{ $customer->contact }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Email</p>
                    <p class="font-medium">{{ $customer->email ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Branch</p>
                    <p class="font-medium">{{ $customer->branch->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Address</p>
                    <p class="font-medium">{{ $customer->address ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Registered</p>
                    <p class="font-medium">
                        @if($customer->created_at)
                            {{ $customer->created_at->format('d/m/Y H:i') }}
                        @else
                            N/A
                        @endif
                    </p>
                </div>
            </div>
        </x-card>
    </div>
    
    <!-- Summary -->
    <div class="lg:col-span-2">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <x-stat-card value="{{ number_format($totalSales, 0) }}" 
                         label="Total Sales" 
                         icon="ik ik-trending-up" 
                         color="green" 
                         subtitle="TZS" />
        </div>
        
        <!-- Recent Transactions -->
        <x-card>
            <x-slot:header>{{ __('Recent Transactions') }}</x-slot:header>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-3 py-2 font-medium">Code</th>
                            <th class="px-3 py-2 font-medium">Type</th>
                            <th class="px-3 py-2 font-medium">Amount</th>
                            <th class="px-3 py-2 font-medium">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($recentTransactions as $transaction)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 font-mono text-sm text-gray-600">{{ $transaction->transaction_code }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ ucfirst(str_replace('_', ' ', $transaction->transaction_type)) }}</td>
                            <td class="px-3 py-2 font-semibold text-gray-800">{{ number_format($transaction->amount, 0) }} TZS</td>
                            <td class="px-3 py-2 text-gray-500">
                                @if($transaction->created_at)
                                    {{ $transaction->created_at->format('d/m/Y H:i') }}
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-3 py-4 text-center text-gray-400">No transactions found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                <a href="{{ route('customers.transactions', $customer->id) }}" class="text-primary-600 hover:text-primary-700 text-sm">
                    View all transactions →
                </a>
            </div>
        </x-card>
    </div>
</div>
@endsection