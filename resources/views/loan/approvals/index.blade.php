@extends('layouts.main')

@section('title', 'Loan Approvals')

@section('content')
<x-page-header title="Loan Approvals" subtitle="Review and approve loan applications" icon="ik ik-check-circle"
    :breadcrumbs="['Home' => url('dashboard'), 'Loans' => route('loans.index'), 'Approvals' => null]" />

<!-- Statistics -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4 mb-6">
    <x-stat-card value="{{ number_format($totalPending) }}" 
                 label="Pending Approval" 
                 icon="ik ik-clock" 
                 color="amber" />
    
    <x-stat-card value="{{ number_format($totalApproved) }}" 
                 label="Approved" 
                 icon="ik ik-check-circle" 
                 color="green" />
    
    <x-stat-card value="{{ number_format($totalRejected) }}" 
                 label="Rejected" 
                 icon="ik ik-x-circle" 
                 color="danger" />
    
    <x-stat-card value="{{ number_format($totalAmount, 0) }}" 
                 label="Pending Amount" 
                 icon="ik ik-dollar-sign" 
                 color="primary" 
                 subtitle="TZS" />
</div>

<!-- Filter Section -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6" x-data="filterApprovals()">
    <div class="flex items-center justify-between mb-3">
        <h4 class="text-sm font-semibold text-gray-700">
            <i class="ik ik-filter mr-2"></i> Filter Loans
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
            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
            <select x-model="filters.status" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>
        
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Branch</label>
            <select x-model="filters.branch_id" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                <option value="">All Branches</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
        
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Min Amount (TZS)</label>
            <input type="number" x-model="filters.min_amount" placeholder="0" 
                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
        </div>
        
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Max Amount (TZS)</label>
            <input type="number" x-model="filters.max_amount" placeholder="No limit" 
                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
        </div>
    </div>
</div>

<!-- Loans Table -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 font-medium">#</th>
                    <th class="px-5 py-3 font-medium">Supplier</th>
                    <th class="px-5 py-3 font-medium">Branch</th>
                    <th class="px-5 py-3 font-medium">Amount</th>
                    <th class="px-5 py-3 font-medium">Interest</th>
                    <th class="px-5 py-3 font-medium">Duration</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                    <th class="px-5 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($loans as $loan)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 text-gray-700">#{{ $loan->id }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $loan->supplier->name ?? 'N/A' }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $loan->branch->name ?? 'N/A' }}</td>
                    <td class="px-5 py-3 font-semibold text-gray-800">{{ number_format($loan->amount, 0) }} TZS</td>
                    <td class="px-5 py-3 text-gray-700">{{ $loan->interest_rate }}%</td>
                    <td class="px-5 py-3 text-gray-700">{{ $loan->duration_months }} months</td>
                    <td class="px-5 py-3">
                        @if($loan->status == 'pending')
                            <span class="badge badge-amber">Pending</span>
                        @elseif($loan->status == 'approved')
                            <span class="badge badge-success">Approved</span>
                        @elseif($loan->status == 'rejected')
                            <span class="badge badge-danger">Rejected</span>
                        @elseif($loan->status == 'hold')
                            <span class="badge badge-info">On Hold</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-500">{{ $loan->created_at->format('d/m/Y') }}</td>
                    <td class="px-5 py-3">
                        <a href="{{ route('loans.approvals.review', $loan->id) }}" 
                           class="text-blue-500 hover:text-blue-600 mr-2"
                           title="Review Application">
                            <i class="ik ik-eye"></i>
                        </a>
                        @if($loan->status == 'pending')
                            <a href="{{ route('loans.approvals.review', $loan->id) }}" 
                               class="text-primary-500 hover:text-primary-600"
                               title="Review & Approve">
                                <i class="ik ik-check-circle"></i>
                            </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-5 py-8 text-center text-gray-400">
                        <i class="ik ik-check-circle text-3xl block mb-2"></i>
                        No loan applications pending for approval.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $loans->links() }}
    </div>
</div>

<script>
function filterApprovals() {
    return {
        filters: {
            status: '',
            branch_id: '',
            min_amount: '',
            max_amount: ''
        },
        
        resetFilters() {
            this.filters = {
                status: '',
                branch_id: '',
                min_amount: '',
                max_amount: ''
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