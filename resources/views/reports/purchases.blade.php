@extends('layouts.main')
@section('title', 'Purchases Report')
@section('content')

<div class="max-w-7xl mx-auto">
    <x-page-header title="{{ __('Purchases Report') }}" 
                   subtitle="{{ __('Detailed purchases analysis') }}" 
                   icon="ik ik-shopping-bag"
                   :breadcrumbs="['Home' => route('dashboard'), 'Reports' => route('reports.index'), 'Purchases' => null]" />

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
        <form action="{{ route('reports.purchases') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Start Date') }}</label>
                <input type="date" name="start_date" value="{{ $startDate ?? now()->startOfMonth()->format('Y-m-d') }}" 
                       class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('End Date') }}</label>
                <input type="date" name="end_date" value="{{ $endDate ?? now()->endOfMonth()->format('Y-m-d') }}" 
                       class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Branch') }}</label>
                <select name="branch_id" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    <option value="">{{ __('All Branches') }}</option>
                    @foreach($branches ?? [] as $branch)
                        <option value="{{ $branch->id }}" {{ ($branchId ?? null) == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Supplier') }}</label>
                <select name="supplier_id" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    <option value="">{{ __('All Suppliers') }}</option>
                    @foreach($suppliers ?? [] as $supplier)
                        <option value="{{ $supplier->id }}" {{ ($supplierId ?? null) == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition">
                <i class="ik ik-search mr-2"></i> {{ __('Apply') }}
            </button>
            <a href="{{ route('reports.purchases') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="ik ik-rotate-ccw mr-2"></i> {{ __('Reset') }}
            </a>
            <a href="{{ route('reports.export', ['type' => 'purchases', 'start_date' => $startDate ?? now()->startOfMonth()->format('Y-m-d'), 'end_date' => $endDate ?? now()->endOfMonth()->format('Y-m-d')]) }}" 
               class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition ml-auto">
                <i class="ik ik-download mr-2"></i> {{ __('Export') }}
            </a>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card value="{{ number_format($summary['total_purchases'] ?? 0, 0) }}" 
                     label="{{ __('Total Purchases') }}" 
                     icon="ik ik-shopping-bag" 
                     color="blue" />
        
        <x-stat-card value="{{ number_format($summary['total_gold'] ?? 0, 2) . ' g' }}" 
                     label="{{ __('Total Gold Purchased') }}" 
                     icon="fas fa-crown" 
                     color="amber" />
        
        <x-stat-card value="{{ number_format($summary['total_transactions'] ?? 0, 0) }}" 
                     label="{{ __('Transactions') }}" 
                     icon="ik ik-file-text" 
                     color="green" />
        
        <x-stat-card value="{{ number_format($summary['average_purchase'] ?? 0, 0) }}" 
                     label="{{ __('Average Purchase') }}" 
                     icon="ik ik-dollar-sign" 
                     color="purple" />
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h5 class="font-semibold text-gray-700 mb-4">{{ __('Purchases by Branch') }}</h5>
            <div class="space-y-3">
                @forelse($byBranch ?? [] as $branch)
                    <div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ $branch['branch'] }}</span>
                            <span class="font-medium text-gray-800">{{ number_format($branch['total'], 0) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-500 rounded-full h-2" style="width: {{ ($summary['total_purchases'] ?? 0) > 0 ? ($branch['total'] / ($summary['total_purchases'] ?? 1)) * 100 : 0 }}%"></div>
                        </div>
                        @if(isset($branch['gold']) && $branch['gold'] > 0)
                            <div class="text-xs text-gray-400 mt-0.5">{{ number_format($branch['gold'], 2) }} g</div>
                        @endif
                    </div>
                @empty
                    <div class="text-center text-gray-400 py-4">{{ __('No data available') }}</div>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h5 class="font-semibold text-gray-700 mb-4">{{ __('Purchases by Supplier') }}</h5>
            <div class="space-y-3 max-h-64 overflow-y-auto">
                @forelse(($bySupplier ?? [])->take(10) as $supplier)
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <div class="flex-1 min-w-0">
                            <span class="text-sm text-gray-600 truncate block">{{ $supplier['supplier'] }}</span>
                            @if(isset($supplier['gold']) && $supplier['gold'] > 0)
                                <span class="text-xs text-gray-400">{{ number_format($supplier['gold'], 2) }} g</span>
                            @endif
                        </div>
                        <span class="text-sm font-medium text-gray-800">{{ number_format($supplier['total'], 0) }}</span>
                    </div>
                @empty
                    <div class="text-center text-gray-400 py-4">{{ __('No data available') }}</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Daily Trends Table -->
    <div class="mt-6">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h5 class="font-semibold text-gray-700">{{ __('Daily Trends') }}</h5>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-6 py-3 font-medium">{{ __('Date') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('Amount') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('Gold (g)') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('Transactions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($dailyTrends ?? [] as $day)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-gray-700">{{ \Carbon\Carbon::parse($day['date'])->format('d M Y') }}</td>
                                <td class="px-6 py-3 text-right font-medium text-gray-800">{{ number_format($day['amount'], 0) }}</td>
                                <td class="px-6 py-3 text-right text-gray-600">{{ number_format($day['gold'] ?? 0, 2) }}</td>
                                <td class="px-6 py-3 text-right text-gray-600">{{ $day['count'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                                    <i class="ik ik-shopping-bag text-3xl block mb-2"></i>
                                    {{ __('No purchases found for the selected period') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t border-gray-200 bg-gray-50">
                        <tr>
                            <td class="px-6 py-3 font-semibold text-gray-700">{{ __('Total') }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-gray-800">{{ number_format($summary['total_purchases'] ?? 0, 0) }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-gray-800">{{ number_format($summary['total_gold'] ?? 0, 2) }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-gray-800">{{ $summary['total_transactions'] ?? 0 }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection