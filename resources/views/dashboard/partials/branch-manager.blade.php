<!-- Branch Manager Stats -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat-card value="{{ $branchName }}" 
                 label="{{ __('Branch') }}" 
                 icon="fas fa-store" 
                 color="primary"
                 subtitle="{{ $staffCount }} Staff Members" />
    
    <x-stat-card value="{{ number_format($totalGold, 2) . ' g' }}" 
                 label="{{ __('Gold Inventory') }}" 
                 icon="fas fa-crown" 
                 color="amber"
                 trend="{{ $totalGold > 0 ? '+100%' : '0%' }}" />
    
    <x-stat-card value="{{ number_format($totalCash, 0) }}" 
                 label="{{ __('Cash Balance') }}" 
                 icon="ik ik-dollar-sign" 
                 color="green"
                 trend="{{ $totalCash > 0 ? '+100%' : '0%' }}" />
    
    <x-stat-card value="{{ number_format($todayRevenue, 0) }}" 
                 label="{{ __('Today\'s Revenue') }}" 
                 icon="ik ik-trending-up" 
                 color="accent"
                 subtitle="{{ $todayTransactions }} Transactions Today" />
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
                 subtitle="Awaiting your review" />
    
    <x-stat-card value="{{ $approvedLoans }}" 
                 label="{{ __('Active Loans') }}" 
                 icon="ik ik-check-circle" 
                 color="green"
                 subtitle="Currently active" />
</div>

<!-- Recent Activity & Quick Actions -->
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
                        <p class="truncate text-xs text-gray-400">{{ $activity->created_at->diffForHumans() }}</p>
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

    <x-card class="xl:col-span-2" hover>
        <x-slot:header>{{ __('Quick Actions') }}</x-slot:header>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('transactions.gold.purchases.create') }}" 
               class="flex flex-col items-center justify-center p-6 bg-amber-50 hover:bg-amber-100 rounded-xl transition">
                <i class="ik ik-shopping-bag text-3xl text-amber-600 mb-2"></i>
                <span class="text-sm font-medium text-gray-700">Record Purchase</span>
            </a>
            
            <a href="{{ route('transactions.gold.sales.create') }}" 
               class="flex flex-col items-center justify-center p-6 bg-green-50 hover:bg-green-100 rounded-xl transition">
                <i class="ik ik-trending-up text-3xl text-green-600 mb-2"></i>
                <span class="text-sm font-medium text-gray-700">Record Sale</span>
            </a>
            
            <a href="{{ route('loans.applications.create') }}" 
               class="flex flex-col items-center justify-center p-6 bg-blue-50 hover:bg-blue-100 rounded-xl transition">
                <i class="ik ik-file-text text-3xl text-blue-600 mb-2"></i>
                <span class="text-sm font-medium text-gray-700">Apply Loan</span>
            </a>
            
            <!-- FIXED: Changed from route('expenses.create') to route('finance.expenses.create') -->
            <a href="{{ route('finance.expenses.create') }}" 
               class="flex flex-col items-center justify-center p-6 bg-red-50 hover:bg-red-100 rounded-xl transition">
                <i class="ik ik-dollar-sign text-3xl text-red-600 mb-2"></i>
                <span class="text-sm font-medium text-gray-700">Add Expense</span>
            </a>
        </div>
    </x-card>
</div>

<!-- 30-Day Summary -->
<div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat-card value="{{ $recentTransactions }}" 
                 label="{{ __('Transactions (30d)') }}" 
                 icon="ik ik-repeat" 
                 color="blue" />
    
    <x-stat-card value="{{ number_format($recentGoldPurchases, 0) }}" 
                 label="{{ __('Gold Purchases') }}" 
                 icon="ik ik-shopping-bag" 
                 color="amber" />
    
    <x-stat-card value="{{ number_format($recentGoldSales, 0) }}" 
                 label="{{ __('Gold Sales') }}" 
                 icon="ik ik-trending-up" 
                 color="green" />
    
    <x-stat-card value="{{ number_format($recentExpenses, 0) }}" 
                 label="{{ __('Expenses') }}" 
                 icon="ik ik-dollar-sign" 
                 color="accent" />
</div>