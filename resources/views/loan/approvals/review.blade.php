@extends('layouts.main')

@section('title', 'Review Loan Application')

@section('content')
<x-page-header title="Review Loan Application" subtitle="Review and approve/reject loan application" icon="ik ik-check-circle"
    :breadcrumbs="['Home' => url('dashboard'), 'Loans' => route('loans.index'), 'Approvals' => route('loans.approvals.index'), 'Review' => null]" />

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Loan Details -->
    <div class="lg:col-span-2">
        <x-card>
            <x-slot:header>{{ __('Application Details') }}</x-slot:header>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Application ID</p>
                    <p class="font-semibold">#{{ $loan->id }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Status</p>
                    <p><span class="badge badge-amber">Pending Review</span></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Application Date</p>
                    <p class="font-medium">{{ $loan->application_date ? $loan->application_date->format('d/m/Y') : 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Submitted By</p>
                    <p class="font-medium">{{ $loan->creator->full_name ?? $loan->creator->username ?? 'N/A' }}</p>
                </div>
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
                    <p class="font-bold text-lg text-green-600">{{ number_format($totalPayable, 0) }} TZS</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Monthly Payment</p>
                    <p class="font-medium">{{ number_format($monthlyPayment, 0) }} TZS</p>
                </div>
                @if($loan->description)
                <div class="col-span-2">
                    <p class="text-sm text-gray-500">Description</p>
                    <p class="font-medium">{{ $loan->description }}</p>
                </div>
                @endif
            </div>
        </x-card>
    </div>
    
    <!-- Review Actions -->
    <div class="lg:col-span-1">
        <x-card>
            <x-slot:header>{{ __('Review Actions') }}</x-slot:header>
            
            <div class="space-y-4">
                <div class="bg-blue-50 rounded-lg p-4 text-sm text-blue-700">
                    <p><strong>Available Cash:</strong> {{ number_format($availableCash, 0) }} TZS</p>
                    <p class="mt-1 text-xs">Branch: {{ $loan->branch->name ?? 'N/A' }}</p>
                </div>
                
                <div class="bg-yellow-50 rounded-lg p-4 text-sm text-yellow-700">
                    <p><strong>Requested Amount:</strong> {{ number_format($loan->amount, 0) }} TZS</p>
                    <p class="mt-1 text-xs">
                        @if($availableCash >= $loan->amount)
                            ✅ Sufficient funds available
                        @else
                            ❌ Insufficient funds (Short by {{ number_format($loan->amount - $availableCash, 0) }} TZS)
                        @endif
                    </p>
                </div>
                
                <!-- Approve Form -->
                <form method="POST" action="{{ route('loans.approvals.approve', $loan->id) }}" id="approveForm">
                    @csrf
                    <div>
                        <label for="approve_comments" class="block text-sm font-medium text-gray-700 mb-1">Comments</label>
                        <textarea name="comments" id="approve_comments" rows="2" 
                                  class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                                  placeholder="Add approval notes"></textarea>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <button type="button" onclick="document.getElementById('approveModal').style.display='flex'" 
                                class="flex-1 rounded-lg bg-green-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-green-600">
                            <i class="ik ik-check-circle"></i> Approve
                        </button>
                    </div>
                </form>
                
                <!-- Reject Form -->
                <form method="POST" action="{{ route('loans.approvals.reject', $loan->id) }}" id="rejectForm">
                    @csrf
                    <div>
                        <label for="reject_comments" class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason *</label>
                        <textarea name="comments" id="reject_comments" rows="2" 
                                  class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                                  placeholder="Why is this application being rejected?"></textarea>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <button type="button" onclick="document.getElementById('rejectModal').style.display='flex'" 
                                class="flex-1 rounded-lg bg-red-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-600">
                            <i class="ik ik-x-circle"></i> Reject
                        </button>
                    </div>
                </form>
                
                <div class="border-t border-gray-200 pt-3">
                    <a href="{{ route('loans.approvals.index') }}" class="block text-center text-sm text-gray-500 hover:text-gray-700">
                        <i class="ik ik-arrow-left"></i> Back to Approvals
                    </a>
                </div>
            </div>
        </x-card>
    </div>
</div>

<!-- ============================================ -->
<!-- APPROVE CONFIRMATION MODAL -->
<!-- ============================================ -->
<div id="approveModal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; padding: 1rem;">
    <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl" style="max-height: 90vh; overflow-y: auto;">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <h5 class="font-semibold text-gray-700 flex items-center gap-2">
                <i class="ik ik-check-circle text-green-500 text-xl"></i>
                {{ __('Approve Loan') }}
            </h5>
            <button type="button" onclick="document.getElementById('approveModal').style.display='none'" class="text-gray-400 hover:text-gray-600">
                <i class="ik ik-x"></i>
            </button>
        </div>
        <div class="px-5 py-4 text-sm text-gray-600">
            <p class="font-medium text-gray-800">Are you sure you want to approve this loan?</p>
            <div class="mt-3 bg-gray-50 rounded-lg p-3">
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <span class="text-gray-500">Supplier:</span>
                    <span class="font-medium">{{ $loan->supplier->name ?? 'N/A' }}</span>
                    <span class="text-gray-500">Amount:</span>
                    <span class="font-bold text-green-600">{{ number_format($loan->amount, 0) }} TZS</span>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-400">
                <i class="ik ik-info mr-1"></i>
                This will disburse the loan and deduct from branch cash.
            </p>
            <div class="mt-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Comments (Optional)</label>
                <textarea id="modal_approve_comments" rows="2" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100" placeholder="Add approval notes..."></textarea>
            </div>
        </div>
        <div class="flex justify-end gap-2 border-t border-gray-100 px-5 py-4">
            <button type="button" onclick="document.getElementById('approveModal').style.display='none'" class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-300">
                {{ __('Cancel') }}
            </button>
            <button type="button" onclick="submitApprove()" class="rounded-lg bg-green-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-green-600">
                <i class="ik ik-check-circle"></i> {{ __('Approve') }}
            </button>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- REJECT CONFIRMATION MODAL -->
<!-- ============================================ -->
<div id="rejectModal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; padding: 1rem;">
    <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl" style="max-height: 90vh; overflow-y: auto;">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <h5 class="font-semibold text-gray-700 flex items-center gap-2">
                <i class="ik ik-x-circle text-red-500 text-xl"></i>
                {{ __('Reject Loan') }}
            </h5>
            <button type="button" onclick="document.getElementById('rejectModal').style.display='none'" class="text-gray-400 hover:text-gray-600">
                <i class="ik ik-x"></i>
            </button>
        </div>
        <div class="px-5 py-4 text-sm text-gray-600">
            <p class="font-medium text-gray-800">Are you sure you want to reject this loan?</p>
            <div class="mt-3 bg-gray-50 rounded-lg p-3">
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <span class="text-gray-500">Supplier:</span>
                    <span class="font-medium">{{ $loan->supplier->name ?? 'N/A' }}</span>
                    <span class="text-gray-500">Amount:</span>
                    <span class="font-bold text-red-600">{{ number_format($loan->amount, 0) }} TZS</span>
                </div>
            </div>
            <div class="mt-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason <span class="text-red-500">*</span></label>
                <textarea id="modal_reject_comments" rows="2" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100" placeholder="Please provide a reason for rejection..."></textarea>
            </div>
        </div>
        <div class="flex justify-end gap-2 border-t border-gray-100 px-5 py-4">
            <button type="button" onclick="document.getElementById('rejectModal').style.display='none'" class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-300">
                {{ __('Cancel') }}
            </button>
            <button type="button" onclick="submitReject()" class="rounded-lg bg-red-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-600">
                <i class="ik ik-x-circle"></i> {{ __('Reject') }}
            </button>
        </div>
    </div>
</div>

<script>
// ============================================
// APPROVE FUNCTIONS
// ============================================
function submitApprove() {
    // Get comments from modal
    const comments = document.getElementById('modal_approve_comments').value;
    
    // Set the comments in the original form
    document.getElementById('approve_comments').value = comments;
    
    // Submit the approve form
    document.getElementById('approveForm').submit();
}

// ============================================
// REJECT FUNCTIONS
// ============================================
function submitReject() {
    // Get comments from modal
    const comments = document.getElementById('modal_reject_comments').value;
    
    // Validate reason
    if (!comments.trim()) {
        alert('Please provide a rejection reason.');
        return;
    }
    
    // Set the comments in the original form
    document.getElementById('reject_comments').value = comments;
    
    // Submit the reject form
    document.getElementById('rejectForm').submit();
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const approveModal = document.getElementById('approveModal');
    const rejectModal = document.getElementById('rejectModal');
    
    if (event.target === approveModal) {
        approveModal.style.display = 'none';
    }
    if (event.target === rejectModal) {
        rejectModal.style.display = 'none';
    }
});
</script>
@endsection