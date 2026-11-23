@extends('layouts.main')

@section('title', $user->username)

@section('content')
<x-page-header title="{{ $user->username }}" subtitle="User details" icon="ik ik-user"
    :breadcrumbs="['Home' => url('dashboard'), 'Management' => route('management.users.index'), $user->username => null]">
    <div class="flex gap-2">
        <a href="{{ route('management.users.edit', $user->id) }}" class="btn btn-warning">
            <i class="ik ik-edit"></i> Edit
        </a>
        <a href="{{ route('management.users.index') }}" class="btn btn-secondary">
            <i class="ik ik-arrow-left"></i> Back
        </a>
    </div>
</x-page-header>

<div class="max-w-3xl mx-auto">
    <x-card>
        <x-slot:header>{{ __('User Information') }}</x-slot:header>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-gray-500">Username</p>
                <p class="font-medium">{{ $user->username }}</p>
            </div>
            <div>
                <p class="text-gray-500">Full Name</p>
                <p class="font-medium">{{ $user->full_name ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-500">Email</p>
                <p class="font-medium">{{ $user->email ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-500">Phone Number</p>
                <p class="font-medium">{{ $user->phone_number ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-500">Role</p>
                <p><span class="badge badge-{{ $user->role == 'director' ? 'primary' : ($user->role == 'branch_manager' ? 'green' : ($user->role == 'accountant' ? 'blue' : 'gray')) }}">
                    {{ $user->role_label }}
                </span></p>
            </div>
            <div>
                <p class="text-gray-500">Branch</p>
                <p class="font-medium">{{ $user->branch->name ?? 'N/A' }}</p>
            </div>
            <div>
                <p class="text-gray-500">Two Factor Auth</p>
                <p class="font-medium">{{ $user->two_factor_enabled ? 'Enabled' : 'Disabled' }}</p>
            </div>
            <div>
                <p class="text-gray-500">Password Changed</p>
                <p class="font-medium">{{ $user->password_changed ? 'Yes' : 'No' }}</p>
            </div>
        </div>
    </x-card>
</div>
@endsection