@extends('layouts.main')

@section('title', 'New Expense Category')

@section('content')
<x-page-header title="New Expense Category" subtitle="Add a new expense category" icon="ik ik-plus"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Expenses' => route('finance.expenses.index'), 'Categories' => route('finance.expenses.categories.index'), 'New' => null]" />

<div class="max-w-3xl mx-auto">
    <x-card>
        <x-slot:header>{{ __('Create New Expense Category') }}</x-slot:header>

        @if(session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <i class="ik ik-alert-circle mr-2"></i>
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('finance.expenses.categories.store') }}">
            @csrf

            <div class="space-y-4">
                <div>
                    <x-form.input name="name" label="Category Name" placeholder="Enter category name" :required="true" />
                    <p class="mt-1 text-xs text-gray-400">Examples: Office Supplies, Transport, Utilities, etc.</p>
                </div>

                <div>
                    <x-form.textarea name="description" label="Description" rows="3" placeholder="Enter category description" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-6">
                <a href="{{ route('finance.expenses.categories.index') }}" class="rounded-lg bg-gray-200 px-6 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-300">
                    <i class="ik ik-x"></i> Cancel
                </a>
                <button type="submit" class="rounded-lg bg-primary-500 px-6 py-2.5 text-sm font-medium text-white transition hover:bg-primary-600">
                    <i class="ik ik-save"></i> Create Category
                </button>
            </div>
        </form>
    </x-card>
</div>
@endsection