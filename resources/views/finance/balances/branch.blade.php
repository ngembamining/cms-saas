@extends('layouts.main')

@section('title', $branch->name . ' - Branch Balance')

@section('content')
<x-page-header title="{{ $branch->name }}" subtitle="Branch balance details" icon="fas fa-store"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), $branch->name => null]" />

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Balance Cards -->
    <div class="lg:col-span-2">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
        
        <!-- Recent Transactions -->
        <div class="bg-white rounded-xl shadow-sm p-6 mt-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Transactions</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-5 py-3 font-medium">Code</th>
                            <th class="px-5 py-3 font-medium">Type</th>
                            <th class="px-5 py-3 font-medium">Amount</th>
                            <th class="px-5 py-3 font-medium">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($recentTransactions as $transaction)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-mono text-sm text-gray-600">{{ $transaction->transaction_code }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ ucfirst(str_replace('_', ' ', $transaction->transaction_type)) }}</td>
                            <td class="px-5 py-3 font-semibold {{ $transaction->transaction_type == 'gold_sale' || $transaction->transaction_type == 'cash_deposit' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $transaction->transaction_type == 'gold_sale' || $transaction->transaction_type == 'cash_deposit' ? '+' : '-' }}
                                {{ number_format($transaction->amount, 0) }} TZS
                            </td>
                            <td class="px-5 py-3 text-gray-500">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-5 py-4 text-center text-gray-400">No transactions found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Branch Info -->
    <div class="lg:col-span-1">
        <x-card>
            <x-slot:header>{{ __('Branch Information') }}</x-slot:header>
            <div class="space-y-3 text-sm">
                <div>
                    <p class="text-gray-500">Branch Name</p>
                    <p class="font-medium">{{ $branch->name }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Location</p>
                    <p class="font-medium">{{ $branch->location ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Status</p>
                    <p>
                        @if(($cashBalance->amount ?? 0) > 0 || ($goldBalance->amount ?? 0) > 0)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-gray">Inactive</span>
                        @endif
                    </p>
                </div>
                
                <!-- Adjustment Form - Only for Directors and Accountants -->
                @if(in_array(Auth::user()->role, ['director', 'accountant']))
                <div class="border-t border-gray-100 pt-3">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Adjust Balance</h4>
                    <form method="POST" action="{{ route('finance.balances.adjust', $branch->id) }}" class="space-y-2">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
                            <select name="balance_type" class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                                <option value="cash">Cash</option>
                                <option value="gold">Gold</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Action</label>
                            <select name="adjustment_type" class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                                <option value="add">Add (+)</option>
                                <option value="subtract">Subtract (-)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Amount</label>
                            <input type="number" name="amount" step="0.01" min="0.01" 
                                   class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                                   placeholder="Enter amount" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Reason</label>
                            <input type="text" name="reason" 
                                   class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                                   placeholder="Reason for adjustment">
                        </div>
                        <button type="submit" class="w-full rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-amber-600">
                            <i class="ik ik-edit-2"></i> Apply Adjustment
                        </button>
                    </form>
                </div>
                @endif
                
                <div class="border-t border-gray-100 pt-3">
                    <a href="{{ route('finance.balances.index') }}" class="text-primary-600 hover:text-primary-700">
                        <i class="ik ik-arrow-left"></i> Back to Balances
                    </a>
                </div>
            </div>
        </x-card>
    </div>
</div>
@endsection