@php $inputCls = 'w-full rounded-xl border bg-gray-50 py-3 pl-11 pr-3 text-sm outline-none transition focus:bg-white focus:ring-4'; @endphp

<x-auth-split title="{{ __('Verify reset code') }} | {{ config('app.name') }}"
              heading="{{ __('Enter your reset code') }}"
              subheading="{{ __('We sent a 6-digit code to your phone. Enter it below to continue.') }}"
              panelHeading="{{ __('Almost there.') }}"
              panelText="{{ __('Your code is valid for 10 minutes. Do not share it with anyone.') }}">

    @if (session('status'))
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            <i class="ik ik-check-circle mt-0.5"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <i class="ik ik-alert-circle mt-0.5"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="ml-4 list-disc">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.sms.verify') }}" class="space-y-5">
        @csrf

        <div>
            <label for="code" class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Reset code') }}</label>
            <div class="relative">
                <i class="ik ik-hash pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input id="code" type="text" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6"
                       placeholder="123456" required autofocus autocomplete="one-time-code"
                       class="{{ $inputCls }} text-center text-lg tracking-[0.5em] font-mono border-gray-200 focus:border-primary-400 focus:ring-primary-100">
            </div>
            @error('code')<p class="mt-1.5 text-xs text-accent-600">{{ $message }}</p>@enderror
        </div>

        <button type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-primary-500 to-primary-600 py-3 text-sm font-semibold text-white shadow-lg shadow-primary-500/25 transition hover:from-primary-600 hover:to-primary-700">
            {{ __('Verify code') }} <i class="ik ik-check"></i>
        </button>
    </form>

    <form method="POST" action="{{ route('password.sms.resend') }}" class="mt-3 text-center">
        @csrf
        <button type="submit" class="text-sm text-primary-600 hover:underline">
            <i class="ik ik-refresh-cw"></i> {{ __('Resend code') }}
        </button>
    </form>

    <p class="mt-5 text-center text-sm text-gray-500">
        <a href="{{ route('password.request') }}" class="inline-flex items-center gap-1 font-semibold text-primary-600 hover:underline">
            <i class="ik ik-arrow-left text-xs"></i> {{ __('Use a different phone number') }}
        </a>
    </p>
</x-auth-split>