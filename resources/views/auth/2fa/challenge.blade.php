@php $inputCls = 'w-full rounded-xl border bg-gray-50 py-3 px-4 text-center text-lg tracking-widest font-mono outline-none transition focus:bg-white focus:ring-4'; @endphp

<x-auth-split title="{{ __('Two-Factor Authentication') }} | {{ config('app.name') }}"
              heading="{{ __('Verify your identity') }}"
              subheading="{{ __('Enter the code from your authenticator app or the code we sent to your phone.') }}">

    @if(session('status'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            <i class="ik ik-check-circle mr-2"></i>{{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="ml-4 list-disc">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($method === 'authenticator')
        <p class="text-sm text-gray-600 mb-4">
            Open your authenticator app and enter the 6-digit code shown for <strong>{{ config('app.name') }}</strong>.
        </p>
    @else
        <p class="text-sm text-gray-600 mb-4">
            We've sent a 6-digit code to <strong>{{ $user->phone_number }}</strong>. It expires in 5 minutes.
        </p>
    @endif

    <form method="POST" action="{{ route('2fa.verify') }}" class="space-y-5">
        @csrf

        <div>
            <label for="code" class="mb-1.5 block text-sm font-medium text-gray-700">
                {{ __('Verification Code') }}
            </label>
            <div class="relative">
                <i class="ik ik-shield pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input
                    id="code"
                    name="code"
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9A-Za-z]*"
                    maxlength="10"
                    autofocus
                    autocomplete="one-time-code"
                    required
                    @class([
                        $inputCls,
                        'border-gray-200 focus:border-primary-400 focus:ring-primary-100' => ! $errors->has('code'),
                        'border-accent-400 focus:ring-accent-400/20' => $errors->has('code'),
                    ])
                    placeholder="••••••"
                >
            </div>
        </div>

        <button type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-primary-500 to-primary-600 py-3 text-sm font-semibold text-white shadow-lg shadow-primary-500/25 transition hover:from-primary-600 hover:to-primary-700">
            <i class="ik ik-check"></i> {{ __('Verify & Continue') }}
        </button>
    </form>

    @if($method === 'sms')
        <form method="POST" action="{{ route('2fa.resend') }}" class="mt-3 text-center">
            @csrf
            <button type="submit" class="text-sm text-primary-600 hover:underline">
                <i class="ik ik-refresh-cw"></i> {{ __('Resend code') }}
            </button>
        </form>
    @endif

    <p class="mt-4 text-center text-xs text-gray-500">
        {{ __('Lost your device? Enter a recovery code in the field above.') }}
    </p>

    <div class="mt-4 border-t border-gray-200 pt-4 text-center">
        <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">
                {{ __('Cancel and return to login') }}
            </button>
        </form>
    </div>
</x-auth-split>