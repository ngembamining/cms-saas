@extends('layouts.main')

@section('title', 'Customers')

@section('content')
<x-page-header title="Customers" subtitle="Manage all customers" icon="ik ik-users"
    :breadcrumbs="['Home' => url('dashboard'), 'Customers' => null]">
    <a href="{{ route('customers.create') }}" class="btn btn-primary">
        <i class="ik ik-plus"></i> New Customer
    </a>
</x-page-header>

<!-- Statistics -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3 mb-6">
    <x-stat-card value="{{ $customers->total() }}" 
                 label="Total Customers" 
                 icon="ik ik-users" 
                 color="primary" />
</div>

<!-- Filter Section -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6" x-data="filterCustomers()">
    <div class="flex items-center justify-between mb-3">
        <h4 class="text-sm font-semibold text-gray-700">
            <i class="ik ik-filter mr-2"></i> Filter Customers
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
    
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
            <input type="text" x-model="filters.search" placeholder="Name, contact or email..." 
                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
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
    </div>
</div>

<!-- Customers Table -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 font-medium">#</th>
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Contact</th>
                    <th class="px-5 py-3 font-medium">Email</th>
                    <th class="px-5 py-3 font-medium">Branch</th>
                    <th class="px-5 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($customers as $customer)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 text-gray-700">#{{ $customer->id }}</td>
                    <td class="px-5 py-3 font-medium text-gray-800">{{ $customer->name }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $customer->contact }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $customer->email ?? 'N/A' }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $customer->branch->name ?? 'N/A' }}</td>
                    <td class="px-5 py-3">
                        <a href="{{ route('customers.show', $customer->id) }}" 
                           class="text-blue-500 hover:text-blue-600 mr-2">
                            <i class="ik ik-eye"></i>
                        </a>
                        <a href="{{ route('customers.edit', $customer->id) }}" 
                           class="text-green-500 hover:text-green-600 mr-2">
                            <i class="ik ik-edit"></i>
                        </a>
                        <form action="{{ route('customers.destroy', $customer->id) }}" 
                              method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-600" 
                                    onclick="return confirm('Delete this customer?')">
                                <i class="ik ik-trash-2"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-8 text-center text-gray-400">
                        <i class="ik ik-users text-3xl block mb-2"></i>
                        No customers found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $customers->links() }}
    </div>
</div>

<script>
function filterCustomers() {
    return {
        filters: {
            search: '',
            branch_id: ''
        },
        
        resetFilters() {
            this.filters = {
                search: '',
                branch_id: ''
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