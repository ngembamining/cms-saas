@extends('layouts.main')

@section('title', 'Cash Reconciliation Details')

@section('content')
<x-page-header title="Cash Reconciliation Details" subtitle="View cash reconciliation details" icon="ik ik-dollar-sign"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Reconciliations' => route('finance.reconciliations.index'), 'Details' => null]">
    <div class="flex gap-2">
        <a href="{{ route('finance.reconciliations.index') }}" class="btn btn-secondary">
            <i class="ik ik-arrow-left"></i> Back
        </a>
    </div>
</x-page-header>

<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="text-xs text-gray-500 uppercase">Branch</label>
            <p class="text-lg font-semibold">{{ $reconciliation->branch->name ?? 'N/A' }}</p>
        </div>
        <div>
            <label class="text-xs text-gray-500 uppercase">Reconciliation Date</label>
            <p class="text-lg font-semibold">{{ $reconciliation->reconciliation_date->format('d/m/Y') }}</p>
        </div>
        <div>
            <label class="text-xs text-gray-500 uppercase">Opening Balance</label>
            <p class="text-lg font-semibold text-blue-600">TZS {{ number_format($reconciliation->opening_balance, 0) }}</p>
        </div>
        <div>
            <label class="text-xs text-gray-500 uppercase">Closing Balance</label>
            <p class="text-lg font-semibold text-green-600">TZS {{ number_format($reconciliation->closing_balance, 0) }}</p>
        </div>
        <div>
            <label class="text-xs text-gray-500 uppercase">System Balance</label>
            <p class="text-lg font-semibold">TZS {{ number_format($reconciliation->system_balance ?? 0, 0) }}</p>
        </div>
        <div>
            <label class="text-xs text-gray-500 uppercase">Variance</label>
            <p class="text-lg font-semibold {{ $reconciliation->variance != 0 ? 'text-red-600' : 'text-green-600' }}">
                TZS {{ number_format($reconciliation->variance ?? 0, 0) }}
            </p>
        </div>
        <div>
            <label class="text-xs text-gray-500 uppercase">Status</label>
            <p><span class="{{ $reconciliation->status_badge }} px-3 py-1 rounded text-sm uppercase">{{ $reconciliation->status }}</span></p>
        </div>
        <div>
            <label class="text-xs text-gray-500 uppercase">Reconciled By</label>
            <p>{{ $reconciliation->reconciledBy->name ?? 'N/A' }}</p>
        </div>
        @if($reconciliation->verified_by)
        <div>
            <label class="text-xs text-gray-500 uppercase">Verified By</label>
            <p>{{ $reconciliation->verifiedBy->name ?? 'N/A' }}</p>
        </div>
        <div>
            <label class="text-xs text-gray-500 uppercase">Verified At</label>
            <p>{{ $reconciliation->verified_at ? $reconciliation->verified_at->format('d/m/Y H:i') : 'N/A' }}</p>
        </div>
        @endif
        @if($reconciliation->notes)
        <div class="md:col-span-2">
            <label class="text-xs text-gray-500 uppercase">Notes</label>
            <p class="text-gray-700">{{ $reconciliation->notes }}</p>
        </div>
        @endif
    </div>
</div>
@endsection