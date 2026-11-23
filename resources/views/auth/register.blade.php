@php
    $inputCls = 'w-full rounded-xl border bg-gray-50 py-3 pl-11 pr-3 text-sm outline-none transition focus:bg-white focus:ring-4';
@endphp

<x-auth-split title="{{ __('Create account') }} | {{ config('app.name') }}"
              heading="{{ __('Create your account') }}"
              subheading="{{ __('Get started in less than a minute.') }}">

    <form method="POST" action="{{ route('register.post') }}" class="space-y-5" x-data="{ show: false, showConfirm: false }">
        @csrf

        {{-- Full Name --}}
        <div>
            <label for="full_name" class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Full name') }}</label>
            <div class="relative">
                <i class="ik ik-user pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input id="full_name" type="text" name="full_name" placeholder="e.g. Jane Doe"
                       value="{{ old('full_name') }}" required autocomplete="name" autofocus
                       @class([
                           $inputCls,
                           'border-gray-200 focus:border-primary-400 focus:ring-primary-100' => ! $errors->has('full_name'),
                           'border-accent-400 focus:ring-accent-400/20' => $errors->has('full_name'),
                       ])>
            </div>
            @error('full_name')<p class="mt-1.5 text-xs text-accent-600">{{ $message }}</p>@enderror
        </div>

        {{-- Username --}}
        <div>
            <label for="username" class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Username') }}</label>
            <div class="relative">
                <i class="ik ik-at-sign pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input id="username" type="text" name="username" placeholder="e.g. jane.doe"
                       value="{{ old('username') }}" required autocomplete="username"
                       @class([
                           $inputCls,
                           'border-gray-200 focus:border-primary-400 focus:ring-primary-100' => ! $errors->has('username'),
                           'border-accent-400 focus:ring-accent-400/20' => $errors->has('username'),
                       ])>
            </div>
            @error('username')<p class="mt-1.5 text-xs text-accent-600">{{ $message }}</p>@enderror
        </div>

        {{-- Email --}}
        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Email') }}</label>
            <div class="relative">
                <i class="ik ik-mail pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input id="email" type="email" name="email" placeholder="you@example.com"
                       value="{{ old('email') }}" required autocomplete="email"
                       @class([
                           $inputCls,
                           'border-gray-200 focus:border-primary-400 focus:ring-primary-100' => ! $errors->has('email'),
                           'border-accent-400 focus:ring-accent-400/20' => $errors->has('email'),
                       ])>
            </div>
            @error('email')<p class="mt-1.5 text-xs text-accent-600">{{ $message }}</p>@enderror
        </div>

        {{-- Password --}}
        <div>
            <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Password') }}</label>
            <div class="relative">
                <i class="ik ik-lock pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input id="password" name="password" placeholder="••••••••" required autocomplete="new-password"
                       :type="show ? 'text' : 'password'"
                       @class([
                           str_replace('pr-3', 'pr-11', $inputCls),
                           'border-gray-200 focus:border-primary-400 focus:ring-primary-100' => ! $errors->has('password'),
                           'border-accent-400 focus:ring-accent-400/20' => $errors->has('password'),
                       ])>
                <button type="button" @click="show = !show" tabindex="-1"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 transition hover:text-gray-600">
                    <i class="ik" :class="show ? 'ik-eye-off' : 'ik-eye'"></i>
                </button>
            </div>
            @error('password')<p class="mt-1.5 text-xs text-accent-600">{{ $message }}</p>@enderror
            <p class="mt-1.5 text-xs text-gray-400">{{ __('Minimum 8 characters.') }}</p>
        </div>

        {{-- Confirm Password --}}
        <div>
            <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Confirm password') }}</label>
            <div class="relative">
                <i class="ik ik-lock pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input id="password_confirmation" name="password_confirmation" placeholder="••••••••" required autocomplete="new-password"
                       :type="showConfirm ? 'text' : 'password'"
                       @class([
                           str_replace('pr-3', 'pr-11', $inputCls),
                           'border-gray-200 focus:border-primary-400 focus:ring-primary-100' => ! $errors->has('password_confirmation'),
                           'border-accent-400 focus:ring-accent-400/20' => $errors->has('password_confirmation'),
                       ])>
                <button type="button" @click="showConfirm = !showConfirm" tabindex="-1"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 transition hover:text-gray-600">
                    <i class="ik" :class="showConfirm ? 'ik-eye-off' : 'ik-eye'"></i>
                </button>
            </div>
            @error('password_confirmation')<p class="mt-1.5 text-xs text-accent-600">{{ $message }}</p>@enderror
        </div>

        <button type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-primary-500 to-primary-600 py-3 text-sm font-semibold text-white shadow-lg shadow-primary-500/25 transition hover:from-primary-600 hover:to-primary-700">
            {{ __('Create account') }} <i class="ik ik-arrow-right"></i>
        </button>

        <p class="text-center text-sm text-gray-500">
            {{ __('Already have an account?') }}
            <a href="{{ route('login') }}" class="font-semibold text-primary-600 hover:underline">{{ __('Sign in') }}</a>
        </p>
    </form>
</x-auth-split>