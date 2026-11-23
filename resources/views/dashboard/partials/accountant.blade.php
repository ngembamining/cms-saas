<!-- Accountant Stats -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat-card value="{{ number_format($totalCash ?? 0, 0) }}" 
                 label="{{ __('Total Cash') }}" 
                 icon="ik ik-dollar-sign" 
                 color="green" />
    
    <x-stat-card value="{{ number_format($totalGold ?? 0, 2) . ' g' }}" 
                 label="{{ __('Total Gold') }}" 
                 icon="fas fa-crown" 
                 color="amber" />
    
    <x-stat-card value="{{ number_format($monthlySales ?? 0, 0) }}" 
                 label="{{ __('Revenue (30d)') }}" 
                 icon="ik ik-trending-up" 
                 color="primary" />
    
    <x-stat-card value="{{ number_format($monthlyExpenses ?? 0, 0) }}" 
                 label="{{ __('Expenses (30d)') }}" 
                 icon="ik ik-dollar-sign" 
                 color="accent" />
</div>

<!-- Financial Summary -->
<div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-2xl p-6 text-white shadow-lg">
        <p class="text-sm opacity-90">{{ __('Net Profit (30d)') }}</p>
        <p class="text-3xl font-bold">{{ number_format(($monthlySales ?? 0) - ($monthlyExpenses ?? 0), 0) }} TZS</p>
        <p class="text-sm opacity-80 mt-2">{{ __('Revenue - Expenses') }}</p>
    </div>
    
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <p class="text-sm text-gray-500">{{ __('Total Loan Portfolio') }}</p>
        <p class="text-2xl font-bold text-gray-800">{{ number_format($totalLoanAmount ?? 0, 0) }} TZS</p>
        <p class="text-sm text-gray-500 mt-2">{{ $activeLoans ?? 0 }} active loans</p>
    </div>
    
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <p class="text-sm text-gray-500">{{ __('Pending Tasks') }}</p>
        <p class="text-2xl font-bold text-amber-600">{{ $pendingReconciliations ?? 0 }}</p>
        <p class="text-sm text-gray-500 mt-2">{{ __('Reconciliations pending') }}</p>
    </div>
</div>

<!-- Branch Balances -->
<div class="mt-5">
    <x-card no-padding hover>
        <x-slot:header>{{ __('Branch Balances') }}</x-slot:header>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium">{{ __('Branch') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Gold (g)') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Cash (TZS)') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($branches ?? [] as $branch)
                        @php
                            $gold = $branch->balances->where('balance_type', 'gold')->first()->amount ?? 0;
                            $cash = $branch->balances->where('balance_type', 'cash')->first()->amount ?? 0;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-semibold text-gray-700">{{ $branch->name ?? 'N/A' }}</td>
                            <td class="px-5 py-3 text-amber-600 font-medium">{{ number_format($gold, 2) }}</td>
                            <td class="px-5 py-3 text-green-600 font-medium">{{ number_format($cash, 0) }}</td>
                            <td class="px-5 py-3">
                                <x-badge color="success">{{ __('Active') }}</x-badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-3 text-center text-gray-400">No branch balances found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>