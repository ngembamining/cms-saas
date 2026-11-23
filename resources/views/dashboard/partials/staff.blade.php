<!-- Staff Stats -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat-card value="{{ $myTransactionCount }}" 
                 label="{{ __('My Transactions') }}" 
                 icon="ik ik-repeat" 
                 color="primary"
                 subtitle="Last 30 days" />
    
    <x-stat-card value="{{ $myNotifications }}" 
                 label="{{ __('Notifications') }}" 
                 icon="ik ik-bell" 
                 color="amber"
                 subtitle="{{ $myNotifications > 0 ? 'Unread' : 'All read' }}" />
    
    @if($branch)
        <x-stat-card value="{{ number_format($branchGold, 2) . ' g' }}" 
                     label="{{ __('Branch Gold') }}" 
                     icon="fas fa-crown" 
                     color="amber" />
        
        <x-stat-card value="{{ number_format($branchCash, 0) }}" 
                     label="{{ __('Branch Cash') }}" 
                     icon="ik ik-dollar-sign" 
                     color="green" />
    @else
        <x-stat-card value="N/A" 
                     label="{{ __('Branch') }}" 
                     icon="fas fa-store" 
                     color="gray" />
    @endif
</div>

<!-- My Recent Transactions -->
<div class="mt-5">
    <x-card no-padding hover>
        <x-slot:header>{{ __('My Recent Transactions') }}</x-slot:header>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium">{{ __('Transaction Code') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Type') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Amount') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Date') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($myTransactions as $transaction)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 text-sm font-mono text-gray-600">{{ $transaction->transaction_code }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ ucfirst(str_replace('_', ' ', $transaction->transaction_type)) }}</td>
                            <td class="px-5 py-3 font-semibold text-green-600">{{ number_format($transaction->amount, 0) }} TZS</td>
                            <td class="px-5 py-3">
                                <x-badge color="{{ $transaction->status == 'completed' ? 'success' : 'amber' }}">
                                    {{ ucfirst($transaction->status) }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-3 text-gray-500">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-3 text-center text-gray-400">No transactions yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>