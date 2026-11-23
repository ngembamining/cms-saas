@extends('layouts.main')

@section('title', $branch->name)

@section('content')
<x-page-header title="{{ $branch->name }}" subtitle="Branch details" icon="fas fa-store"
    :breadcrumbs="['Home' => url('dashboard'), 'Management' => route('management.users.index'), 'Branches' => route('management.branches.index'), $branch->name => null]">
    <div class="flex gap-2">
        <a href="{{ route('management.branches.edit', $branch->id) }}" class="btn btn-warning">
            <i class="ik ik-edit"></i> Edit
        </a>
        <a href="{{ route('management.branches.index') }}" class="btn btn-secondary">
            <i class="ik ik-arrow-left"></i> Back
        </a>
    </div>
</x-page-header>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Branch Info -->
    <div class="lg:col-span-1">
        <x-card>
            <x-slot:header>{{ __('Branch Information') }}</x-slot:header>
            <div class="space-y-3 text-sm">
                <div>
                    <p class="text-gray-500">Name</p>
                    <p class="font-medium">{{ $branch->name }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Location</p>
                    <p class="font-medium">{{ $branch->location ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Staff Count</p>
                    <p class="font-medium">{{ $totalStaff }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Created</p>
                    <p class="font-medium">{{ $branch->created_at ? $branch->created_at->format('d/m/Y H:i') : 'N/A' }}</p>
                </div>
                <div class="border-t border-gray-200 pt-3">
                    <a href="{{ route('management.branches.balances', $branch->id) }}" class="text-primary-600 hover:text-primary-700 text-sm">
                        <i class="ik ik-dollar-sign"></i> View Balances
                    </a>
                </div>
            </div>
        </x-card>
    </div>
    
    <!-- Summary -->
    <div class="lg:col-span-2">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <x-stat-card value="{{ number_format($cashBalance->amount ?? 0, 0) }}" 
                         label="Cash Balance" 
                         icon="ik ik-dollar-sign" 
                         color="green" 
                         subtitle="TZS" />
            
            <x-stat-card value="{{ number_format($goldBalance->amount ?? 0, 2) . ' g' }}" 
                         label="Gold Balance" 
                         icon="fas fa-crown" 
                         color="amber" />
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
            <x-stat-card value="{{ number_format($totalTransactions) }}" 
                         label="Transactions" 
                         icon="ik ik-repeat" 
                         color="primary" />
            
            <x-stat-card value="{{ number_format($totalLoans) }}" 
                         label="Loans" 
                         icon="ik ik-file-text" 
                         color="blue" />
            
            <x-stat-card value="{{ number_format($totalExpenses, 0) }}" 
                         label="Total Expenses" 
                         icon="ik ik-dollar-sign" 
                         color="danger" 
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
                            <th class="px-3 py-2 font-medium">By</th>
                            <th class="px-3 py-2 font-medium">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($recentTransactions as $transaction)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 font-mono text-sm text-gray-600">{{ $transaction->transaction_code }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ ucfirst(str_replace('_', ' ', $transaction->transaction_type)) }}</td>
                            <td class="px-3 py-2 font-semibold {{ $transaction->transaction_type == 'gold_sale' ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format($transaction->amount, 0) }} TZS
                            </td>
                            <td class="px-3 py-2 text-gray-700">{{ $transaction->user->full_name ?? $transaction->user->username ?? 'N/A' }}</td>
                            <td class="px-3 py-2 text-gray-500">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-3 py-4 text-center text-gray-400">No transactions found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</div>
@endsection