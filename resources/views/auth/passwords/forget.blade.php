@php
    $inputCls = 'w-full rounded-xl border bg-gray-50 py-3 pl-11 pr-3 text-sm outline-none transition focus:bg-white focus:ring-4';
@endphp

<x-auth-split title="{{ __('Forgot password') }} | {{ config('app.name') }}"
              heading="{{ __('Forgot your password?') }}"
              subheading="{{ __('Enter your phone number to receive a reset code.') }}"
              panelHeading="{{ __('Reset your access in seconds.') }}"
              panelText="{{ __('We’ll send you a secure code to reset your password — no support ticket required.') }}">

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

    {{-- SMS Form Only --}}
    <form method="POST" action="{{ route('password.sms.send') }}" class="space-y-5">
        @csrf
        <div>
            <label for="phone_number" class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Phone number') }}</label>
            <div class="relative">
                <i class="ik ik-phone pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input id="phone_number" type="tel" name="phone_number" placeholder="0712 345 678" value="{{ old('phone_number') }}" required autocomplete="tel"
                       @class([
                           $inputCls,
                           'border-gray-200 focus:border-primary-400 focus:ring-primary-100' => ! $errors->has('phone_number'),
                           'border-accent-400 focus:ring-accent-400/20' => $errors->has('phone_number'),
                       ])>
            </div>
            @error('phone_number')<p class="mt-1.5 text-xs text-accent-600">{{ $message }}</p>@enderror
            <p class="mt-2 text-xs text-gray-500">
                {{ __('We\'ll text you a 6-digit code to reset your password.') }}
            </p>
        </div>

        <button type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-primary-500 to-primary-600 py-3 text-sm font-semibold text-white shadow-lg shadow-primary-500/25 transition hover:from-primary-600 hover:to-primary-700">
            {{ __('Send reset code via SMS') }} <i class="ik ik-message-square"></i>
        </button>
    </form>

    <p class="text-center text-sm text-gray-500">
        <a href="{{ url('login') }}" class="inline-flex items-center gap-1 font-semibold text-primary-600 hover:underline">
            <i class="ik ik-arrow-left text-xs"></i> {{ __('Back to sign in') }}
        </a>
    </p>
</x-auth-split>