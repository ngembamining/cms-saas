@extends('layouts.main')

@section('title', 'Enable 2FA - SMS')

@section('content')
<div class="max-w-2xl mx-auto">
    <x-card>
        <x-slot:header>Enable Two-Factor Authentication (SMS)</x-slot:header>

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="ml-4 list-disc">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <p class="text-sm text-gray-600 mb-4">
            We've sent a 6-digit verification code to <strong>{{ $user->phone_number }}</strong>.
            Enter it below to confirm.
        </p>

        <form method="POST" action="{{ route('2fa.confirm.sms') }}" class="space-y-4">
            @csrf
            <div>
                <label for="code" class="mb-1.5 block text-sm font-medium text-gray-700">
                    Verification Code
                </label>
                <input id="code" name="code" type="text" inputmode="numeric" maxlength="6" required autofocus
                       class="w-full rounded-xl border border-gray-200 bg-gray-50 py-3 px-4 text-center text-lg tracking-widest font-mono outline-none transition focus:bg-white focus:border-primary-400 focus:ring-4 focus:ring-primary-100"
                       placeholder="••••••">
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('profile.edit') }}" class="rounded-lg bg-gray-200 px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-300">
                    Cancel
                </a>
                <button type="submit" class="rounded-lg bg-primary-500 px-6 py-2.5 text-sm font-medium text-white hover:bg-primary-600">
                    <i class="ik ik-check"></i> Confirm & Enable
                </button>
            </div>
        </form>
    </x-card>
</div>
@endsection