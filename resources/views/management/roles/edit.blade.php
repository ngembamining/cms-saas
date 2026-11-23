@extends('layouts.main')

@section('title', 'Edit Role')

@section('content')
<x-page-header title="Edit Role" subtitle="Update role information" icon="ik ik-edit"
    :breadcrumbs="['Home' => url('dashboard'), 'Management' => route('management.users.index'), 'Roles' => route('management.roles.index'), 'Edit' => null]" />

<div class="max-w-4xl mx-auto">
    <x-card>
        <x-slot:header>{{ __('Edit Role') }}</x-slot:header>

        @if(session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <i class="ik ik-alert-circle mr-2"></i>
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('management.roles.update', $role['name'] ?? '') }}">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <div>
                    <x-form.input name="name" label="Role Name" :value="$role['label'] ?? ''" placeholder="Enter role name" :required="true" />
                </div>

                <div>
                    <x-form.textarea name="description" label="Description" rows="3" placeholder="Enter role description">{{ $role['description'] ?? '' }}</x-form.textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-6">
                <a href="{{ route('management.roles.index') }}" class="rounded-lg bg-gray-200 px-6 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-300">
                    <i class="ik ik-x"></i> Cancel
                </a>
                <button type="submit" class="rounded-lg bg-primary-500 px-6 py-2.5 text-sm font-medium text-white transition hover:bg-primary-600">
                    <i class="ik ik-save"></i> Update Role
                </button>
            </div>
        </form>
    </x-card>
</div>
@endsection