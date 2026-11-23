@extends('layouts.main')

@section('title', 'Assets Management')

@section('content')
<x-page-header title="Assets" subtitle="Manage company assets" icon="ik ik-box"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Assets' => null]">
    <div class="flex gap-2">
        <a href="{{ route('finance.assets.create') }}" class="btn btn-primary">
            <i class="ik ik-plus"></i> New Asset
        </a>
        <a href="{{ route('finance.assets.print', request()->all()) }}" class="btn btn-info" target="_blank">
            <i class="ik ik-printer"></i> Print Report
        </a>
        <a href="{{ route('finance.assets.export', request()->all()) }}" class="btn btn-success">
            <i class="ik ik-download"></i> Export CSV
        </a>
    </div>
</x-page-header>

<!-- Statistics -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4 mb-6">
    <x-stat-card value="{{ number_format($totalAssets) }}" 
                 label="Total Assets" 
                 icon="ik ik-box" 
                 color="primary" />
    
    <x-stat-card value="TZS {{ number_format($totalValue, 0) }}" 
                 label="Total Current Value" 
                 icon="ik ik-dollar-sign" 
                 color="success" 
                 subtitle="Purchase: TZS {{ number_format($totalPurchaseValue, 0) }}" />
    
    <x-stat-card value="TZS {{ number_format($totalPurchaseValue - $totalValue, 0) }}" 
                 label="Total Depreciation" 
                 icon="ik ik-trending-down" 
                 color="danger" />
    
    <x-stat-card value="{{ number_format($totalAssets > 0 ? ($totalValue / $totalAssets) : 0, 0) }}" 
                 label="Average Asset Value" 
                 icon="ik ik-bar-chart" 
                 color="info" />
</div>

<!-- Filter Section -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form action="{{ route('finance.assets.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" 
                   placeholder="Search assets..." 
                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
            <select name="category" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>
                        {{ $category }}
                    </option>
                @endforeach
            </select>
        </div>
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
            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
            <select name="status" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                <option value="">All Status</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                        {{ ucfirst($status) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                <i class="ik ik-search mr-2"></i> Filter
            </button>
            <a href="{{ route('finance.assets.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="ik ik-rotate-ccw"></i>
            </a>
        </div>
    </form>
</div>

<!-- Assets Table -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 font-medium">Asset Code</th>
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Category</th>
                    <th class="px-5 py-3 font-medium">Branch</th>
                    <th class="px-5 py-3 font-medium text-right">Purchase Price</th>
                    <th class="px-5 py-3 font-medium text-right">Current Value</th>
                    <th class="px-5 py-3 font-medium text-right">Depreciation</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($assets as $asset)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-mono text-xs">{{ $asset->asset_code }}</td>
                    <td class="px-5 py-3 font-medium text-gray-700">{{ $asset->name }}</td>
                    <td class="px-5 py-3">
                        <span class="bg-gray-100 px-2 py-1 rounded text-xs">{{ $asset->category }}</span>
                    </td>
                    <td class="px-5 py-3 text-gray-700">{{ $asset->branch->name ?? 'N/A' }}</td>
                    <td class="px-5 py-3 text-right text-gray-600">TZS {{ number_format($asset->purchase_price, 0) }}</td>
                    <td class="px-5 py-3 text-right font-semibold text-green-600">TZS {{ number_format($asset->current_value, 0) }}</td>
                    <td class="px-5 py-3 text-right text-red-500">TZS {{ number_format($asset->purchase_price - $asset->current_value, 0) }}</td>
                    <td class="px-5 py-3">
                        <span class="{{ $asset->status_badge }} px-2 py-1 rounded text-xs uppercase">
                            {{ $asset->status }}
                        </span>
                    </td>
                    <td class="px-5 py-3">
                        <a href="{{ route('finance.assets.show', $asset->id) }}" 
                           class="text-blue-500 hover:text-blue-600 mr-2">
                            <i class="ik ik-eye"></i>
                        </a>
                        <a href="{{ route('finance.assets.edit', $asset->id) }}" 
                           class="text-green-500 hover:text-green-600 mr-2">
                            <i class="ik ik-edit"></i>
                        </a>
                        <form action="{{ route('finance.assets.destroy', $asset->id) }}" 
                              method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-600" 
                                    onclick="return confirm('Delete this asset?')">
                                <i class="ik ik-trash-2"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-5 py-8 text-center text-gray-400">
                        <i class="ik ik-box text-3xl block mb-2"></i>
                        No assets found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $assets->links() }}
    </div>
</div>
@endsection