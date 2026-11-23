@extends('layouts.main')

@section('title', 'Branch Balances')

@section('content')
<x-page-header title="Branch Balances" subtitle="View branch cash and gold balances" icon="ik ik-dollar-sign"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Balances' => null]" />

<!-- Statistics -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4 mb-6">
    <x-stat-card value="{{ number_format($totalCash, 0) }}" 
                 label="Total Cash" 
                 icon="ik ik-dollar-sign" 
                 color="green" 
                 subtitle="TZS" />
    
    <x-stat-card value="{{ number_format($totalGold, 2) . ' g' }}" 
                 label="Total Gold" 
                 icon="fas fa-crown" 
                 color="amber" />
    
    <x-stat-card value="{{ $totalBranches }}" 
                 label="Total Branches" 
                 icon="fas fa-store" 
                 color="primary" />
</div>

<!-- Balances Table -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 font-medium">Branch</th>
                    <th class="px-5 py-3 font-medium">Location</th>
                    <th class="px-5 py-3 font-medium">Cash Balance (TZS)</th>
                    <th class="px-5 py-3 font-medium">Gold Balance (g)</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($branches as $branch)
                @php
                    $cash = $branch->balances->where('balance_type', 'cash')->first();
                    $gold = $branch->balances->where('balance_type', 'gold')->first();
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-semibold text-gray-700">{{ $branch->name }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $branch->location ?? 'N/A' }}</td>
                    <td class="px-5 py-3 font-medium text-green-600">
                        {{ number_format($cash->amount ?? 0, 0) }}
                    </td>
                    <td class="px-5 py-3 font-medium text-amber-600">
                        {{ number_format($gold->amount ?? 0, 2) }}
                    </td>
                    <td class="px-5 py-3">
                        @if(($cash->amount ?? 0) > 0 || ($gold->amount ?? 0) > 0)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-gray">Inactive</span>
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        <a href="{{ route('finance.balances.branch', $branch->id) }}" 
                           class="text-blue-500 hover:text-blue-600">
                            <i class="ik ik-eye"></i> View Details
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-8 text-center text-gray-400">
                        <i class="ik ik-dollar-sign text-3xl block mb-2"></i>
                        No branches found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection