@extends('layouts.main')

@section('title', 'New Loan Application')

@section('content')
<x-page-header title="New Loan Application" subtitle="Submit a new loan application" icon="ik ik-file-plus"
    :breadcrumbs="['Home' => url('dashboard'), 'Loans' => route('loans.index'), 'Applications' => route('loans.applications.index'), 'New' => null]" />

<div class="max-w-4xl mx-auto">
    <x-card>
        <x-slot:header>{{ __('Submit Loan Application') }}</x-slot:header>

        @if(session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <i class="ik ik-alert-circle mr-2"></i>
                {{ session('error') }}
            </div>
        @endif

        @if(session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                <i class="ik ik-check-circle mr-2"></i>
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="mb-1 font-medium"><i class="ik ik-alert-circle mr-2"></i>Please fix the following before submitting:</p>
                <ul class="ml-6 list-disc">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('loans.applications.store') }}">
            @csrf

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-form.select name="branch_id" label="Branch" :required="true">
                        <option value="">Select Branch</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </x-form.select>
                </div>

                <div>
                    <x-form.select name="supplier_id" label="Supplier" :required="true">
                        <option value="">Select Supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </x-form.select>
                </div>

                <div>
                    <x-form.input name="amount" label="Amount (TZS)" type="number" step="1000" min="1000" placeholder="Enter amount" :required="true" />
                </div>

                <div>
                    <x-form.input name="interest_rate" label="Interest Rate (%)" type="number" step="0.1" min="0" max="100" placeholder="0" :required="true" />
                </div>

                <div>
                    <x-form.input name="duration_months" label="Duration (Months)" type="number" min="1" max="60" placeholder="Enter months" :required="true" />
                </div>

                <div>
                    <x-form.input name="application_date" label="Application Date" type="date" :required="true" value="{{ old('application_date', date('Y-m-d')) }}" />
                </div>

                <div class="sm:col-span-2">
                    <x-form.textarea name="description" label="Description" rows="3" placeholder="Enter any additional details about the loan" />
                </div>
            </div>

            {{-- ============================================ --}}
            {{-- SMS NOTIFICATIONS SECTION                    --}}
            {{-- ============================================ --}}
            <div class="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
                <h6 class="font-semibold text-blue-800">
                    <i class="ik ik-message-circle mr-2"></i>SMS Notifications
                </h6>
                <div class="mt-2 space-y-2 text-sm text-blue-700">
                    <div class="flex items-start gap-2">
                        <span class="mt-0.5">📱</span>
                        <div>
                            <strong>Supplier:</strong> Acknowledgment that the application was received
                            <span class="block text-xs text-blue-500 mt-0.5">Template: loan_application_supplier</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="mt-0.5">📱</span>
                        <div>
                            <strong>Branch Manager:</strong> Alert with full application details for review
                            <span class="block text-xs text-blue-500 mt-0.5">Template: loan_application_manager</span>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="mt-0.5">📱</span>
                        <div>
                            <strong>Directors:</strong> Full application report summary
                            <span class="block text-xs text-blue-500 mt-0.5">Template: loan_application_director</span>
                        </div>
                    </div>
                </div>
                <div class="mt-3 p-2 bg-blue-100 rounded-lg">
                    <p class="text-xs text-blue-700">
                        <i class="ik ik-info mr-1"></i>
                        SMS will be sent automatically after the application is successfully submitted.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-6">
                <a href="{{ route('loans.applications.index') }}" class="rounded-lg bg-gray-200 px-6 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-300">
                    <i class="ik ik-x"></i> Cancel
                </a>
                <button type="submit" class="rounded-lg bg-primary-500 px-6 py-2.5 text-sm font-medium text-white transition hover:bg-primary-600">
                    <i class="ik ik-save"></i> Submit Application
                </button>
            </div>
        </form>
    </x-card>
</div>
@endsection