@extends('layouts.main')

@section('title', 'Cash Withdrawal')

@section('content')
<x-page-header title="Cash Withdrawal" subtitle="Record a cash withdrawal" icon="ik ik-arrow-down"
    :breadcrumbs="['Home' => url('dashboard'), 'Transactions' => route('transactions.index'), 'Cash' => route('transactions.cash.index'), 'Withdraw' => null]" />

<div class="max-w-2xl mx-auto">
    <x-card>
        <x-slot:header>{{ __('Record Cash Withdrawal') }}</x-slot:header>

        @if(session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <i class="ik ik-alert-circle mr-2"></i>
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('transactions.cash.withdraw.store') }}">
            @csrf

            <div class="space-y-4">
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
                    <x-form.input name="amount" label="Amount (TZS)" type="number" step="0.01" min="0.01" placeholder="Enter amount" :required="true" />
                    <p class="mt-1 text-xs text-gray-400">Make sure you have sufficient cash balance</p>
                </div>

                <div>
                    <x-form.select name="payment_method" label="Payment Method" :required="true">
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="mobile_money">Mobile Money</option>
                    </x-form.select>
                </div>

                <div>
                    <x-form.input name="payment_reference" label="Payment Reference" placeholder="Enter payment reference" />
                </div>

                <div>
                    <x-form.textarea name="description" label="Description" rows="3" placeholder="Enter any additional details" />
                </div>

                <!-- ============================================ -->
                <!-- SMS NOTIFICATIONS SECTION -->
                <!-- ============================================ -->
                <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <h6 class="font-semibold text-blue-800">
                        <i class="ik ik-message-circle mr-2"></i>SMS Notifications
                    </h6>
                    <div class="mt-2 space-y-2 text-sm text-blue-700">
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5">📱</span>
                            <div>
                                <strong>Branch Manager:</strong> Withdrawal alert with amount and details
                            </div>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="mt-0.5">📱</span>
                            <div>
                                <strong>Directors:</strong> Withdrawal report summary
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 p-2 bg-blue-100 rounded-lg">
                        <p class="text-xs text-blue-700">
                            <i class="ik ik-info mr-1"></i>
                            SMS will be sent automatically after successful withdrawal recording.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-6">
                <a href="{{ route('transactions.cash.index') }}" class="rounded-lg bg-gray-200 px-6 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-300">
                    <i class="ik ik-x"></i> Cancel
                </a>
                <button type="submit" class="rounded-lg bg-red-500 px-6 py-2.5 text-sm font-medium text-white transition hover:bg-red-600">
                    <i class="ik ik-save"></i> Record Withdrawal
                </button>
            </div>
        </form>
    </x-card>
</div>
@endsection