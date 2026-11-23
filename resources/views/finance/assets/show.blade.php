@extends('layouts.main')

@section('title', 'Asset Details')

@section('content')
<x-page-header title="Asset Details" subtitle="View asset information" icon="ik ik-box"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Assets' => route('finance.assets.index'), 'Details' => null]">
    <div class="flex gap-2">
        <a href="{{ route('finance.assets.edit', $asset->id) }}" class="btn btn-primary">
            <i class="ik ik-edit"></i> Edit
        </a>
        <a href="{{ route('finance.assets.index') }}" class="btn btn-secondary">
            <i class="ik ik-arrow-left"></i> Back
        </a>
    </div>
</x-page-header>

<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Left Column -->
        <div>
            <div class="mb-4">
                <label class="text-xs text-gray-500 uppercase">Asset Code</label>
                <p class="text-lg font-mono font-semibold text-primary-600">{{ $asset->asset_code }}</p>
            </div>
            
            <div class="mb-4">
                <label class="text-xs text-gray-500 uppercase">Asset Name</label>
                <p class="text-lg font-semibold">{{ $asset->name }}</p>
            </div>
            
            <div class="mb-4">
                <label class="text-xs text-gray-500 uppercase">Category</label>
                <p><span class="bg-gray-100 px-2 py-1 rounded text-sm">{{ $asset->category }}</span></p>
            </div>
            
            <div class="mb-4">
                <label class="text-xs text-gray-500 uppercase">Description</label>
                <p class="text-gray-700">{{ $asset->description ?? 'No description' }}</p>
            </div>
            
            <div class="mb-4">
                <label class="text-xs text-gray-500 uppercase">Branch</label>
                <p>{{ $asset->branch->name ?? 'N/A' }}</p>
            </div>
            
            <div class="mb-4">
                <label class="text-xs text-gray-500 uppercase">Location</label>
                <p>{{ $asset->location ?? 'N/A' }}</p>
            </div>
        </div>
        
        <!-- Right Column -->
        <div>
            <div class="mb-4">
                <label class="text-xs text-gray-500 uppercase">Purchase Price</label>
                <p class="text-lg font-semibold text-blue-600">TZS {{ number_format($asset->purchase_price, 0) }}</p>
            </div>
            
            <div class="mb-4">
                <label class="text-xs text-gray-500 uppercase">Current Value</label>
                <p class="text-lg font-semibold text-green-600">TZS {{ number_format($asset->current_value, 0) }}</p>
            </div>
            
            <div class="mb-4">
                <label class="text-xs text-gray-500 uppercase">Depreciation</label>
                <p class="text-lg font-semibold text-red-600">TZS {{ number_format($asset->purchase_price - $asset->current_value, 0) }}</p>
            </div>
            
            <div class="mb-4">
                <label class="text-xs text-gray-500 uppercase">Purchase Date</label>
                <p>{{ $asset->purchase_date->format('d/m/Y') }}</p>
            </div>
            
            <div class="mb-4">
                <label class="text-xs text-gray-500 uppercase">Supplier</label>
                <p>{{ $asset->supplier ?? 'N/A' }}</p>
            </div>
            
            <div class="mb-4">
                <label class="text-xs text-gray-500 uppercase">Serial Number</label>
                <p class="font-mono">{{ $asset->serial_number ?? 'N/A' }}</p>
            </div>
            
            <div class="mb-4">
                <label class="text-xs text-gray-500 uppercase">Status</label>
                <p><span class="{{ $asset->status_badge }} px-3 py-1 rounded text-sm uppercase">{{ $asset->status }}</span></p>
            </div>
            
            <div class="mb-4">
                <label class="text-xs text-gray-500 uppercase">Assigned To</label>
                <p>{{ $asset->assignedTo->full_name ?? $asset->assignedTo->name ?? $asset->assignedTo->username ?? 'Unassigned' }}</p>
            </div>
            
            <div class="mb-4">
                <label class="text-xs text-gray-500 uppercase">Depreciation Rate</label>
                <p>{{ $asset->depreciation_rate }}% per year</p>
            </div>
            
            <div class="mb-4">
                <label class="text-xs text-gray-500 uppercase">Warranty Until</label>
                <p>{{ $asset->warranty_until ? date('d/m/Y', strtotime($asset->warranty_until)) : 'N/A' }}</p>
            </div>
            
            <div class="mb-4">
                <label class="text-xs text-gray-500 uppercase">Last Maintenance</label>
                <p>{{ $asset->last_maintenance ? date('d/m/Y', strtotime($asset->last_maintenance)) : 'N/A' }}</p>
            </div>
            
            <div class="mb-4">
                <label class="text-xs text-gray-500 uppercase">Next Maintenance</label>
                <p>{{ $asset->next_maintenance ? date('d/m/Y', strtotime($asset->next_maintenance)) : 'N/A' }}</p>
            </div>
            
            <div class="mb-4">
                <label class="text-xs text-gray-500 uppercase">Age</label>
                <p>{{ $asset->age_in_years }} years</p>
            </div>
            
            <div class="mb-4">
                <label class="text-xs text-gray-500 uppercase">Created By</label>
                <p>{{ $asset->creator->full_name ?? $asset->creator->name ?? $asset->creator->username ?? 'Unknown' }}</p>
            </div>
            
            @if($asset->notes)
            <div class="mb-4">
                <label class="text-xs text-gray-500 uppercase">Notes</label>
                <p class="text-gray-700">{{ $asset->notes }}</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection