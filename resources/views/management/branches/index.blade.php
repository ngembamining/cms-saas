@extends('layouts.main')

@section('title', 'Branches')

@section('content')
<x-page-header title="Branches" subtitle="Manage all branches" icon="fas fa-store"
    :breadcrumbs="['Home' => url('dashboard'), 'Management' => route('management.users.index'), 'Branches' => null]">
    <a href="{{ route('management.branches.create') }}" class="btn btn-primary">
        <i class="ik ik-plus"></i> New Branch
    </a>
</x-page-header>

<!-- Statistics -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3 mb-6">
    <x-stat-card value="{{ $branches->total() }}" 
                 label="Total Branches" 
                 icon="fas fa-store" 
                 color="primary" />
</div>

<!-- Filter Section -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6" x-data="filterBranches()">
    <div class="flex items-center justify-between mb-3">
        <h4 class="text-sm font-semibold text-gray-700">
            <i class="ik ik-filter mr-2"></i> Filter Branches
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
    
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
            <input type="text" x-model="filters.search" placeholder="Name or location..." 
                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
        </div>
    </div>
</div>

<!-- Branches Table -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 font-medium">#</th>
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Location</th>
                    <th class="px-5 py-3 font-medium">Gold (g)</th>
                    <th class="px-5 py-3 font-medium">Staff</th>
                    <th class="px-5 py-3 font-medium">Created</th>
                    <th class="px-5 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($branches as $branch)
                @php
                    $goldBalance = $branch->balances->where('balance_type', 'gold')->first();
                    $staffCount = $branch->users->count();
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 text-gray-700">#{{ $branch->id }}</td>
                    <td class="px-5 py-3 font-medium text-gray-800">{{ $branch->name }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $branch->location ?? 'N/A' }}</td>
                    <td class="px-5 py-3 text-amber-600 font-medium">{{ number_format($goldBalance->amount ?? 0, 2) }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $staffCount }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $branch->created_at ? $branch->created_at->format('d/m/Y') : 'N/A' }}</td>
                    <td class="px-5 py-3">
                        <a href="{{ route('management.branches.show', $branch->id) }}" 
                           class="text-blue-500 hover:text-blue-600 mr-2">
                            <i class="ik ik-eye"></i>
                        </a>
                        <a href="{{ route('management.branches.edit', $branch->id) }}" 
                           class="text-green-500 hover:text-green-600 mr-2">
                            <i class="ik ik-edit"></i>
                        </a>
                        <form action="{{ route('management.branches.destroy', $branch->id) }}" 
                              method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-600" 
                                    onclick="return confirm('Delete this branch?')">
                                <i class="ik ik-trash-2"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-8 text-center text-gray-400">
                        <i class="fas fa-store text-3xl block mb-2"></i>
                        No branches found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $branches->links() }}
    </div>
</div>

<script>
function filterBranches() {
    return {
        filters: {
            search: ''
        },
        
        resetFilters() {
            this.filters = {
                search: ''
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