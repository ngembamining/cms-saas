@extends('layouts.main')
@section('title', 'Branch Balance')
@section('content')

<div class="max-w-7xl mx-auto">
    <x-page-header title="{{ $branch->name }} - {{ __('Balance') }}" 
                   subtitle="{{ __('View branch balances and send to directors') }}" 
                   icon="ik ik-dollar-sign"
                   :breadcrumbs="['Home' => route('dashboard'), 'My Branch' => route('branch.dashboard'), 'Balance' => null]" />

    <!-- Balance Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Cash Balance</p>
                    <p class="text-3xl font-bold text-green-600">{{ number_format($cashBalance->amount ?? 0, 0) }}</p>
                </div>
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="ik ik-dollar-sign text-green-500 text-2xl"></i>
                </div>
            </div>
            <div class="mt-2 text-xs text-gray-400">
                Minimum balance: {{ number_format($cashBalance->minimum_balance ?? 0, 0) }}
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Gold Balance</p>
                    <p class="text-3xl font-bold text-amber-600">{{ number_format($goldBalance->amount ?? 0, 2) }} g</p>
                </div>
                <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-crown text-amber-500 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Send to Director</p>
                    <button id="sendBalanceBtn" class="mt-2 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition text-sm">
                        <i class="ik ik-send mr-2"></i> Send Balance
                    </button>
                </div>
                <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center">
                    <i class="ik ik-credit-card text-primary-500 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Send Balance Modal -->
    <div id="sendBalanceModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 hidden">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">{{ __('Send Balance to Director') }}</h3>
                <button onclick="closeModal('sendBalanceModal')" class="text-gray-400 hover:text-gray-600">
                    <i class="ik ik-x"></i>
                </button>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                <p class="text-sm text-blue-700">
                    <i class="ik ik-info mr-2"></i>
                    Available Cash Balance: <strong>{{ number_format($cashBalance->amount ?? 0, 0) }}</strong>
                </p>
            </div>

            <form id="sendBalanceForm">
                @csrf

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Amount') }}</label>
                        <input type="number" name="amount" id="amount" required
                               min="1000" max="{{ $cashBalance->amount ?? 0 }}"
                               placeholder="Enter amount"
                               class="w-full rounded-lg border border-gray-200 px-4 py-2 focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        <p class="text-xs text-gray-400 mt-1">Minimum: 1,000 | Maximum: {{ number_format($cashBalance->amount ?? 0, 0) }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Select Director') }}</label>
                        <select name="director_id" id="director_id" required
                                class="w-full rounded-lg border border-gray-200 px-4 py-2 focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                            <option value="">{{ __('Select Director') }}</option>
                            @foreach($directors ?? [] as $director)
                                <option value="{{ $director->id }}">
                                    {{ $director->full_name ?? $director->username }} ({{ $director->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Bank Name') }}</label>
                        <input type="text" name="bank_name" id="bank_name" required
                               placeholder="e.g., CRDB Bank, NMB Bank"
                               class="w-full rounded-lg border border-gray-200 px-4 py-2 focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Account Number') }}</label>
                        <input type="text" name="account_number" id="account_number" required
                               placeholder="Enter account number"
                               class="w-full rounded-lg border border-gray-200 px-4 py-2 focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Account Name') }}</label>
                        <input type="text" name="account_name" id="account_name" required
                               placeholder="Enter account holder name"
                               class="w-full rounded-lg border border-gray-200 px-4 py-2 focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Remarks (Optional)') }}</label>
                        <textarea name="remarks" id="remarks" rows="2"
                                  placeholder="Any additional remarks"
                                  class="w-full rounded-lg border border-gray-200 px-4 py-2 focus:border-primary-400 focus:ring-2 focus:ring-primary-100"></textarea>
                    </div>
                </div>

                <div id="formError" class="mt-3 hidden text-red-600 text-sm"></div>
                <div id="formSuccess" class="mt-3 hidden text-green-600 text-sm"></div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="closeModal('sendBalanceModal')" 
                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" id="submitBtn"
                            class="px-4 py-2 text-sm bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition disabled:opacity-50">
                        <i class="ik ik-send mr-1"></i> {{ __('Send Balance') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Balance History -->
    <div class="mt-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h5 class="font-semibold text-gray-700 mb-4">{{ __('Balance History (Last 30 Days)') }}</h5>
            <div class="space-y-2">
                @foreach($history ?? [] as $day)
                    <div class="flex items-center justify-between text-sm border-b border-gray-50 py-1">
                        <span class="text-gray-600">{{ \Carbon\Carbon::parse($day['date'])->format('d M Y') }}</span>
                        <span class="text-green-600">Cash: {{ number_format($day['cash'], 0) }}</span>
                        <span class="text-amber-600">Gold: {{ number_format($day['gold'], 2) }}g</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Recent Transfers -->
    @if(isset($recentTransfers) && $recentTransfers->count() > 0)
    <div class="mt-6">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h5 class="font-semibold text-gray-700">{{ __('Recent Balance Transfers') }}</h5>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-6 py-3 font-medium">{{ __('Date') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('Amount') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Bank') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($recentTransfers as $transfer)
                            @php
                                $details = json_decode($transfer->comments, true);
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-gray-500">{{ $transfer->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-3 text-right font-medium text-red-600">{{ number_format($transfer->amount, 0) }}</td>
                                <td class="px-6 py-3 text-gray-700">{{ $details['bank_name'] ?? 'N/A' }}</td>
                                <td class="px-6 py-3">
                                    <x-badge color="success">{{ __('Completed') }}</x-badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
// Modal functions
function openModal() {
    document.getElementById('sendBalanceModal').classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
    document.getElementById('formError').classList.add('hidden');
    document.getElementById('formSuccess').classList.add('hidden');
}

// Send Balance Button
document.getElementById('sendBalanceBtn').addEventListener('click', openModal);

// Close modal on outside click
document.getElementById('sendBalanceModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal('sendBalanceModal');
    }
});

// Form Submission
document.getElementById('sendBalanceForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const submitBtn = document.getElementById('submitBtn');
    const errorDiv = document.getElementById('formError');
    const successDiv = document.getElementById('formSuccess');

    // Reset messages
    errorDiv.classList.add('hidden');
    successDiv.classList.add('hidden');

    // Validate amount
    const amount = parseFloat(document.getElementById('amount').value);
    const maxAmount = {{ $cashBalance->amount ?? 0 }};
    
    if (isNaN(amount) || amount < 1000) {
        errorDiv.textContent = 'Please enter a valid amount (minimum 1,000)';
        errorDiv.classList.remove('hidden');
        return;
    }

    if (amount > maxAmount) {
        errorDiv.textContent = 'Amount exceeds available balance: ' + Number(maxAmount).toLocaleString();
        errorDiv.classList.remove('hidden');
        return;
    }

    // Show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="ik ik-loader animate-spin mr-2"></i> Sending...';

    const formData = new FormData(this);

    fetch('{{ route("branch.send.balance") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            successDiv.textContent = '✅ ' + data.message;
            successDiv.classList.remove('hidden');
            
            // Reload after 2 seconds
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            errorDiv.textContent = '❌ ' + data.message;
            errorDiv.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="ik ik-send mr-1"></i> Send Balance';
        }
    })
    .catch(error => {
        errorDiv.textContent = '❌ An error occurred. Please try again.';
        errorDiv.classList.remove('hidden');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="ik ik-send mr-1"></i> Send Balance';
    });
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal('sendBalanceModal');
    }
});
</script>

@endsection