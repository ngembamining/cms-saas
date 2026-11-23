@extends('layouts.main')

@section('title', 'Edit Expense')

@section('content')
<x-page-header title="Edit Expense" subtitle="Update expense details" icon="ik ik-edit"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Expenses' => route('finance.expenses.index'), 'Edit' => null]" />

<div class="max-w-3xl mx-auto">
    <x-card>
        <x-slot:header>{{ __('Edit Expense') }}</x-slot:header>

        @if(session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <i class="ik ik-alert-circle mr-2"></i>
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('finance.expenses.update', $expense->id) }}">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <div>
                    <x-form.select name="branch_id" label="Branch" :required="true">
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ $expense->branch_id == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </x-form.select>
                </div>

                <div>
                    <x-form.select name="category" label="Category" :required="true">
                        @foreach($categories as $category)
                            <option value="{{ $category->name }}" {{ $expense->category == $category->name ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </x-form.select>
                </div>

                <div>
                    <x-form.input name="amount" label="Amount (TZS)" type="number" step="0.01" min="0.01" :value="$expense->amount" :required="true" />
                </div>

                <div>
                    <x-form.textarea name="description" label="Description" rows="3" placeholder="Enter expense description">{{ $expense->description }}</x-form.textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-6">
                <a href="{{ route('finance.expenses.index') }}" class="rounded-lg bg-gray-200 px-6 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-300">
                    <i class="ik ik-x"></i> Cancel
                </a>
                <button type="submit" class="rounded-lg bg-primary-500 px-6 py-2.5 text-sm font-medium text-white transition hover:bg-primary-600">
                    <i class="ik ik-save"></i> Update Expense
                </button>
            </div>
        </form>
    </x-card>
</div>
@endsection