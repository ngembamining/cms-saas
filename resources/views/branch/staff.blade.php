@extends('layouts.main')
@section('title', 'Branch Staff')
@section('content')

<div class="max-w-7xl mx-auto">
    <x-page-header title="{{ $branch->name }} - {{ __('Staff') }}" 
                   subtitle="{{ __('View branch staff members') }}" 
                   icon="ik ik-users"
                   :breadcrumbs="['Home' => route('dashboard'), 'My Branch' => route('branch.dashboard'), 'Staff' => null]" />

    <!-- Staff Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Staff</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center">
                    <i class="ik ik-users text-primary-500 text-xl"></i>
                </div>
            </div>
        </div>

        @foreach($stats['by_role'] ?? [] as $role => $count)
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">{{ ucfirst($role) }}</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $count }}</p>
                    </div>
                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                        <i class="ik ik-user text-gray-500 text-xl"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Staff List -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h5 class="font-semibold text-gray-700">{{ __('Staff Members') }}</h5>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-6 py-3 font-medium">{{ __('Name') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Role') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Email') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Phone') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($staff ?? [] as $member)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $member->avatar ?? asset('img/default-avatar.png') }}" 
                                         class="w-8 h-8 rounded-full object-cover" alt="">
                                    <span class="font-medium text-gray-700">{{ $member->full_name ?? $member->username }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-3">
                                <span class="px-2 py-1 bg-gray-100 rounded text-xs">
                                    {{ ucfirst($member->role) }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-gray-600">{{ $member->email }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $member->phone_number ?? '-' }}</td>
                            <td class="px-6 py-3">
                                <x-badge color="success">{{ __('Active') }}</x-badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                                {{ __('No staff members found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection