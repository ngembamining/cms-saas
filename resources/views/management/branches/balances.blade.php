@extends('layouts.main')

@section('title', $branch->name . ' - Balances')

@section('content')
<x-page-header title="{{ $branch->name }}" subtitle="Branch balances" icon="ik ik-dollar-sign"
    :breadcrumbs="['Home' => url('dashboard'), 'Management' => route('management.users.index'), 'Branches' => route('management.branches.index'), $branch->name => route('management.branches.show', $branch->id), 'Balances' => null]">
    <a href="{{ route('management.branches.show', $branch->id) }}" class="btn btn-secondary">
        <i class="ik ik-arrow-left"></i> Back to Branch
    </a>
</x-page-header>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Cash Balance -->
    <x-card>
        <x-slot:header>{{ __('Cash Balance') }}</x-slot:header>
        <div class="text-center py-8">
            <p class="text-4xl font-bold text-green-600">{{ number_format($cashBalance->amount ?? 0, 0) }}</p>
            <p class="text-sm text-gray-500 mt-2">TZS</p>
            <p class="text-xs text-gray-400 mt-1">Minimum: {{ number_format($cashBalance->minimum_balance ?? 0, 0) }} TZS</p>
            <p class="text-xs text-gray-400">Last updated: {{ $cashBalance->last_updated ? $cashBalance->last_updated->format('d/m/Y H:i') : 'N/A' }}</p>
        </div>
    </x-card>

    <!-- Gold Balance -->
    <x-card>
        <x-slot:header>{{ __('Gold Balance') }}</x-slot:header>
        <div class="text-center py-8">
            <p class="text-4xl font-bold text-amber-600">{{ number_format($goldBalance->amount ?? 0, 2) }}</p>
            <p class="text-sm text-gray-500 mt-2">g</p>
            <p class="text-xs text-gray-400 mt-1">Minimum: {{ number_format($goldBalance->minimum_balance ?? 0, 2) }} g</p>
            <p class="text-xs text-gray-400">Last updated: {{ $goldBalance->last_updated ? $goldBalance->last_updated->format('d/m/Y H:i') : 'N/A' }}</p>
        </div>
    </x-card>
</div>
@endsection