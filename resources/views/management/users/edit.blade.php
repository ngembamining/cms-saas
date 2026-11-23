@extends('layouts.main')

@section('title', 'Edit User')

@section('content')
<x-page-header title="Edit User" subtitle="Update user information" icon="ik ik-edit"
    :breadcrumbs="['Home' => url('dashboard'), 'Management' => route('management.users.index'), 'Edit' => null]" />

<div class="max-w-3xl mx-auto">
    <x-card>
        <x-slot:header>{{ __('Edit User') }}</x-slot:header>

        @if(session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <i class="ik ik-alert-circle mr-2"></i>
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('management.users.update', $user->id) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-form.input name="username" label="Username" :value="$user->username" placeholder="Enter username" :required="true" />
                </div>

                <div>
                    <x-form.input name="full_name" label="Full Name" :value="$user->full_name" placeholder="Enter full name" />
                </div>

                <div>
                    <x-form.input name="email" label="Email Address" type="email" :value="$user->email" placeholder="Enter email address" />
                </div>

                <div>
                    <x-form.input name="phone_number" label="Phone Number" :value="$user->phone_number" placeholder="Enter phone number" />
                </div>

                <div>
                    <x-form.select name="role" label="Role" :required="true">
                        @foreach($roles as $key => $label)
                            <option value="{{ $key }}" {{ $user->role == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </x-form.select>
                </div>

                <div class="sm:col-span-2">
                    <x-form.select name="assigned_branch" label="Assigned Branch">
                        <option value="">None</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ $user->assigned_branch == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </x-form.select>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-6">
                <a href="{{ route('management.users.index') }}" class="rounded-lg bg-gray-200 px-6 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-300">
                    <i class="ik ik-x"></i> Cancel
                </a>
                <button type="submit" class="rounded-lg bg-primary-500 px-6 py-2.5 text-sm font-medium text-white transition hover:bg-primary-600">
                    <i class="ik ik-save"></i> Update User
                </button>
            </div>
        </form>
    </x-card>
</div>
@endsection