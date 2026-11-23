@extends('layouts.main')

@section('title', 'Reconciliations')

@section('content')
<x-page-header title="Reconciliations" subtitle="Manage branch reconciliations" icon="ik ik-check-square"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Reconciliations' => null]">
    <div class="flex gap-2">
        <a href="{{ route('finance.reconciliations.cash.create') }}" class="btn btn-primary">
            <i class="ik ik-plus"></i> New Cash Reconciliation
        </a>
        <a href="{{ route('finance.reconciliations.gold.create') }}" class="btn btn-warning">
            <i class="ik ik-plus"></i> New Gold Reconciliation
        </a>
        <a href="{{ route('finance.reconciliations.print', request()->all()) }}" class="btn btn-info" target="_blank">
            <i class="ik ik-printer"></i> Print Report
        </a>
        <a href="{{ route('finance.reconciliations.export', request()->all()) }}" class="btn btn-success">
            <i class="ik ik-download"></i> Export Excel
        </a>
        <a href="{{ route('finance.reconciliations.export-pdf', request()->all()) }}" class="btn btn-danger" target="_blank">
            <i class="ik ik-file-text"></i> Export PDF
        </a>
    </div>
</x-page-header>

<!-- Statistics -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4 mb-6">
    <x-stat-card value="{{ number_format($totalReconciliations) }}" 
                 label="Total Reconciliations" 
                 icon="ik ik-list" 
                 color="primary" />
    
    <x-stat-card value="{{ number_format($pendingReconciliations) }}" 
                 label="Pending" 
                 icon="ik ik-clock" 
                 color="warning" />
    
    <x-stat-card value="{{ number_format($verifiedReconciliations) }}" 
                 label="Verified" 
                 icon="ik ik-check-circle" 
                 color="success" />
    
    <x-stat-card value="{{ number_format($rejectedReconciliations) }}" 
                 label="Rejected" 
                 icon="ik ik-x-circle" 
                 color="danger" />
</div>

<!-- Filter Section -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form action="{{ route('finance.reconciliations.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Branch</label>
            <select name="branch_id" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                <option value="">All Branches</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
            <select name="type" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                <option value="">All Types</option>
                <option value="cash" {{ request('type') == 'cash' ? 'selected' : '' }}>Cash</option>
                <option value="gold" {{ request('type') == 'gold' ? 'selected' : '' }}>Gold</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">From Date</label>
            <input type="date" name="from_date" value="{{ request('from_date') }}" 
                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">To Date</label>
            <input type="date" name="to_date" value="{{ request('to_date') }}" 
                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                <i class="ik ik-search mr-2"></i> Filter
            </button>
            <a href="{{ route('finance.reconciliations.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="ik ik-rotate-ccw"></i>
            </a>
        </div>
    </form>
</div>

<!-- Cash Reconciliations Table -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h4 class="text-lg font-semibold text-gray-800">
            <i class="ik ik-dollar-sign mr-2 text-green-600"></i> Cash Reconciliations
        </h4>
        <span class="text-sm text-gray-500">Total: {{ $cashReconciliations->total() }}</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 font-medium">#</th>
                    <th class="px-5 py-3 font-medium">Branch</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                    <th class="px-5 py-3 font-medium text-right">Opening</th>
                    <th class="px-5 py-3 font-medium text-right">Closing</th>
                    <th class="px-5 py-3 font-medium text-right">System Balance</th>
                    <th class="px-5 py-3 font-medium text-right">Variance</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($cashReconciliations as $reconciliation)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3">#{{ $reconciliation->id }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $reconciliation->branch->name ?? 'N/A' }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $reconciliation->reconciliation_date->format('d/m/Y') }}</td>
                    <td class="px-5 py-3 text-right">TZS {{ number_format($reconciliation->opening_balance, 0) }}</td>
                    <td class="px-5 py-3 text-right font-semibold">TZS {{ number_format($reconciliation->closing_balance, 0) }}</td>
                    <td class="px-5 py-3 text-right">TZS {{ number_format($reconciliation->system_balance ?? 0, 0) }}</td>
                    <td class="px-5 py-3 text-right {{ $reconciliation->variance != 0 ? 'text-red-600 font-semibold' : 'text-green-600' }}">
                        TZS {{ number_format($reconciliation->variance ?? 0, 0) }}
                    </td>
                    <td class="px-5 py-3">
                        @if($reconciliation->status == 'pending')
                            <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs">Pending</span>
                        @elseif($reconciliation->status == 'verified')
                            <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs">Verified</span>
                        @else
                            <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs">Rejected</span>
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        <a href="{{ route('finance.reconciliations.cash.show', $reconciliation->id) }}" 
                           class="text-blue-500 hover:text-blue-600 mr-2">
                            <i class="ik ik-eye"></i>
                        </a>
                        @if($reconciliation->status == 'pending')
                            @if(in_array(auth()->user()->role, ['director', 'accountant']))
                            <form action="{{ route('finance.reconciliations.cash.verify', $reconciliation->id) }}" 
                                  method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-green-500 hover:text-green-600 mr-2" 
                                        onclick="return confirm('Verify this reconciliation?')">
                                    <i class="ik ik-check"></i>
                                </button>
                            </form>
                            <form action="{{ route('finance.reconciliations.cash.reject', $reconciliation->id) }}" 
                                  method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-red-500 hover:text-red-600" 
                                        onclick="return confirm('Reject this reconciliation?')">
                                    <i class="ik ik-x"></i>
                                </button>
                            </form>
                            @endif
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-5 py-8 text-center text-gray-400">
                        <i class="ik ik-dollar-sign text-3xl block mb-2"></i>
                        No cash reconciliations found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $cashReconciliations->links() }}
    </div>
