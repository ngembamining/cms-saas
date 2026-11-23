@extends('layouts.main')

@section('title', 'Edit Customer')

@section('content')
<x-page-header title="Edit Customer" subtitle="Update customer information" icon="ik ik-edit"
    :breadcrumbs="['Home' => url('dashboard'), 'Customers' => route('customers.index'), 'Edit' => null]" />

<div class="max-w-3xl mx-auto">
    <x-card>
        <x-slot:header>{{ __('Edit Customer') }}</x-slot:header>

        @if(session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <i class="ik ik-alert-circle mr-2"></i>
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('customers.update', $customer->id) }}">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <div>
                    <x-form.input name="name" label="Customer Name" :value="$customer->name" placeholder="Enter customer name" :required="true" />
                </div>

                <div>
                    <x-form.input name="contact" label="Contact Number" :value="$customer->contact" placeholder="Enter contact number" :required="true" />
                </div>

                <div>
                    <x-form.input name="email" label="Email Address" type="email" :value="$customer->email" placeholder="Enter email address" />
                </div>

                <div>
                    <x-form.select name="branch_id" label="Branch" :required="true">
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ $customer->branch_id == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </x-form.select>
                </div>

                <div>
                    <x-form.textarea name="address" label="Address" rows="3" placeholder="Enter customer address">{{ $customer->address }}</x-form.textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-6">
                <a href="{{ route('customers.index') }}" class="rounded-lg bg-gray-200 px-6 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-300">
                    <i class="ik ik-x"></i> Cancel
                </a>
                <button type="submit" class="rounded-lg bg-primary-500 px-6 py-2.5 text-sm font-medium text-white transition hover:bg-primary-600">
                    <i class="ik ik-save"></i] Update Customer
                </button>
            </div>
        </form>
    </x-card>
</div>
@endsection