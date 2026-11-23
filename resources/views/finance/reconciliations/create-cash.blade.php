@extends('layouts.main')

@section('title', 'New Cash Reconciliation')

@section('content')
<x-page-header title="New Cash Reconciliation" subtitle="Create a new cash reconciliation" icon="ik ik-dollar-sign"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Reconciliations' => route('finance.reconciliations.index'), 'Create' => null]" />

<div class="bg-white rounded-xl shadow-sm p-6">
    <form action="{{ route('finance.reconciliations.cash.store') }}" method="POST">
        @csrf
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Branch -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Branch <span class="text-red-500">*</span></label>
                <select name="branch_id" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent @error('branch_id') border-red-500 @enderror" required>
                    <option value="">Select Branch</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
                @error('branch_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Reconciliation Date -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Reconciliation Date <span class="text-red-500">*</span></label>
                <input type="date" name="reconciliation_date" value="{{ old('reconciliation_date', date('Y-m-d')) }}"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent @error('reconciliation_date') border-red-500 @enderror" required>
                @error('reconciliation_date')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Opening Balance -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Opening Balance (TZS) <span class="text-red-500">*</span></label>
                <input type="number" name="opening_balance" value="{{ old('opening_balance') }}" step="0.01" min="0"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent @error('opening_balance') border-red-500 @enderror"
                       placeholder="0.00" required>
                @error('opening_balance')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Closing Balance -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Closing Balance (TZS) <span class="text-red-500">*</span></label>
                <input type="number" name="closing_balance" value="{{ old('closing_balance') }}" step="0.01" min="0"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent @error('closing_balance') border-red-500 @enderror"
                       placeholder="0.00" required>
                @error('closing_balance')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            <!-- Notes -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="3" 
                          class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary-500 focus:border-transparent @error('notes') border-red-500 @enderror"
                          placeholder="Additional notes...">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
        
        <div class="flex items-center gap-3 mt-6 pt-6 border-t">
            <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                <i class="ik ik-check-circle mr-2"></i> Create Reconciliation
            </button>
            <a href="{{ route('finance.reconciliations.index') }}" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="ik ik-x-circle mr-2"></i> Cancel
            </a>
        </div>
    </form>
</div>
@endsection