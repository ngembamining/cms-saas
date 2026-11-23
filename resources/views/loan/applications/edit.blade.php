@extends('layouts.main')

@section('title', 'Edit Loan Application')

@section('content')
<x-page-header title="Edit Loan Application" subtitle="Edit loan application details" icon="ik ik-edit"
    :breadcrumbs="['Home' => url('dashboard'), 'Loans' => route('loans.index'), 'Applications' => route('loans.applications.index'), 'Edit' => null]" />

<div class="max-w-4xl mx-auto">
    <x-card>
        <x-slot:header>{{ __('Edit Loan Application') }}</x-slot:header>

        <form method="POST" action="{{ route('loans.applications.update', $loan->id) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-form.select name="branch_id" label="Branch" :required="true">
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ $loan->branch_id == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </x-form.select>
                </div>

                <div>
                    <x-form.select name="supplier_id" label="Supplier" :required="true">
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ $loan->suppliers_id == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </x-form.select>
                </div>

                <div>
                    <x-form.input name="amount" label="Amount (TZS)" type="number" step="1000" min="1000" :value="$loan->amount" :required="true" />
                </div>

                <div>
                    <x-form.input name="interest_rate" label="Interest Rate (%)" type="number" step="0.1" min="0" max="100" :value="$loan->interest_rate" :required="true" />
                </div>

                <div>
                    <x-form.input name="duration_months" label="Duration (Months)" type="number" min="1" max="60" :value="$loan->duration_months" :required="true" />
                </div>

                <div>
                    <x-form.input name="application_date" label="Application Date" type="date" :value="$loan->application_date ? $loan->application_date->format('Y-m-d') : ''" :required="true" />
                </div>

                <div class="sm:col-span-2">
                    <x-form.textarea name="description" label="Description" rows="3" placeholder="Enter any additional details">{{ $loan->description }}</x-form.textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-6">
                <a href="{{ route('loans.applications.index') }}" class="rounded-lg bg-gray-200 px-6 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-300">
                    <i class="ik ik-x"></i> Cancel
                </a>
                <button type="submit" class="rounded-lg bg-primary-500 px-6 py-2.5 text-sm font-medium text-white transition hover:bg-primary-600">
                    <i class="ik ik-save"></i> Update Application
                </button>
            </div>
        </form>
    </x-card>
</div>
@endsection