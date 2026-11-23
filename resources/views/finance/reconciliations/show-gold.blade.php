@extends('layouts.main')

@section('title', 'Gold Reconciliation Details')

@section('content')
<x-page-header title="Gold Reconciliation Details" subtitle="View gold reconciliation details" icon="ik ik-trending-up"
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
            <label class="text-xs text-gray-500 uppercase">Opening Weight</label>
            <p class="text-lg font-semibold text-yellow-600">{{ number_format($reconciliation->opening_weight, 2) }} g</p>
        </div>
        <div>
            <label class="text-xs text-gray-500 uppercase">Closing Weight</label>
            <p class="text-lg font-semibold text-yellow-700">{{ number_format($reconciliation->closing_weight, 2) }} g</p>
        </div>
        <div>
            <label class="text-xs text-gray-500 uppercase">Difference</label>
            <p class="text-lg font-semibold {{ $reconciliation->opening_weight != $reconciliation->closing_weight ? 'text-red-600' : 'text-green-600' }}">
                {{ number_format($reconciliation->closing_weight - $reconciliation->opening_weight, 2) }} g
            </p>
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