<!-- Stat Cards -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat-card value="{{ number_format($totalBranches) }}" 
                 label="{{ __('Branches') }}" 
                 icon="fas fa-store" 
                 color="primary"
                 trend="+{{ $totalBranches > 0 ? '100' : '0' }}%" 
                 :spark="[8,12,9,14,11,16,13,18]" />
    
    <x-stat-card value="{{ number_format($totalUsers) }}" 
                 label="{{ __('Users') }}" 
                 icon="ik ik-users" 
                 color="green"
                 trend="+{{ $totalUsers > 0 ? '100' : '0' }}%" 
                 :spark="[5,8,6,9,12,10,14,17]" />
    
    <x-stat-card value="{{ number_format($totalGold, 2) . ' g' }}" 
                 label="{{ __('Gold Inventory') }}" 
                 icon="fas fa-crown" 
                 color="amber"
                 trend="{{ $totalGold > 0 ? '+100%' : '0%' }}" 
                 :spark="[10,9,11,10,13,12,15,16]" />
    
    <x-stat-card value="{{ number_format($totalCash, 0) }}" 
                 label="{{ __('Cash Balance') }}" 
                 icon="ik ik-dollar-sign" 
                 color="accent"
                 trend="{{ $totalCash > 0 ? '+100%' : '0%' }}" 
                 :spark="[14,12,15,11,13,10,12,9]" />
</div>

<!-- Loan Statistics -->
<div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat-card value="{{ $totalLoans }}" 
                 label="{{ __('Total Loans') }}" 
                 icon="ik ik-file-text" 
                 color="blue"
                 subtitle="{{ number_format($totalLoanAmount, 0) }} TZS" />
    
    <x-stat-card value="{{ $pendingLoans }}" 
                 label="{{ __('Pending Approval') }}" 
                 icon="ik ik-clock" 
                 color="amber"
                 subtitle="Awaiting review" />
    
    <x-stat-card value="{{ $approvedLoans }}" 
                 label="{{ __('Active Loans') }}" 
                 icon="ik ik-check-circle" 
                 color="green"
                 subtitle="Currently active" />
    
    <x-stat-card value="{{ $completedLoans }}" 
                 label="{{ __('Completed') }}" 
                 icon="ik ik-award" 
                 color="primary"
                 subtitle="Fully repaid" />
</div>

<!-- Charts Row -->
<div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
    <x-card class="xl:col-span-2" hover>
        <x-slot:header>{{ __('Revenue Overview (30 Days)') }}</x-slot:header>
        <x-slot:actions>
            <span class="inline-flex items-center gap-1.5 text-xs text-gray-500">
                <span class="h-2.5 w-2.5 rounded-full bg-primary-500"></span>{{ __('Sales') }}
                <span class="h-2.5 w-2.5 rounded-full bg-amber-500 ml-2"></span>{{ __('Purchases') }}
            </span>
        </x-slot:actions>
        <x-chart.line height="h-64" color="primary"
                      :data="$chartData['sales']"
                      :labels="$chartData['labels']"
                      prefix="" suffix=" TZS" />
    </x-card>

    <x-card hover>
        <x-slot:header>{{ __('Transaction Summary') }}</x-slot:header>
        <x-chart.donut label="{{ $recentTransactions }}" 
                       sublabel="{{ __('Total Transactions') }}"
                       :segments="[
                           ['value' => $recentGoldSales > 0 ? round(($recentGoldSales / ($recentGoldSales + $recentGoldPurchases + $recentExpenses)) * 100) : 0, 
                            'color' => 'green', 'label' => 'Gold Sales'],
                           ['value' => $recentGoldPurchases > 0 ? round(($recentGoldPurchases / ($recentGoldSales + $recentGoldPurchases + $recentExpenses)) * 100) : 0, 
                            'color' => 'amber', 'label' => 'Gold Purchases'],
                           ['value' => $recentExpenses > 0 ? round(($recentExpenses / ($recentGoldSales + $recentGoldPurchases + $recentExpenses)) * 100) : 0, 
                            'color' => 'accent', 'label' => 'Expenses'],
                       ]" />
    </x-card>
</div>

<!-- Recent Activity & Branch Performance -->
<div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
    <x-card hover>
        <x-slot:header>{{ __('Recent Activity') }}</x-slot:header>
        <div class="space-y-4 max-h-96 overflow-y-auto">
            @forelse($recentActivities as $activity)
                <div class="flex items-center gap-3 border-b border-gray-100 pb-3 last:border-0">
                    <span class="relative shrink-0">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-100 text-primary-600">
                            <i class="ik ik-{{ $activity->transaction_type == 'gold_sale' ? 'trending-up' : ($activity->transaction_type == 'gold_purchase' ? 'shopping-bag' : 'dollar-sign') }} text-sm"></i>
                        </span>
                    </span>
                    <div class="min-w-0 flex-1">
                        <h6 class="truncate text-sm font-semibold text-gray-700">
                            {{ ucfirst(str_replace('_', ' ', $activity->transaction_type)) }}
                        </h6>
                        <p class="truncate text-xs text-gray-400">
                            {{ $activity->branch->name ?? 'N/A' }} • {{ $activity->created_at->diffForHumans() }}
                        </p>
                    </div>
                    <span class="whitespace-nowrap text-sm font-semibold text-green-600">
                        {{ number_format($activity->amount, 0) }} TZS
                    </span>
                </div>
            @empty
                <p class="text-center text-gray-400 py-4">No recent activity</p>
            @endforelse
        </div>
    </x-card>

    <x-card class="xl:col-span-2" no-padding hover>
        <x-slot:header>{{ __('Branch Performance') }}</x-slot:header>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium">{{ __('Branch') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Gold (g)') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Cash (TZS)') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Transactions') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($branchPerformance as $branch)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-semibold text-gray-700">{{ $branch->name }}</td>
                            <td class="px-5 py-3 text-amber-600 font-medium">
                                {{ number_format($branch->balances->where('balance_type', 'gold')->first()->amount ?? 0, 2) }}
                            </td>
                            <td class="px-5 py-3 text-green-600 font-medium">
                                {{ number_format($branch->balances->where('balance_type', 'cash')->first()->amount ?? 0, 0) }}
                            </td>
                            <td class="px-5 py-3 text-gray-700">{{ $branch->transaction_count ?? 0 }}</td>
                            <td class="px-5 py-3">
                                <x-badge color="success">{{ __('Active') }}</x-badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-3 text-center text-gray-400">No branches found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>