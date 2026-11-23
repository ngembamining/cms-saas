@extends('layouts.main')

@section('title', 'Role Permissions')

@section('content')
<x-page-header title="Role Permissions" subtitle="Manage permissions for role" icon="ik ik-lock"
    :breadcrumbs="['Home' => url('dashboard'), 'Management' => route('management.users.index'), 'Roles' => route('management.roles.index'), 'Permissions' => null]" />

<div class="max-w-4xl mx-auto">
    <x-card>
        <x-slot:header>{{ __('Assign Permissions') }}</x-slot:header>

        @if(session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <i class="ik ik-alert-circle mr-2"></i>
                {{ session('error') }}
            </div>
        @endif

        @if(session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                <i class="ik ik-check-circle mr-2"></i>
                {{ session('success') }}
            </div>
        @endif

        {{-- Using direct URL to avoid route parameter issues --}}
        <form method="POST" action="{{ url('management/roles/' . ($role['name'] ?? '') . '/permissions') }}">
            @csrf

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach($permissionGroups as $groupName => $permissions)
                <div class="bg-gray-50 rounded-lg p-4">
                    <h5 class="text-sm font-semibold text-gray-700 mb-2">{{ $groupName }}</h5>
                    <div class="space-y-2">
                        @foreach($permissions as $key => $description)
                        <label class="flex items-center gap-2 text-sm text-gray-600">
                            <input type="checkbox" name="permissions[]" value="{{ $key }}" 
                                   {{ in_array($key, $rolePermissions) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-200">
                            <span>{{ $description }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-6">
                <a href="{{ route('management.roles.index') }}" class="rounded-lg bg-gray-200 px-6 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-300">
                    <i class="ik ik-x"></i> Cancel
                </a>
                <button type="submit" class="rounded-lg bg-primary-500 px-6 py-2.5 text-sm font-medium text-white transition hover:bg-primary-600">
                    <i class="ik ik-save"></i> Update Permissions
                </button>
            </div>
        </form>
    </x-card>
</div>
@endsection