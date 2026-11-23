@extends('layouts.main')

@section('title', 'Permission Groups')

@section('content')
<x-page-header title="Permission Groups" subtitle="View permissions by group" icon="ik ik-grid"
    :breadcrumbs="['Home' => url('dashboard'), 'Management' => route('management.users.index'), 'Permissions' => route('management.permissions.index'), 'Groups' => null]" />

<div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
    @foreach($permissionGroups as $groupName => $permissions)
    <x-card>
        <x-slot:header>{{ $groupName }}</x-slot:header>
        <div class="space-y-2">
            @foreach($permissions as $key => $description)
            <div class="flex items-center justify-between py-1 border-b border-gray-50 last:border-0">
                <span class="text-sm text-gray-700">{{ $description }}</span>
                <span class="text-xs text-gray-400 font-mono">{{ $key }}</span>
            </div>
            @endforeach
            <div class="mt-2 pt-2 border-t border-gray-200 text-xs text-gray-500">
                Total: {{ count($permissions) }} permissions
            </div>
        </div>
    </x-card>
    @endforeach
</div>
@endsection