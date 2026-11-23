@extends('layouts.main')
@section('title', 'Employee Details')
@section('content')

<div class="max-w-4xl mx-auto">
    <x-page-header title="{{ __('Employee Details') }}" 
                   subtitle="{{ $employee->full_name ?? $employee->username }}" 
                   icon="ik ik-user"
                   :breadcrumbs="['Home' => route('dashboard'), 'HR' => route('hr.index'), 'Employees' => route('hr.employees'), 'Details' => null]" />

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <div class="flex items-center gap-6">
                <img src="{{ $employee->avatar ?? asset('img/default-avatar.png') }}" 
                     class="w-24 h-24 rounded-full object-cover" alt="">
                <div>
                    <h3 class="text-2xl font-semibold text-gray-800">{{ $employee->full_name ?? $employee->username }}</h3>
                    <p class="text-gray-500">{{ $employee->email }}</p>
                    <span class="inline-block mt-1 px-3 py-1 bg-primary-100 text-primary-700 rounded-full text-xs font-medium">
                        {{ ucfirst(str_replace('_', ' ', $employee->role)) }}
                    </span>
                    @if($employee->assigned_branch)
                        <span class="inline-block mt-1 ml-2 px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-medium">
                            <i class="ik ik-map-pin mr-1"></i> {{ $employee->branch->name ?? 'N/A' }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h5 class="font-semibold text-gray-700 mb-3">{{ __('Personal Information') }}</h5>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between py-1 border-b border-gray-50">
                            <dt class="text-gray-500">{{ __('Username') }}</dt>
                            <dd class="font-medium text-gray-700">{{ $employee->username }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-50">
                            <dt class="text-gray-500">{{ __('Email') }}</dt>
                            <dd class="font-medium text-gray-700">{{ $employee->email }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-50">
                            <dt class="text-gray-500">{{ __('Phone') }}</dt>
                            <dd class="font-medium text-gray-700">{{ $employee->phone_number ?? 'N/A' }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-50">
                            <dt class="text-gray-500">{{ __('Role') }}</dt>
                            <dd class="font-medium text-gray-700">{{ ucfirst(str_replace('_', ' ', $employee->role)) }}</dd>
                        </div>
                        <div class="flex justify-between py-1">
                            <dt class="text-gray-500">{{ __('Branch') }}</dt>
                            <dd class="font-medium text-gray-700">{{ $employee->branch->name ?? 'N/A' }}</dd>
                        </div>
                    </dl>
                </div>
                <div>
                    <h5 class="font-semibold text-gray-700 mb-3">{{ __('Account Information') }}</h5>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between py-1 border-b border-gray-50">
                            <dt class="text-gray-500">{{ __('Member Since') }}</dt>
                            <dd class="font-medium text-gray-700">{{ $employee->created_at ? $employee->created_at->format('d M Y') : 'N/A' }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-50">
                            <dt class="text-gray-500">{{ __('2FA Enabled') }}</dt>
                            <dd class="font-medium text-gray-700">{{ $employee->two_factor_enabled ? 'Yes' : 'No' }}</dd>
                        </div>
                        <div class="flex justify-between py-1">
                            <dt class="text-gray-500">{{ __('Password Changed') }}</dt>
                            <dd class="font-medium text-gray-700">{{ $employee->password_changed ? 'Yes' : 'Not yet' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection