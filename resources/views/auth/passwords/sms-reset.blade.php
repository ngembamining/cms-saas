@php $inputCls = 'w-full rounded-xl border bg-gray-50 py-3 pl-11 pr-3 text-sm outline-none transition focus:bg-white focus:ring-4'; @endphp

<x-auth-split title="{{ __('Set new password') }} | {{ config('app.name') }}"
              heading="{{ __('Set a new password') }}"
              subheading="{{ __('Choose a strong password you have not used before.') }}"
              panelHeading="{{ __('Last step.') }}"
              panelText="{{ __('Once you save, you’ll be able to sign in with your new password immediately.') }}">

    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="ml-4 list-disc">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.sms.reset') }}" class="space-y-5">
        @csrf

        <div>
            <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('New password') }}</label>
            <div class="relative">
                <i class="ik ik-lock pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input id="password" type="password" name="password" placeholder="••••••••" required autocomplete="new-password"
                       class="{{ $inputCls }} border-gray-200 focus:border-primary-400 focus:ring-primary-100">
            </div>
            @error('password')<p class="mt-1.5 text-xs text-accent-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Confirm new password') }}</label>
            <div class="relative">
                <i class="ik ik-lock pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input id="password_confirmation" type="password" name="password_confirmation" placeholder="••••••••" required autocomplete="new-password"
                       class="{{ $inputCls }} border-gray-200 focus:border-primary-400 focus:ring-primary-100">
            </div>
        </div>

        <button type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-primary-500 to-primary-600 py-3 text-sm font-semibold text-white shadow-lg shadow-primary-500/25 transition hover:from-primary-600 hover:to-primary-700">
            {{ __('Save new password') }} <i class="ik ik-check"></i>
        </button>
    </form>
</x-auth-split>