</div>

<!-- Gold Reconciliations Table -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="flex items-center justify-between mb-4">
        <h4 class="text-lg font-semibold text-gray-800">
            <i class="ik ik-trending-up mr-2 text-yellow-600"></i> Gold Reconciliations
        </h4>
        <span class="text-sm text-gray-500">Total: {{ $goldReconciliations->total() }}</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 font-medium">#</th>
                    <th class="px-5 py-3 font-medium">Branch</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                    <th class="px-5 py-3 font-medium text-right">Opening (g)</th>
                    <th class="px-5 py-3 font-medium text-right">Closing (g)</th>
                    <th class="px-5 py-3 font-medium text-right">Difference (g)</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($goldReconciliations as $reconciliation)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3">#{{ $reconciliation->id }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $reconciliation->branch->name ?? 'N/A' }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $reconciliation->reconciliation_date->format('d/m/Y') }}</td>
                    <td class="px-5 py-3 text-right">{{ number_format($reconciliation->opening_weight, 2) }}</td>
                    <td class="px-5 py-3 text-right font-semibold">{{ number_format($reconciliation->closing_weight, 2) }}</td>
                    <td class="px-5 py-3 text-right {{ $reconciliation->closing_weight != $reconciliation->opening_weight ? 'text-red-600 font-semibold' : 'text-green-600' }}">
                        {{ number_format($reconciliation->closing_weight - $reconciliation->opening_weight, 2) }}
                    </td>
                    <td class="px-5 py-3">
                        @if($reconciliation->verified_by)
                            <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs">Verified</span>
                        @else
                            <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs">Pending</span>
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        <a href="{{ route('finance.reconciliations.gold.show', $reconciliation->id) }}" 
                           class="text-blue-500 hover:text-blue-600 mr-2">
                            <i class="ik ik-eye"></i>
                        </a>
                        @if(!$reconciliation->verified_by)
                            @if(in_array(auth()->user()->role, ['director', 'accountant']))
                            <form action="{{ route('finance.reconciliations.gold.verify', $reconciliation->id) }}" 
                                  method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-green-500 hover:text-green-600 mr-2" 
                                        onclick="return confirm('Verify this reconciliation?')">
                                    <i class="ik ik-check"></i>
                                </button>
                            </form>
                            <form action="{{ route('finance.reconciliations.gold.reject', $reconciliation->id) }}" 
                                  method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-red-500 hover:text-red-600" 
                                        onclick="return confirm('Reject this reconciliation?')">
                                    <i class="ik ik-x"></i>
                                </button>
                            </form>
                            @endif
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-5 py-8 text-center text-gray-400">
                        <i class="ik ik-trending-up text-3xl block mb-2"></i>
                        No gold reconciliations found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $goldReconciliations->links() }}
    </div>
</div>
@endsection