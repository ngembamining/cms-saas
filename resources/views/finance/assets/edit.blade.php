@extends('layouts.main')

@section('title', 'Edit Asset')

@section('content')
<x-page-header title="Edit Asset" subtitle="Update asset information" icon="ik ik-edit"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Assets' => route('finance.assets.index'), 'Edit' => null]" />

<div class="bg-white rounded-xl shadow-sm p-6">
    <form action="{{ route('finance.assets.update', $asset->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Asset Name -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Asset Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $asset->name) }}" 
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent @error('name') border-red-500 @enderror"
                       placeholder="e.g. Dell Laptop Latitude 7420" required>
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Category -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                <select name="category" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent @error('category') border-red-500 @enderror" required>
                    <option value="">Select Category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}" {{ old('category', $asset->category) == $category ? 'selected' : '' }}>
                            {{ $category }}
                        </option>
                    @endforeach
                </select>
                @error('category')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Description -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3" 
                          class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent @error('description') border-red-500 @enderror"
                          placeholder="Asset description...">{{ old('description', $asset->description) }}</textarea>
                @error('description')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Purchase Price -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Price (TZS) <span class="text-red-500">*</span></label>
                <input type="number" name="purchase_price" value="{{ old('purchase_price', $asset->purchase_price) }}" step="0.01" min="0"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent @error('purchase_price') border-red-500 @enderror"
                       placeholder="e.g. 3200000" required>
                @error('purchase_price')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Current Value -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Current Value (TZS) <span class="text-red-500">*</span></label>
                <input type="number" name="current_value" value="{{ old('current_value', $asset->current_value) }}" step="0.01" min="0"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent @error('current_value') border-red-500 @enderror"
                       placeholder="e.g. 2560000" required>
                @error('current_value')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Purchase Date -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Date <span class="text-red-500">*</span></label>
                <input type="date" name="purchase_date" value="{{ old('purchase_date', $asset->purchase_date->format('Y-m-d')) }}"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent @error('purchase_date') border-red-500 @enderror" required>
                @error('purchase_date')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Supplier -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                <input type="text" name="supplier" value="{{ old('supplier', $asset->supplier) }}"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent @error('supplier') border-red-500 @enderror"
                       placeholder="e.g. Dell Technologies">
                @error('supplier')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Serial Number -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Serial Number</label>
                <input type="text" name="serial_number" value="{{ old('serial_number', $asset->serial_number) }}"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent @error('serial_number') border-red-500 @enderror"
                       placeholder="e.g. DELL-LAT-7420-004">
                @error('serial_number')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Location -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                <input type="text" name="location" value="{{ old('location', $asset->location) }}"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent @error('location') border-red-500 @enderror"
                       placeholder="e.g. Main Office, IT Room">
                @error('location')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Branch -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Branch <span class="text-red-500">*</span></label>
                <select name="branch_id" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent @error('branch_id') border-red-500 @enderror" required>
                    <option value="">Select Branch</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id', $asset->branch_id) == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
                @error('branch_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Assigned To -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Assigned To</label>
                <select name="assigned_to" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent @error('assigned_to') border-red-500 @enderror">
                    <option value="">Unassigned</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('assigned_to', $asset->assigned_to) == $user->id ? 'selected' : '' }}>
                            {{ $user->full_name ?? $user->name ?? $user->username }}
                        </option>
                    @endforeach
                </select>
                @error('assigned_to')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                <select name="status" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent @error('status') border-red-500 @enderror" required>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ old('status', $asset->status) == $status ? 'selected' : '' }}>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
                @error('status')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Depreciation Rate -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Depreciation Rate (%)</label>
                <input type="number" name="depreciation_rate" value="{{ old('depreciation_rate', $asset->depreciation_rate) }}" step="0.01" min="0" max="100"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent @error('depreciation_rate') border-red-500 @enderror"
                       placeholder="e.g. 20">
                @error('depreciation_rate')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Warranty Until -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Warranty Until</label>
                <input type="date" name="warranty_until" value="{{ old('warranty_until', $asset->warranty_until) }}"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent @error('warranty_until') border-red-500 @enderror">
                @error('warranty_until')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Last Maintenance -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Last Maintenance</label>
                <input type="date" name="last_maintenance" value="{{ old('last_maintenance', $asset->last_maintenance) }}"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent @error('last_maintenance') border-red-500 @enderror">
                @error('last_maintenance')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Next Maintenance -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Next Maintenance</label>
                <input type="date" name="next_maintenance" value="{{ old('next_maintenance', $asset->next_maintenance) }}"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent @error('next_maintenance') border-red-500 @enderror">
                @error('next_maintenance')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Notes -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="2" 
                          class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent @error('notes') border-red-500 @enderror"
                          placeholder="Additional notes...">{{ old('notes', $asset->notes) }}</textarea>
                @error('notes')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
        
        <div class="flex items-center gap-3 mt-6 pt-6 border-t">
            <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                <i class="ik ik-check-circle mr-2"></i> Update Asset
            </button>
            <a href="{{ route('finance.assets.index') }}" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="ik ik-x-circle mr-2"></i> Cancel
            </a>
        </div>
    </form>
</div>
@endsection