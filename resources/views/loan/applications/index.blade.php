@extends('layouts.main')

@section('title', 'Loan Applications')

@section('content')
<x-page-header title="Loan Applications" subtitle="Manage all loan applications" icon="ik ik-file-text"
    :breadcrumbs="['Home' => url('dashboard'), 'Loans' => route('loans.index'), 'Applications' => null]">
    <a href="{{ route('loans.applications.create') }}" class="btn btn-primary">
        <i class="ik ik-plus"></i> New Application
    </a>
</x-page-header>

<!-- Statistics -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4 mb-6">
    <x-stat-card value="{{ number_format($amountLoaned, 0) }}" 
                 label="Amount Loaned" 
                 icon="ik ik-dollar-sign" 
                 color="primary" 
                 subtitle="TZS" />

    <x-stat-card value="{{ number_format($amountPaid, 0) }}" 
                 label="Amount Paid So Far" 
                 icon="ik ik-check-circle" 
                 color="green" 
                 subtitle="TZS" />

    <x-stat-card value="{{ number_format($loanBalance, 0) }}" 
                 label="Loan Balance (Remaining)" 
                 icon="ik ik-clock" 
                 color="amber" 
                 subtitle="TZS" />

    <x-stat-card value="{{ number_format($interestCollected, 0) }}" 
                 label="Interest Collected" 
                 icon="ik ik-percent" 
                 color="accent" 
                 subtitle="TZS" />
</div>

<!-- Filter Section -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6" x-data="filterLoans()" x-init="init()">
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
    
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <!-- Branch Filter -->
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Branch</label>
            <select x-model="filters.branch_id" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                <option value="">All Branches</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
        
        <!-- Supplier Filter (searchable) -->
        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
            <label class="block text-xs font-medium text-gray-600 mb-1">Supplier</label>
            <div class="relative">
                <input type="text"
                       x-model="supplierSearch"
                       @focus="open = true"
                       @input="open = true; if (!$event.target.value) filters.supplier_id = ''"
                       placeholder="Type supplier name..."
                       autocomplete="off"
                       class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100 pr-7">
                <button x-show="supplierSearch" @click="clearSupplier()" type="button"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                    <i class="ik ik-x text-xs"></i>
                </button>
            </div>

            <!-- Dropdown results -->
            <div x-show="open && filteredSuppliers().length > 0"
                 x-transition
                 class="absolute z-20 mt-1 w-full max-h-56 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg">
                <template x-for="supplier in filteredSuppliers()" :key="supplier.id">
                    <button type="button"
                            @click="selectSupplier(supplier); open = false"
                            class="block w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-primary-50"
                            :class="{ 'bg-primary-50 font-medium': filters.supplier_id == supplier.id }">
                        <span x-text="supplier.name"></span>
                    </button>
                </template>
            </div>

            <!-- No matches -->
            <div x-show="open && supplierSearch && filteredSuppliers().length === 0"
                 x-transition
                 class="absolute z-20 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg px-3 py-2 text-sm text-gray-400">
                No suppliers match "<span x-text="supplierSearch"></span>"
            </div>
        </div>
        
        <!-- Status Filter -->
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
            <select x-model="filters.status" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="completed">Completed</option>
            </select>
        </div>
        
        <!-- Amount Range -->
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
    
    <!-- Date Range -->
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 mt-3">
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
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Min Interest (%)</label>
            <input type="number" x-model="filters.min_interest" placeholder="0" step="0.1"
                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Max Interest (%)</label>
            <input type="number" x-model="filters.max_interest" placeholder="No limit" step="0.1"
                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
        </div>
    </div>
    
    <!-- Active Filters Display -->
    <div class="mt-3 flex flex-wrap gap-2" x-show="hasActiveFilters()">
        <span class="text-xs text-gray-500">Active Filters:</span>
        <template x-for="(filter, key) in getActiveFilters()" :key="key">
            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-700">
                <span x-text="filter.label"></span>
                <button @click="removeFilter(key)" class="text-gray-400 hover:text-gray-600">
                    <i class="ik ik-x text-xs"></i>
                </button>
            </span>
        </template>
    </div>
</div>

<!-- Loans Table -->
<div class="bg-white rounded-xl shadow-sm p-6" id="loansTable">
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
            <tbody class="divide-y divide-gray-50" id="loansTableBody">
                @forelse($loans as $loan)
                @php
                    $interestAmount = $loan->amount * $loan->interest_rate / 100;
                @endphp
                <tr class="hover:bg-gray-50 loan-row" data-loan-id="{{ $loan->id }}">
                    <td class="px-5 py-3 text-gray-700">#{{ $loan->id }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $loan->supplier->name ?? 'N/A' }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $loan->branch->name ?? 'N/A' }}</td>
                    <td class="px-5 py-3 font-semibold text-gray-800">{{ number_format($loan->amount, 0) }} TZS</td>
                    <td class="px-5 py-3 text-gray-700">
                        {{ number_format($interestAmount, 0) }} TZS
                        <div class="text-xs text-gray-400">{{ rtrim(rtrim(number_format($loan->interest_rate, 2), '0'), '.') }}%</div>
                    </td>
                    <td class="px-5 py-3 text-gray-700">{{ $loan->duration_months }} months</td>
                    <td class="px-5 py-3">
                        @if($loan->status == 'pending')
                            <span class="badge badge-amber">Pending</span>
                        @elseif($loan->status == 'approved')
                            <span class="badge badge-success">Approved</span>
                        @elseif($loan->status == 'rejected')
                            <span class="badge badge-danger">Rejected</span>
                        @elseif($loan->status == 'completed')
                            <span class="badge badge-primary">Completed</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-500">{{ $loan->created_at->format('d/m/Y') }}</td>
                    <td class="px-5 py-3">
                        <a href="{{ route('loans.applications.show', $loan->id) }}" 
                           class="text-blue-500 hover:text-blue-600 mr-2">
                            <i class="ik ik-eye"></i>
                        </a>
                        @if($loan->status == 'pending')
                            <a href="{{ route('loans.applications.edit', $loan->id) }}" 
                               class="text-green-500 hover:text-green-600 mr-2">
                                <i class="ik ik-edit"></i>
                            </a>
                            <form action="{{ route('loans.applications.destroy', $loan->id) }}" 
                                  method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-600" 
                                        onclick="return confirm('Delete this application?')">
                                    <i class="ik ik-trash-2"></i>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-5 py-8 text-center text-gray-400">
                        <i class="ik ik-file-text text-3xl block mb-2"></i>
                        No loan applications found.
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

