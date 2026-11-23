@extends('layouts.main')
@section('title', 'Loans Report')
@section('content')

<div class="max-w-7xl mx-auto">
    <x-page-header title="{{ __('Loans Report') }}" 
                   subtitle="{{ __('Detailed loans analysis and tracking') }}" 
                   icon="ik ik-file-text"
                   :breadcrumbs="['Home' => route('dashboard'), 'Reports' => route('reports.index'), 'Loans' => null]">
        <div class="flex gap-2">
            <a href="{{ route('reports.loans.print', request()->all()) }}" class="btn btn-info" target="_blank">
                <i class="ik ik-printer"></i> Print Report
            </a>
            <a href="{{ route('reports.loans.export', request()->all()) }}" class="btn btn-success">
                <i class="ik ik-download"></i> Export Excel
            </a>
        </div>
    </x-page-header>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
        <form action="{{ route('reports.loans') }}" method="GET" class="flex flex-wrap items-end gap-4">
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
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Status') }}</label>
                <select name="status" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    <option value="">{{ __('All Status') }}</option>
                    <option value="pending" {{ ($status ?? null) == 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                    <option value="approved" {{ ($status ?? null) == 'approved' ? 'selected' : '' }}>{{ __('Approved') }}</option>
                    <option value="completed" {{ ($status ?? null) == 'completed' ? 'selected' : '' }}>{{ __('Completed') }}</option>
                    <option value="rejected" {{ ($status ?? null) == 'rejected' ? 'selected' : '' }}>{{ __('Rejected') }}</option>
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
            <a href="{{ route('reports.loans') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="ik ik-rotate-ccw mr-2"></i> {{ __('Reset') }}
            </a>
        </form>
    </div>

    <!-- Summary Cards - Row 1 -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5">
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">{{ __('Total Loans') }}</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($summary['total_loans'] ?? 0, 0) }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <i class="ik ik-file-text text-blue-600"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">{{ __('Amount Loaned Principal') }}</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($summary['principal_amount'] ?? 0, 0) }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <i class="ik ik-dollar-sign text-green-600"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">{{ __('Interest Expected') }}</p>
                    <p class="text-2xl font-bold text-purple-600">{{ number_format($summary['expected_interest'] ?? 0, 0) }}</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <i class="ik ik-trending-up text-purple-600"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-teal-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">{{ __('Total Paid (Active)') }}</p>
                    <p class="text-2xl font-bold text-teal-600">{{ number_format($summary['active_payments'] ?? 0, 0) }}</p>
                </div>
                <div class="p-3 bg-teal-100 rounded-full">
                    <i class="ik ik-credit-card text-teal-600"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-amber-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">{{ __('Interest Collected') }}</p>
                    <p class="text-2xl font-bold text-amber-600">{{ number_format($summary['interest_collected'] ?? 0, 0) }}</p>
                </div>
                <div class="p-3 bg-amber-100 rounded-full">
                    <i class="ik ik-activity text-amber-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards - Row 2 -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-4 mt-4">
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-indigo-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500">{{ __('Active Loans') }}</p>
                    <p class="text-xl font-bold text-indigo-600">{{ number_format($summary['active_loans'] ?? 0, 0) }}</p>
                </div>
                <i class="ik ik-check-circle text-indigo-500 text-xl"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500">{{ __('Completed') }}</p>
                    <p class="text-xl font-bold text-green-600">{{ number_format($summary['completed_loans'] ?? 0, 0) }}</p>
                </div>
                <i class="ik ik-check-square text-green-500 text-xl"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500">{{ __('Rejected') }}</p>
                    <p class="text-xl font-bold text-red-600">{{ number_format($summary['rejected_loans'] ?? 0, 0) }}</p>
                </div>
                <i class="ik ik-x-circle text-red-500 text-xl"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-pink-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500">{{ __('Avg Interest Rate') }}</p>
                    <p class="text-xl font-bold text-pink-600">{{ number_format($summary['avg_interest'] ?? 0, 2) }}%</p>
                </div>
                <i class="ik ik-percent text-pink-500 text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h5 class="font-semibold text-gray-700 mb-4">{{ __('Loans by Branch') }}</h5>
            <div class="space-y-3">
                @forelse($byBranch ?? [] as $branch)
                    <div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ $branch['branch'] }}</span>
                            <span class="font-medium text-gray-800">{{ number_format($branch['total'], 0) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-500 rounded-full h-2" style="width: {{ ($summary['principal_amount'] ?? 0) > 0 ? ($branch['total'] / ($summary['principal_amount'] ?? 1)) * 100 : 0 }}%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-400 mt-0.5">
                            <span>{{ $branch['count'] }} {{ __('loans') }}</span>
                            <span>{{ number_format($branch['avg_interest'] ?? 0, 1) }}%</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-400 py-4">{{ __('No data available') }}</div>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h5 class="font-semibold text-gray-700 mb-4">{{ __('Loans by Supplier') }}</h5>
            <div class="space-y-3 max-h-64 overflow-y-auto">
                @forelse(($bySupplier ?? [])->take(10) as $supplier)
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <div class="flex-1 min-w-0">
                            <span class="text-sm text-gray-600 truncate block">{{ $supplier['supplier'] }}</span>
                            <span class="text-xs text-gray-400">{{ $supplier['count'] }} {{ __('loans') }}</span>
                        </div>
                        <span class="text-sm font-medium text-gray-800">{{ number_format($supplier['total'], 0) }}</span>
                    </div>
                @empty
                    <div class="text-center text-gray-400 py-4">{{ __('No data available') }}</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Quick Summary -->
    <div class="bg-white rounded-xl shadow-sm p-6 mt-6">
        <h5 class="font-semibold text-gray-700 mb-4">{{ __('Quick Summary') }}</h5>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="border-r border-gray-100 pr-4">
                <span class="text-sm text-gray-500">{{ __('Outstanding Balance') }}</span>
                <p class="text-xl font-bold text-amber-600">{{ number_format($summary['outstanding_balance'] ?? 0, 0) }}</p>
            </div>
            <div class="border-r border-gray-100 pr-4">
                <span class="text-sm text-gray-500">{{ __('Average Duration') }}</span>
                <p class="text-xl font-bold text-gray-800">{{ number_format($summary['avg_duration'] ?? 0, 1) }} {{ __('months') }}</p>
            </div>
            <div class="border-r border-gray-100 pr-4">
                <span class="text-sm text-gray-500">{{ __('Total Suppliers') }}</span>
                <p class="text-xl font-bold text-gray-800">{{ $summary['total_suppliers'] ?? 0 }}</p>
            </div>
            <div>
                <span class="text-sm text-gray-500">{{ __('Pending Approvals') }}</span>
                <p class="text-xl font-bold text-blue-600">{{ number_format($summary['pending_approvals'] ?? 0, 0) }}</p>
            </div>
        </div>
    </div>

    <!-- Detailed Loans Table -->
    <div class="mt-6">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h5 class="font-semibold text-gray-700">{{ __('Loan Details') }}</h5>
                <span class="text-sm text-gray-400">{{ count($loans ?? []) }} {{ __('loans') }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-6 py-3 font-medium">{{ __('Loan ID') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Branch') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Supplier') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('Amount') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('Interest') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('Interest Amount') }}</th>
                            <th class="px-6 py-3 font-medium text-center">{{ __('Duration') }}</th>
                            <th class="px-6 py-3 font-medium text-center">{{ __('Status') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('Paid') }}</th>
                            <th class="px-6 py-3 font-medium text-center">{{ __('Date') }}</th>
                            <th class="px-6 py-3 font-medium text-center">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($loans ?? [] as $loan)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-3 font-mono text-xs text-gray-600">#{{ $loan->id }}</td>
                                <td class="px-6 py-3 text-gray-700">{{ $loan->branch->name ?? 'N/A' }}</td>
                                <td class="px-6 py-3 text-gray-700">{{ $loan->supplier->name ?? 'N/A' }}</td>
                                <td class="px-6 py-3 text-right font-medium text-gray-800">{{ number_format($loan->amount, 0) }}</td>
                                <td class="px-6 py-3 text-right text-gray-600">{{ number_format($loan->interest_rate, 1) }}%</td>
                                @php
                                    $interestAmount = $loan->amount * ($loan->interest_rate / 100);
                                @endphp
                                <td class="px-6 py-3 text-right font-medium text-purple-600">{{ number_format($interestAmount, 0) }}</td>
                                <td class="px-6 py-3 text-center text-gray-600">{{ $loan->duration_months }} {{ __('mo') }}</td>
                                <td class="px-6 py-3 text-center">
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-amber-100 text-amber-800',
                                            'approved' => 'bg-blue-100 text-blue-800',
                                            'completed' => 'bg-green-100 text-green-800',
                                            'rejected' => 'bg-red-100 text-red-800',
                                        ];
                                        $statusColor = $statusColors[$loan->status] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusColor }}">
                                        {{ ucfirst($loan->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-right font-medium text-teal-600">{{ number_format($loan->payments->sum('amount') ?? 0, 0) }}</td>
                                <td class="px-6 py-3 text-center text-xs text-gray-400">
                                    {{ $loan->created_at ? $loan->created_at->format('d/m/Y') : 'N/A' }}
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <a href="{{ route('loans.show', $loan->id) }}" class="text-primary-500 hover:text-primary-700 text-sm" title="View Details">
                                        <i class="ik ik-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-6 py-8 text-center text-gray-400">
                                    <i class="ik ik-file-text text-3xl block mb-2"></i>
                                    {{ __('No loans found for the selected period') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t border-gray-200 bg-gray-50">
                        <tr>
                            <td colspan="3" class="px-6 py-3 font-semibold text-gray-700">{{ __('Grand Total') }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-gray-800">{{ number_format($summary['principal_amount'] ?? 0, 0) }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-gray-800">{{ number_format($summary['avg_interest'] ?? 0, 1) }}%</td>
                            <td class="px-6 py-3 text-right font-semibold text-purple-600">{{ number_format($summary['expected_interest'] ?? 0, 0) }}</td>
                            <td colspan="2" class="px-6 py-3 text-center text-gray-400">{{ $summary['total_loans'] ?? 0 }} {{ __('loans') }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-teal-600">{{ number_format($summary['total_payments'] ?? 0, 0) }}</td>
                            <td colspan="2" class="px-6 py-3 text-center"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    @if(isset($loans) && method_exists($loans, 'links'))
        <div class="mt-6">
            {{ $loans->appends(request()->query())->links() }}
        </div>
    @endif
</div>

@endsection