@extends('layouts.main')

@section('title', 'Loan Application Details')

@section('content')
<x-page-header title="Loan Application Details" subtitle="View loan application details" icon="ik ik-eye"
    :breadcrumbs="['Home' => url('dashboard'), 'Loans' => route('loans.index'), 'Applications' => route('loans.applications.index'), 'Details' => null]">
    <div class="flex gap-2">
        <a href="{{ route('loans.applications.index') }}" class="btn btn-secondary">
            <i class="ik ik-arrow-left"></i> Back
        </a>
        @if($loan->status == 'pending')
            <a href="{{ route('loans.applications.edit', $loan->id) }}" class="btn btn-warning">
                <i class="ik ik-edit"></i> Edit
            </a>
            @can('loan.approve')
                <a href="{{ route('loans.approvals.index') }}" class="btn btn-success">
                    <i class="ik ik-check-circle"></i> Review
                </a>
            @endcan
        @endif
    </div>
</x-page-header>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Details -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Application Information</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Application ID</p>
                    <p class="font-semibold">#{{ $loan->id }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Status</p>
                    <p>
                        @if($loan->status == 'pending')
                            <span class="badge badge-amber">Pending</span>
                        @elseif($loan->status == 'approved')
                            <span class="badge badge-success">Approved</span>
                        @elseif($loan->status == 'rejected')
                            <span class="badge badge-danger">Rejected</span>
                        @elseif($loan->status == 'completed')
                            <span class="badge badge-primary">Completed</span>
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Application Date</p>
                    <p class="font-medium">{{ $loan->application_date ? $loan->application_date->format('d/m/Y') : 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Submitted By</p>
                    <p class="font-medium">{{ $loan->creator->full_name ?? $loan->creator->username ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-6 mt-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Loan Details</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Branch</p>
                    <p class="font-medium">{{ $loan->branch->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Supplier</p>
                    <p class="font-medium">{{ $loan->supplier->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Amount</p>
                    <p class="font-bold text-lg text-primary-600">{{ number_format($loan->amount, 0) }} TZS</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Interest Rate</p>
                    <p class="font-medium">{{ $loan->interest_rate }}%</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Duration</p>
                    <p class="font-medium">{{ $loan->duration_months }} months</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total Payable</p>
                    @php
                        $totalPayable = $loan->amount + ($loan->amount * $loan->interest_rate / 100);
                    @endphp
                    <p class="font-bold text-green-600">{{ number_format($totalPayable, 0) }} TZS</p>
                </div>
                @if($loan->description)
                <div class="col-span-2">
                    <p class="text-sm text-gray-500">Description</p>
                    <p class="font-medium">{{ $loan->description }}</p>
                </div>
                @endif
                @if($loan->comments)
                <div class="col-span-2">
                    <p class="text-sm text-gray-500">Comments</p>
                    <p class="font-medium">{{ $loan->comments }}</p>
                </div>
                @endif
                @if($loan->approved_date)
                <div>
                    <p class="text-sm text-gray-500">Approved Date</p>
                    <p class="font-medium">{{ $loan->approved_date->format('d/m/Y') }}</p>
                </div>
                @endif
                @if($loan->approver)
                <div>
                    <p class="text-sm text-gray-500">Approved By</p>
                    <p class="font-medium">{{ $loan->approver->full_name ?? $loan->approver->username ?? 'N/A' }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Summary Card -->
    <div class="lg:col-span-1">
        <div class="bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl p-6 text-white">
            <h4 class="text-sm font-medium opacity-90">Loan Summary</h4>
            <div class="mt-4 space-y-3">
                <div>
                    <p class="text-xs opacity-80">Principal Amount</p>
                    <p class="text-2xl font-bold">{{ number_format($loan->amount, 0) }} TZS</p>
                </div>
                <div>
                    <p class="text-xs opacity-80">Interest</p>
                    <p class="text-xl font-bold">{{ $loan->interest_rate }}%</p>
                </div>
                <div>
                    <p class="text-xs opacity-80">Total Payable</p>
                    @php
                        $totalPayable = $loan->amount + ($loan->amount * $loan->interest_rate / 100);
                    @endphp
                    <p class="text-2xl font-bold">{{ number_format($totalPayable, 0) }} TZS</p>
                </div>
                <div>
                    <p class="text-xs opacity-80">Duration</p>
                    <p class="text-xl font-bold">{{ $loan->duration_months }} months</p>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-white/20">
                <p class="text-xs opacity-80">Status</p>
                <p class="font-semibold">
                    @if($loan->status == 'pending')
                        ⏳ Awaiting Review
                    @elseif($loan->status == 'approved')
                        ✅ Approved
                    @elseif($loan->status == 'rejected')
                        ❌ Rejected
                    @elseif($loan->status == 'completed')
                        🎯 Completed
                    @endif
                </p>
            </div>
        </div>
    </div>
</div>
@endsection