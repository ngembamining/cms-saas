@extends('layouts.main')

@section('title', 'Roles')

@section('content')
<x-page-header title="Roles" subtitle="Manage user roles and permissions" icon="ik ik-shield"
    :breadcrumbs="['Home' => url('dashboard'), 'Management' => route('management.users.index'), 'Roles' => null]">
    <a href="{{ route('management.roles.create') }}" class="btn btn-primary">
        <i class="ik ik-plus"></i> New Role
    </a>
</x-page-header>

<!-- Statistics -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4 mb-6">
    <x-stat-card value="{{ count($roles) }}" 
                 label="Total Roles" 
                 icon="ik ik-shield" 
                 color="primary" />
</div>

<!-- Roles Cards -->
<div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
    @foreach($roles as $key => $role)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">
        <div class="p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold text-gray-800">{{ $role['label'] }}</h3>
                <span class="badge badge-{{ $role['color'] }}">{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
            </div>
            <p class="text-sm text-gray-600 mb-3">{{ $role['description'] }}</p>
            
            <div class="flex items-center gap-4 text-sm">
                <span class="text-gray-500">
                    <i class="ik ik-users mr-1"></i>
                    {{ $userCounts[$key] ?? 0 }} users
                </span>
                <span class="text-gray-500">
                    <i class="ik ik-lock mr-1"></i>
                    {{ count($role['permissions']) == 1 && $role['permissions'][0] == '*' ? 'All' : count($role['permissions']) }} permissions
                </span>
            </div>
            
            <div class="mt-4 pt-4 border-t border-gray-100 flex gap-2">
                <a href="{{ route('management.roles.edit', $key) }}" 
                   class="text-green-500 hover:text-green-600">
                    <i class="ik ik-edit"></i> Edit
                </a>
                <a href="{{ route('management.roles.permissions', $key) }}" 
                   class="text-blue-500 hover:text-blue-600">
                    <i class="ik ik-lock"></i> Permissions
                </a>
                @if(($userCounts[$key] ?? 0) == 0)
                <form action="{{ route('management.roles.destroy', $key) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-500 hover:text-red-600" onclick="return confirm('Delete this role?')">
                        <i class="ik ik-trash-2"></i> Delete
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection