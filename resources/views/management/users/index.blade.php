@extends('layouts.main')

@section('title', 'Users')

@section('content')
<x-page-header title="Users" subtitle="Manage all system users" icon="ik ik-users"
    :breadcrumbs="['Home' => url('dashboard'), 'Management' => route('management.users.index'), 'Users' => null]">
    <a href="{{ route('management.users.create') }}" class="btn btn-primary">
        <i class="ik ik-plus"></i> New User
    </a>
</x-page-header>

<!-- Statistics -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4 mb-6">
    <x-stat-card value="{{ $users->total() }}" 
                 label="Total Users" 
                 icon="ik ik-users" 
                 color="primary" />
</div>

<!-- Filter Section -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6" x-data="filterUsers()">
    <div class="flex items-center justify-between mb-3">
        <h4 class="text-sm font-semibold text-gray-700">
            <i class="ik ik-filter mr-2"></i> Filter Users
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
            <input type="text" x-model="filters.search" placeholder="Username, name or email..." 
                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
        </div>
        
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Role</label>
            <select x-model="filters.role" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                <option value="">All Roles</option>
                @foreach($roles as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
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
    </div>
</div>

<!-- Users Table -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 font-medium">#</th>
                    <th class="px-5 py-3 font-medium">Username</th>
                    <th class="px-5 py-3 font-medium">Full Name</th>
                    <th class="px-5 py-3 font-medium">Email</th>
                    <th class="px-5 py-3 font-medium">Role</th>
                    <th class="px-5 py-3 font-medium">Branch</th>
                    <th class="px-5 py-3 font-medium">Phone</th>
                    <th class="px-5 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($users as $user)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 text-gray-700">#{{ $user->id }}</td>
                    <td class="px-5 py-3 font-medium text-gray-800">{{ $user->username }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $user->full_name ?? 'N/A' }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $user->email ?? 'N/A' }}</td>
                    <td class="px-5 py-3">
                        <span class="badge badge-{{ $user->role == 'director' ? 'primary' : ($user->role == 'branch_manager' ? 'green' : ($user->role == 'accountant' ? 'blue' : 'gray')) }}">
                            {{ $user->role_label }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-gray-700">{{ $user->branch->name ?? 'N/A' }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $user->phone_number ?? 'N/A' }}</td>
                    <td class="px-5 py-3">
                        <a href="{{ route('management.users.show', $user->id) }}" 
                           class="text-blue-500 hover:text-blue-600 mr-2">
                            <i class="ik ik-eye"></i>
                        </a>
                        <a href="{{ route('management.users.edit', $user->id) }}" 
                           class="text-green-500 hover:text-green-600 mr-2">
                            <i class="ik ik-edit"></i>
                        </a>
                        <form action="{{ route('management.users.destroy', $user->id) }}" 
                              method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-600" 
                                    onclick="return confirm('Delete this user?')">
                                <i class="ik ik-trash-2"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-5 py-8 text-center text-gray-400">
                        <i class="ik ik-users text-3xl block mb-2"></i>
                        No users found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $users->links() }}
    </div>
</div>

<script>
function filterUsers() {
    return {
        filters: {
            search: '',
            role: '',
            branch_id: ''
        },
        
        resetFilters() {
            this.filters = {
                search: '',
                role: '',
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