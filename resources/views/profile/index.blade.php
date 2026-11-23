@extends('layouts.main')
@section('title', 'Profile')
@section('content')

<div class="max-w-4xl mx-auto">
    <x-page-header title="{{ __('Profile') }}" 
                   subtitle="{{ __('Manage your account settings and preferences') }}" 
                   icon="ik ik-user"
                   :breadcrumbs="['Home' => route('dashboard'), 'Profile' => null]" />

    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Left Column - Avatar & Info -->
        <div class="md:col-span-1">
            <div class="bg-white rounded-xl shadow-sm p-6 text-center">
                <div class="relative inline-block">
                    <img src="{{ $user->avatar }}" 
                         alt="{{ $user->full_name ?? $user->username }}" 
                         id="profile-avatar"
                         class="w-32 h-32 rounded-full object-cover mx-auto ring-4 ring-gray-100">
                    <label for="avatar-upload" class="absolute bottom-0 right-0 bg-primary-500 text-white p-2 rounded-full cursor-pointer hover:bg-primary-600 transition shadow-lg">
                        <i class="ik ik-camera text-sm"></i>
                        <input type="file" id="avatar-upload" class="hidden" accept="image/*">
                    </label>
                </div>
                <h3 class="mt-4 text-xl font-semibold text-gray-800">{{ $user->full_name ?? $user->username }}</h3>
                <p class="text-sm text-gray-500">{{ $user->email }}</p>
                <span class="inline-block mt-2 px-3 py-1 bg-primary-100 text-primary-700 rounded-full text-xs font-medium">
                    {{ ucfirst($user->role) }}
                </span>
                @if($user->assigned_branch)
                    <p class="mt-2 text-sm text-gray-500">
                        <i class="ik ik-map-pin mr-1"></i> 
                        {{ $user->branch->name ?? 'Branch' }}
                    </p>
                @endif
                <div id="avatar-notification" class="mt-3"></div>
            </div>

            <!-- Quick Stats -->
            <div class="mt-4 bg-white rounded-xl shadow-sm p-4">
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-600">Member since</span>
                    <span class="text-sm font-medium text-gray-800">{{ $user->created_at->format('d M Y') }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                    <span class="text-sm text-gray-600">2FA Enabled</span>
                    <span class="text-sm font-medium {{ $user->two_factor_enabled ? 'text-green-600' : 'text-gray-400' }}">
                        {{ $user->two_factor_enabled ? 'Yes (SMS)' : 'No' }}
                    </span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="text-sm text-gray-600">Password changed</span>
                    <span class="text-sm font-medium {{ $user->password_changed ? 'text-green-600' : 'text-amber-600' }}">
                        {{ $user->password_changed ? 'Yes' : 'Not yet' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Right Column - Profile Settings -->
        <div class="md:col-span-2 space-y-6">
            <!-- Profile Information -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h4 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Profile Information') }}</h4>
                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Full Name') }}</label>
                            <input type="text" name="full_name" value="{{ old('full_name', $user->full_name) }}" 
                                   class="w-full rounded-lg border border-gray-200 px-4 py-2 focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Username') }}</label>
                            <input type="text" value="{{ $user->username }}" disabled 
                                   class="w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 text-gray-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Email Address') }}</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" 
                                   class="w-full rounded-lg border border-gray-200 px-4 py-2 focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Phone Number') }}</label>
                            <input type="text" name="phone_number" value="{{ old('phone_number', $user->phone_number) }}" 
                                   class="w-full rounded-lg border border-gray-200 px-4 py-2 focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        </div>
                        <div>
                            <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition">
                                {{ __('Update Profile') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Change Password -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h4 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Change Password') }}</h4>
                <form action="{{ route('profile.password') }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Current Password') }}</label>
                            <input type="password" name="current_password" 
                                   class="w-full rounded-lg border border-gray-200 px-4 py-2 focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('New Password') }}</label>
                            <input type="password" name="password" 
                                   class="w-full rounded-lg border border-gray-200 px-4 py-2 focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Confirm New Password') }}</label>
                            <input type="password" name="password_confirmation" 
                                   class="w-full rounded-lg border border-gray-200 px-4 py-2 focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        </div>
                        <div>
                            <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition">
                                {{ __('Update Password') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Two-Factor Authentication (SMS) -->
            @php
                $twofaPending = session('twofa_setup:hash') && session('twofa_setup:expires') > now()->timestamp;
            @endphp
            <div id="two-factor" class="bg-white rounded-xl shadow-sm p-6" data-focus="{{ ($twofaPending || session('success') || $errors->has('two_factor') || $errors->has('code') || $errors->has('two_factor_password')) ? '1' : '0' }}">
                <div class="flex items-start justify-between gap-3 mb-1">
                    <h4 class="text-lg font-semibold text-gray-800">{{ __('Two-Factor Authentication') }}</h4>
                    <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $user->two_factor_enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ $user->two_factor_enabled ? 'Enabled' : 'Disabled' }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 mb-4">
                    Add an extra layer of security. When enabled, we send a 6-digit SMS code to your phone every time you sign in.
                </p>

                @error('two_factor')
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">{{ $message }}</div>
                @enderror

                @if($user->two_factor_enabled)
                    {{-- ============ ENABLED: offer to disable ============ --}}
                    <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800 mb-4">
                        <i class="ik ik-shield mr-1"></i>
                        Your account is protected. Codes are sent via SMS to
                        <strong>{{ $user->two_factor_secret ?: $user->phone_number }}</strong>.
                    </div>

                    <form action="{{ route('profile.2fa.disable') }}" method="POST"
                          onsubmit="return confirm('Turn off two-factor authentication? Your account will be less secure.');">
                        @csrf
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Confirm your password to turn it off') }}</label>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <input type="password" name="two_factor_password" autocomplete="current-password" required
                                   class="flex-1 rounded-lg border border-gray-200 px-4 py-2 focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                            <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
                                {{ __('Disable 2FA') }}
                            </button>
                        </div>
                    </form>

                @elseif($twofaPending)
                    {{-- ============ PENDING: enter the SMS code ============ --}}
                    <form action="{{ route('profile.2fa.enable') }}" method="POST">
                        @csrf
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Enter the 6-digit code we sent you') }}</label>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <input type="text" name="code" inputmode="numeric" maxlength="6" pattern="\d{6}"
                                   autocomplete="one-time-code" required placeholder="123456"
                                   class="flex-1 rounded-lg border border-gray-200 px-4 py-2 tracking-widest text-center text-lg focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                {{ __('Verify & Enable') }}
                            </button>
                        </div>
                    </form>

                    <div class="mt-3 flex items-center gap-4 text-sm">
                        <form action="{{ route('profile.2fa.send') }}" method="POST">
                            @csrf
                            <button type="submit" class="text-primary-600 hover:underline">{{ __('Resend code') }}</button>
                        </form>
                        <form action="{{ route('profile.2fa.cancel') }}" method="POST">
                            @csrf
                            <button type="submit" class="text-gray-500 hover:underline">{{ __('Cancel') }}</button>
                        </form>
                    </div>

                @else
                    {{-- ============ DISABLED: offer to enable ============ --}}
                    @if(empty($user->phone_number))
                        <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                            Add your phone number under <strong>Profile Information</strong> and save it, then come back here to enable 2FA.
                        </div>
                    @else
                        <div class="rounded-lg bg-gray-50 border border-gray-200 px-4 py-3 text-sm text-gray-700 mb-4">
                            Codes will be sent to <strong>{{ $user->phone_number }}</strong>.
                            Wrong number? Update it in Profile Information first.
                        </div>
                        <form action="{{ route('profile.2fa.send') }}" method="POST">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition">
                                {{ __('Enable 2FA') }}
                            </button>
                        </form>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Bring the 2FA card into view after sending a code / enabling / disabling
    const tf = document.getElementById('two-factor');
    if (tf && tf.dataset.focus === '1') { tf.scrollIntoView({ behavior: 'smooth', block: 'center' }); }

    const avatarInput = document.getElementById('avatar-upload');
    const avatarImg = document.getElementById('profile-avatar');
    const notificationDiv = document.getElementById('avatar-notification');
    
    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    showNotification('Please upload a valid image (JPEG, PNG, JPG, GIF)', 'error');
                    this.value = '';
                    return;
                }
                
                // Validate file size (2MB max)
                if (file.size > 2 * 1024 * 1024) {
                    showNotification('File size must be less than 2MB', 'error');
                    this.value = '';
                    return;
                }
                
                const formData = new FormData();
                formData.append('avatar', file);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                
                // Show loading state
                avatarImg.style.opacity = '0.5';
                avatarImg.style.transition = 'opacity 0.3s';
                showNotification('Uploading...', 'loading');
                
                fetch('{{ route("profile.avatar") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update avatar image with cache busting
                        avatarImg.src = data.avatar + '?t=' + new Date().getTime();
                        avatarImg.style.opacity = '1';
                        showNotification('Avatar updated successfully!', 'success');
                    } else {
                        avatarImg.style.opacity = '1';
                        showNotification(data.message || 'Failed to upload avatar', 'error');
                    }
                })
                .catch(error => {
                    avatarImg.style.opacity = '1';
                    console.error('Error:', error);
                    showNotification('Failed to upload avatar. Please try again.', 'error');
                });
            }
        });
    }
    
    // Helper function to show notifications
    function showNotification(message, type = 'success') {
        if (!notificationDiv) return;
        
        let bgColor, textColor, borderColor;
        
        switch(type) {
            case 'success':
                bgColor = 'bg-green-50';
                textColor = 'text-green-700';
                borderColor = 'border-green-200';
                break;
            case 'error':
                bgColor = 'bg-red-50';
                textColor = 'text-red-700';
                borderColor = 'border-red-200';
                break;
            case 'loading':
                bgColor = 'bg-blue-50';
                textColor = 'text-blue-700';
                borderColor = 'border-blue-200';
                break;
            default:
                bgColor = 'bg-gray-50';
                textColor = 'text-gray-700';
                borderColor = 'border-gray-200';
        }
        
        notificationDiv.className = `mt-3 px-4 py-2 rounded-lg ${bgColor} ${textColor} border ${borderColor} text-sm`;
        notificationDiv.textContent = message;
        
        // Auto remove after 5 seconds (except for loading)
        if (type !== 'loading') {
            setTimeout(() => {
                if (notificationDiv) {
                    notificationDiv.className = 'mt-3';
                    notificationDiv.textContent = '';
                }
            }, 5000);
        }
    }
});
</script>

@endsection