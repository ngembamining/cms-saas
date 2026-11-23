@extends('layouts.main')

@section('title', 'SMS Settings')

@section('content')
<x-page-header title="SMS Settings" subtitle="Configure SMS settings" icon="ik ik-settings"
    :breadcrumbs="['Home' => url('dashboard'), 'Communication' => route('communication.sms.index'), 'Settings' => null]" />

<div class="max-w-4xl mx-auto">
    <x-card>
        <x-slot:header>{{ __('SMS Configuration') }}</x-slot:header>

        @if(session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                <i class="ik ik-check-circle mr-2"></i>
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('communication.sms.settings.update') }}">
            @csrf

            <div class="space-y-4">
                <div>
                    <x-form.input name="api_token" label="API Token" :value="$settings['api_token'] ?? ''" placeholder="Enter your KingoSMS API token" :required="true" />
                    <p class="mt-1 text-xs text-gray-400">Get your API token from your KingoSMS dashboard</p>
                </div>

                <div>
                    <x-form.input name="sender_id" label="Sender ID" :value="$settings['sender_id'] ?? 'NGEMBA MINE'" placeholder="Enter sender ID (max 11 chars)" maxlength="11" :required="true" />
                    <p class="mt-1 text-xs text-gray-400">This will appear as the sender name on recipient's phone</p>
                </div>

                <div>
                    <x-form.toggle name="is_active" label="Enable SMS Service" :checked="$settings['is_active'] ?? true" color="green" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-6">
                <a href="{{ route('communication.sms.index') }}" class="rounded-lg bg-gray-200 px-6 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-300">
                    <i class="ik ik-x"></i> Cancel
                </a>
                <button type="submit" class="rounded-lg bg-primary-500 px-6 py-2.5 text-sm font-medium text-white transition hover:bg-primary-600">
                    <i class="ik ik-save"></i> Save Settings
                </button>
            </div>
        </form>
    </x-card>
</div>
@endsection