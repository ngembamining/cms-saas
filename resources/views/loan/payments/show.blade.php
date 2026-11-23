@extends('layouts.main')

@section('title', 'Payment #' . $payment->id)

@section('content')
<x-page-header title="Payment #{{ $payment->id }}" subtitle="Loan payment details" icon="ik ik-credit-card"
    :breadcrumbs="['Home' => url('dashboard'), 'Loans' => route('loans.index'), 'Payments' => route('loans.payments.index'), 'Payment #' . $payment->id => null]">
    <div class="flex gap-2">
        @if($payment->status == 'pending')
            <a href="{{ route('loans.payments.verify', $payment->id) }}"
               class="btn btn-success"
               onclick="return confirm('Verify this payment?')">
                <i class="ik ik-check-circle"></i> Verify Payment
            </a>
            <form action="{{ route('loans.payments.destroy', $payment->id) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger"
                        onclick="return confirm('Delete this payment?')">
                    <i class="ik ik-trash-2"></i> Delete
                </button>
            </form>
        @endif
        <a href="{{ route('loans.payments.index') }}" class="btn btn-outline-secondary">
            <i class="ik ik-arrow-left"></i> Back
        </a>
    </div>
</x-page-header>

@php
    $loan = $payment->loan;
    $totalPayable = $loan ? $loan->amount + ($loan->amount * $loan->interest_rate / 100) : 0;
    $totalPaid = $loan
        ? $loan->payments()->where('status', 'verified')->sum('amount')
        : 0;
    $remainingBalance = $totalPayable - $totalPaid;
    $progress = $totalPayable > 0 ? min(100, round(($totalPaid / $totalPayable) * 100)) : 0;
@endphp

<div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

    <!-- Payment Details -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-5">
            <h4 class="text-sm font-semibold text-gray-700">
                <i class="ik ik-credit-card mr-2"></i> Payment Information
            </h4>
            @if($payment->status == 'pending')
                <span class="badge badge-amber">Pending</span>
            @elseif($payment->status == 'verified')
                <span class="badge badge-success">Verified</span>
            @else
                <span class="badge badge-gray">{{ ucfirst($payment->status) }}</span>
            @endif
        </div>

        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-400">Amount Paid</dt>
                <dd class="text-lg font-semibold text-primary-600">{{ number_format($payment->amount, 0) }} TZS</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-400">Payment Date</dt>
                <dd class="text-sm text-gray-700">{{ $payment->payment_date->format('d/m/Y') }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-400">Payment Method</dt>
                <dd class="text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-400">Reference</dt>
                <dd class="text-sm text-gray-700">{{ $payment->reference ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-400">Recorded On</dt>
                <dd class="text-sm text-gray-700">{{ $payment->created_at->format('d/m/Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-400">Verified By</dt>
                <dd class="text-sm text-gray-700">
                    @if($payment->status == 'verified')
                        {{ $payment->verifier->name ?? ('User #' . $payment->verified_by) }}
                        <span class="text-gray-400">on {{ optional($payment->verified_at)->format('d/m/Y H:i') }}</span>
                    @else
                        <span class="text-gray-400">Not yet verified</span>
                    @endif
                </dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs uppercase tracking-wide text-gray-400">Comments</dt>
                <dd class="text-sm text-gray-700">{{ $payment->comments ?: '—' }}</dd>
            </div>
        </dl>
    </div>

    <!-- Loan Summary -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h4 class="text-sm font-semibold text-gray-700 mb-5">
            <i class="ik ik-file-text mr-2"></i> Loan #{{ $loan->id ?? '—' }}
        </h4>

        @if($loan)
            <dl class="space-y-4">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-400">Status</dt>
                    <dd class="mt-1">
                        @if($loan->status == 'approved')
                            <span class="badge badge-primary">Approved</span>
                        @elseif($loan->status == 'completed')
                            <span class="badge badge-success">Completed</span>
                        @elseif($loan->status == 'pending')
                            <span class="badge badge-amber">Pending</span>
                        @elseif($loan->status == 'rejected')
                            <span class="badge badge-danger">Rejected</span>
                        @else
                            <span class="badge badge-gray">{{ ucfirst($loan->status) }}</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-400">Supplier</dt>
                    <dd class="text-sm text-gray-700">{{ $loan->supplier->name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-400">Branch</dt>
                    <dd class="text-sm text-gray-700">{{ $loan->branch->name ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-400">Principal</dt>
                    <dd class="text-sm text-gray-700">{{ number_format($loan->amount, 0) }} TZS</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-400">Interest Rate</dt>
                    <dd class="text-sm text-gray-700">{{ number_format($loan->interest_rate, 2) }}%</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-400">Total Payable</dt>
                    <dd class="text-sm font-semibold text-gray-800">{{ number_format($totalPayable, 0) }} TZS</dd>
                </div>

                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-400 mb-1">Payment Progress</dt>
                    <dd>
                        <div class="w-full bg-gray-100 rounded-full h-2.5 mb-1">
                            <div class="h-2.5 rounded-full {{ $progress >= 100 ? 'bg-green-500' : 'bg-primary-500' }}"
                                 style="width: {{ $progress }}%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>Paid: {{ number_format($totalPaid, 0) }}</span>
                            <span>{{ $progress }}%</span>
                        </div>
                        @if($remainingBalance > 0)
                            <div class="text-xs text-amber-600 mt-1">
                                Remaining: {{ number_format($remainingBalance, 0) }} TZS
                            </div>
                        @else
                            <div class="text-xs text-green-600 mt-1">
                                Fully paid
                            </div>
                        @endif
                    </dd>
                </div>

                <div class="pt-2 border-t border-gray-100">
                    <a href="{{ route('loans.show', $loan->id) }}" class="text-sm text-primary-600 hover:text-primary-700">
                        <i class="ik ik-external-link mr-1"></i> View Full Loan
                    </a>
                </div>
            </dl>
        @else
            <p class="text-sm text-gray-400">Loan record not found.</p>
        @endif
    </div>
</div>
@endsection
