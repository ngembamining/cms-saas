@extends('layouts.main')
@section('title', 'Employees')
@section('content')

<div class="max-w-7xl mx-auto">
    <x-page-header title="{{ __('Employees') }}" 
                   subtitle="{{ __('Manage all employees') }}" 
                   icon="ik ik-users"
                   :breadcrumbs="['Home' => route('dashboard'), 'HR' => route('hr.index'), 'Employees' => null]" />

    <!-- Filter Section -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
        <form action="{{ route('hr.employees') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Search') }}</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Name, email or username..."
                       class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Role') }}</label>
                <select name="role" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    <option value="">{{ __('All Roles') }}</option>
                    @foreach($roles ?? [] as $role)
                        <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $role)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Branch') }}</label>
                <select name="branch_id" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    <option value="">{{ __('All Branches') }}</option>
                    @foreach($branches ?? [] as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition">
                <i class="ik ik-search mr-2"></i> {{ __('Apply') }}
            </button>
            <a href="{{ route('hr.employees') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="ik ik-rotate-ccw mr-2"></i> {{ __('Reset') }}
            </a>
        </form>
    </div>

    <!-- Employees Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-6 py-3 font-medium">{{ __('Name') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Username') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Role') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Branch') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Email') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($employees ?? [] as $employee)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $employee->avatar ?? asset('img/default-avatar.png') }}" 
                                         class="w-8 h-8 rounded-full object-cover" alt="">
                                    <span class="font-medium text-gray-700">{{ $employee->full_name ?? $employee->username }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-gray-600">{{ $employee->username }}</td>
                            <td class="px-6 py-3">
                                <span class="px-2 py-1 bg-gray-100 rounded text-xs">
                                    {{ ucfirst(str_replace('_', ' ', $employee->role)) }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-gray-600">{{ $employee->branch->name ?? 'N/A' }}</td>
                            <td class="px-6 py-3 text-gray-600">{{ $employee->email }}</td>
                            <td class="px-6 py-3">
                                <a href="{{ route('hr.employees.show', $employee->id) }}" 
                                   class="text-blue-500 hover:text-blue-600">
                                    <i class="ik ik-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                                <i class="ik ik-users text-3xl block mb-2"></i>
                                {{ __('No employees found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-gray-100">
            {{ $employees->links() }}
        </div>
    </div>
</div>

@endsection