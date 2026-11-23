@extends('layouts.main')

@section('title', 'Loan Payments')

@section('content')
<x-page-header title="Loan Payments" subtitle="Manage loan payments" icon="ik ik-credit-card"
    :breadcrumbs="['Home' => url('dashboard'), 'Loans' => route('loans.index'), 'Payments' => null]">
    <a href="{{ route('loans.payments.create') }}" class="btn btn-primary">
        <i class="ik ik-plus"></i> New Payment
    </a>
</x-page-header>

<!-- Statistics -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4 mb-6">
    <x-stat-card value="{{ number_format($totalPayments, 0) }}" 
                 label="Total Payments" 
                 icon="ik ik-credit-card" 
                 color="primary" 
                 subtitle="TZS" />
    
    <x-stat-card value="{{ number_format($verifiedPayments, 0) }}" 
                 label="Verified" 
                 icon="ik ik-check-circle" 
                 color="green" 
                 subtitle="TZS" />
    
    <x-stat-card value="{{ number_format($totalPayments - $verifiedPayments, 0) }}" 
                 label="Pending" 
                 icon="ik ik-clock" 
                 color="amber" 
                 subtitle="TZS" />
    
    <x-stat-card value="{{ $pendingPayments }}" 
                 label="Pending Count" 
                 icon="ik ik-list" 
                 color="amber" />
</div>

<!-- Filter Section -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6" x-data="filterPayments()">
    <div class="flex items-center justify-between mb-3">
        <h4 class="text-sm font-semibold text-gray-700">
            <i class="ik ik-filter mr-2"></i> Filter Payments
        </h4>
        <div class="flex gap-2">
            <button @click="resetFilters()" class="text-sm text-gray-500 hover:text-gray-700">
                <i class="ik ik-rotate-ccw"></i> Reset
            </button>
            <button @click="applyFilters()" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                <i class="ik ik-search"></i> Apply Filters
            </button>
        </div>
    </div>
    
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Loan ID</label>
            <input type="number" x-model="filters.loan_id" placeholder="Loan ID" 
                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
        </div>
        
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
            <select x-model="filters.status" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="verified">Verified</option>
            </select>
        </div>
        
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">From Date</label>
            <input type="date" x-model="filters.from_date" 
                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
        </div>
        
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">To Date</label>
            <input type="date" x-model="filters.to_date" 
                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
        </div>
    </div>
</div>

<!-- Payments Table -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 font-medium">#</th>
                    <th class="px-5 py-3 font-medium">Loan ID</th>
                    <th class="px-5 py-3 font-medium">Supplier</th>
                    <th class="px-5 py-3 font-medium">Amount</th>
                    <th class="px-5 py-3 font-medium">Payment Method</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                    <th class="px-5 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($payments as $payment)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 text-gray-700">#{{ $payment->id }}</td>
                    <td class="px-5 py-3 text-gray-700">#{{ $payment->loan_id }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $payment->loan->supplier->name ?? 'N/A' }}</td>
                    <td class="px-5 py-3 font-semibold text-primary-600">{{ number_format($payment->amount, 0) }} TZS</td>
                    <td class="px-5 py-3 text-gray-700">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                    <td class="px-5 py-3">
                        @if($payment->status == 'pending')
                            <span class="badge badge-amber">Pending</span>
                        @elseif($payment->status == 'verified')
                            <span class="badge badge-success">Verified</span>
                        @else
                            <span class="badge badge-gray">{{ $payment->status }}</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-500">{{ $payment->payment_date->format('d/m/Y') }}</td>
                    <td class="px-5 py-3">
                        <a href="{{ route('loans.payments.show', $payment->id) }}" 
                           class="text-blue-500 hover:text-blue-600 mr-2">
                            <i class="ik ik-eye"></i>
                        </a>
                        @if($payment->status == 'pending')
                            <a href="{{ route('loans.payments.verify', $payment->id) }}" 
                               class="text-green-500 hover:text-green-600 mr-2"
                               onclick="return confirm('Verify this payment?')">
                                <i class="ik ik-check-circle"></i>
                            </a>
                            <form action="{{ route('loans.payments.destroy', $payment->id) }}" 
                                  method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-600" 
                                        onclick="return confirm('Delete this payment?')">
                                    <i class="ik ik-trash-2"></i>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-5 py-8 text-center text-gray-400">
                        <i class="ik ik-credit-card text-3xl block mb-2"></i>
                        No loan payments recorded yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $payments->links() }}
    </div>
</div>

<script>
function filterPayments() {
    return {
        filters: {
            loan_id: '',
            status: '',
            from_date: '',
            to_date: ''
        },
        
        resetFilters() {
            this.filters = {
                loan_id: '',
                status: '',
                from_date: '',
                to_date: ''
            };
            this.applyFilters();
        },
        
        applyFilters() {
            const params = new URLSearchParams();
            Object.keys(this.filters).forEach(key => {
                if (this.filters[key]) {
                    params.append(key, this.filters[key]);
                }
            });
            
            const url = window.location.pathname + '?' + params.toString();
            window.location.href = url;
        }
    }
}
</script>
@endsection