@php
    $suppliersForSearch = $suppliers->map(function ($s) {
        return ['id' => $s->id, 'name' => $s->name];
    })->values();
    $branchNamesForSearch = $branches->pluck('name', 'id');
@endphp
<script>
function filterLoans() {
    return {
        // Full supplier list passed from the server, used for client-side search
        suppliers: @json($suppliersForSearch),
        supplierSearch: '',

        filters: {
            branch_id: '',
            supplier_id: '',
            status: '',
            min_amount: '',
            max_amount: '',
            from_date: '',
            to_date: '',
            min_interest: '',
            max_interest: ''
        },
        activeFilters: {},

        init() {
            // Pre-fill filters (and the supplier search box) from the current URL,
            // so filters look "active" after applyFilters() redirects the page.
            const params = new URLSearchParams(window.location.search);
            Object.keys(this.filters).forEach(key => {
                if (params.get(key)) {
                    this.filters[key] = params.get(key);
                }
            });

            if (this.filters.supplier_id) {
                const match = this.suppliers.find(s => s.id == this.filters.supplier_id);
                if (match) this.supplierSearch = match.name;
            }
        },

        // Returns suppliers matching the current search text (case-insensitive, capped to 20)
        filteredSuppliers() {
            const q = this.supplierSearch.trim().toLowerCase();
            if (!q) return this.suppliers.slice(0, 20);
            return this.suppliers
                .filter(s => s.name.toLowerCase().includes(q))
                .slice(0, 20);
        },

        selectSupplier(supplier) {
            this.filters.supplier_id = supplier.id;
            this.supplierSearch = supplier.name;
        },

        clearSupplier() {
            this.filters.supplier_id = '';
            this.supplierSearch = '';
        },
        
        hasActiveFilters() {
            return Object.keys(this.getActiveFilters()).length > 0;
        },
        
        getActiveFilters() {
            const filters = {};
            
            // Get branch name
            if (this.filters.branch_id) {
                const branchName = @json($branchNamesForSearch);
                filters.branch_id = {
                    label: 'Branch: ' + (branchName[this.filters.branch_id] || this.filters.branch_id),
                    value: this.filters.branch_id
                };
            }
            
            // Supplier name comes from the search box / selected supplier
            if (this.filters.supplier_id) {
                filters.supplier_id = {
                    label: 'Supplier: ' + (this.supplierSearch || this.filters.supplier_id),
                    value: this.filters.supplier_id
                };
            }
            
            if (this.filters.status) {
                filters.status = {
                    label: 'Status: ' + this.filters.status.charAt(0).toUpperCase() + this.filters.status.slice(1),
                    value: this.filters.status
                };
            }
            
            if (this.filters.min_amount) {
                filters.min_amount = {
                    label: 'Min: ' + parseInt(this.filters.min_amount).toLocaleString() + ' TZS',
                    value: this.filters.min_amount
                };
            }
            
            if (this.filters.max_amount) {
                filters.max_amount = {
                    label: 'Max: ' + parseInt(this.filters.max_amount).toLocaleString() + ' TZS',
                    value: this.filters.max_amount
                };
            }
            
            if (this.filters.from_date) {
                filters.from_date = {
                    label: 'From: ' + this.filters.from_date,
                    value: this.filters.from_date
                };
            }
            
            if (this.filters.to_date) {
                filters.to_date = {
                    label: 'To: ' + this.filters.to_date,
                    value: this.filters.to_date
                };
            }
            
            if (this.filters.min_interest) {
                filters.min_interest = {
                    label: 'Min Interest: ' + this.filters.min_interest + '%',
                    value: this.filters.min_interest
                };
            }
            
            if (this.filters.max_interest) {
                filters.max_interest = {
                    label: 'Max Interest: ' + this.filters.max_interest + '%',
                    value: this.filters.max_interest
                };
            }
            
            return filters;
        },
        
        removeFilter(key) {
            this.filters[key] = '';
            if (key === 'supplier_id') {
                this.supplierSearch = '';
            }
            this.applyFilters();
        },
        
        resetFilters() {
            this.filters = {
                branch_id: '',
                supplier_id: '',
                status: '',
                min_amount: '',
                max_amount: '',
                from_date: '',
                to_date: '',
                min_interest: '',
                max_interest: ''
            };
            this.supplierSearch = '';
            this.applyFilters();
        },
        
        applyFilters() {
            // Build query string
            const params = new URLSearchParams();
            Object.keys(this.filters).forEach(key => {
                if (this.filters[key]) {
                    params.append(key, this.filters[key]);
                }
            });
            
            // Redirect to filtered URL
            const url = window.location.pathname + '?' + params.toString();
            window.location.href = url;
        }
    }
}
</script>
@endsection