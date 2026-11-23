@extends('layouts.main')

@section('title', 'New Branch')

@section('content')
<x-page-header title="New Branch" subtitle="Add a new branch" icon="fas fa-store"
    :breadcrumbs="['Home' => url('dashboard'), 'Management' => route('management.users.index'), 'Branches' => route('management.branches.index'), 'New' => null]" />

<div class="max-w-3xl mx-auto">
    <x-card>
        <x-slot:header>{{ __('Create New Branch') }}</x-slot:header>

        @if(session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <i class="ik ik-alert-circle mr-2"></i>
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('management.branches.store') }}">
            @csrf

            <div class="space-y-4">
                <div>
                    <x-form.input name="name" label="Branch Name" placeholder="Enter branch name" :required="true" />
                </div>

                <div>
                    <x-form.input name="location" label="Location" placeholder="Enter branch location" :required="true" />
                </div>

                <div>
                    <x-form.input name="gold_available" label="Initial Gold (g)" type="number" step="0.01" min="0" placeholder="0.00" />
                    <p class="mt-1 text-xs text-gray-400">Optional: Set initial gold inventory for this branch</p>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-6">
                <a href="{{ route('management.branches.index') }}" class="rounded-lg bg-gray-200 px-6 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-300">
                    <i class="ik ik-x"></i> Cancel
                </a>
                <button type="submit" class="rounded-lg bg-primary-500 px-6 py-2.5 text-sm font-medium text-white transition hover:bg-primary-600">
                    <i class="ik ik-save"></i> Create Branch
                </button>
            </div>
        </form>
    </x-card>
</div>
@endsection