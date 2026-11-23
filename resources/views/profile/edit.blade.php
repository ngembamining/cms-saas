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
            <i class="ik ik-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            <i class="ik ik-alert-circle mr-2"></i>{{ session('error') }}
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
                    <span class="text-sm font-medium {{ $user->hasTwoFactorEnabled() ? 'text-green-600' : 'text-gray-400' }}">
                        {{ $user->hasTwoFactorEnabled() ? 'Yes (' . $user->two_factor_method_label . ')' : 'No' }}
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

            {{-- ============================================ --}}
            {{-- TWO-FACTOR AUTHENTICATION SECTION             --}}
            {{-- ============================================ --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-gray-800">
                        <i class="ik ik-shield mr-2 text-primary-600"></i>
                        {{ __('Two-Factor Authentication') }}
                    </h4>
                    @if($user->hasTwoFactorEnabled())
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                            <i class="ik ik-check-circle"></i> Enabled
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                            <i class="ik ik-x-circle"></i> Disabled
                        </span>
                    @endif
                </div>

                @if($user->hasTwoFactorEnabled())
                    {{-- ============================================= --}}
                    {{-- ENABLED STATE                                  --}}
                    {{-- ============================================= --}}
                    <div class="space-y-4">
                        <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                            <div class="flex items-start gap-3">
                                <i class="ik ik-shield-check text-green-600 text-2xl mt-0.5"></i>
                                <div class="flex-1">
                                    <p class="font-medium text-green-800">
                                        Your account is protected by 2FA
                                    </p>
                                    <p class="text-sm text-green-700 mt-1">
                                        Method: <strong>{{ $user->two_factor_method_label }}</strong>
                                    </p>
                                    @if($user->two_factor_confirmed_at)
                                        <p class="text-xs text-green-600 mt-1">
                                            Enabled on {{ $user->two_factor_confirmed_at->format('d M Y, H:i') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Recovery Codes --}}
                        @if($user->two_factor_recovery_codes)
                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                                <div class="flex items-start gap-3">
                                    <i class="ik ik-key text-amber-600 text-xl mt-0.5"></i>
                                    <div class="flex-1">
                                        <p class="font-medium text-amber-800 text-sm">Recovery Codes</p>
                                        <p class="text-xs text-amber-700 mt-1">
                                            You have recovery codes saved. Keep them safe — each can be used once.
                                        </p>
                                        <details class="mt-2">
                                            <summary class="text-xs font-medium text-amber-700 cursor-pointer hover:underline">
                                                View my recovery codes
                                            </summary>
                                            <div class="mt-2 grid grid-cols-2 gap-2">
                                                @foreach(json_decode(decrypt($user->two_factor_recovery_codes), true) ?? [] as $code)
                                                    <code class="rounded bg-white border border-amber-200 px-2 py-1 text-xs font-mono text-center tracking-widest">
                                                        {{ $code }}
                                                    </code>
                                                @endforeach
                                            </div>
                                        </details>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Disable Button --}}
                        <div class="pt-2 border-t border-gray-100">
                            <button type="button"
                                    onclick="document.getElementById('disable2faModal').classList.remove('hidden'); document.getElementById('disable2faModal').classList.add('flex');"
                                    class="text-sm text-red-600 hover:text-red-700 font-medium">
                                <i class="ik ik-x-circle mr-1"></i> Disable Two-Factor Authentication
                            </button>
                        </div>
                    </div>

                @else
                    {{-- ============================================= --}}
                    {{-- DISABLED STATE — SETUP OPTIONS                 --}}
                    {{-- ============================================= --}}
                    <div class="space-y-4">
                        <p class="text-sm text-gray-600">
                            Add an extra layer of security to your account. When enabled, you'll need to enter
                            a verification code each time you sign in.
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            {{-- Authenticator App Option --}}
                            <a href="{{ route('2fa.setup', ['method' => 'authenticator']) }}"
                               class="group flex items-start gap-3 rounded-lg border-2 border-gray-200 bg-white p-4 transition hover:border-primary-400 hover:bg-primary-50">
                                <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center">
                                    <i class="ik ik-smartphone text-primary-600 text-xl"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="font-semibold text-gray-800 text-sm">Authenticator App</p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Use Google Authenticator, Authy, or 1Password
                                    </p>
                                    <span class="inline-block mt-2 text-xs font-medium text-primary-600 group-hover:underline">
                                        Set up →
                                    </span>
                                </div>
                            </a>

                            {{-- SMS Option --}}
                            @if($user->phone_number)
                                <a href="{{ route('2fa.setup', ['method' => 'sms']) }}"
                                   class="group flex items-start gap-3 rounded-lg border-2 border-gray-200 bg-white p-4 transition hover:border-blue-400 hover:bg-blue-50">
                                    <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                                        <i class="ik ik-message-square text-blue-600 text-xl"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-semibold text-gray-800 text-sm">SMS Verification</p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            Codes sent to {{ $user->phone_number }}
                                        </p>
                                        <span class="inline-block mt-2 text-xs font-medium text-blue-600 group-hover:underline">
                                            Set up →
                                        </span>
                                    </div>
                                </a>
                            @else
                                <div class="flex items-start gap-3 rounded-lg border-2 border-dashed border-gray-200 bg-gray-50 p-4 opacity-60 cursor-not-allowed">
                                    <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-gray-200 flex items-center justify-center">
                                        <i class="ik ik-message-square text-gray-400 text-xl"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-semibold text-gray-500 text-sm">SMS Verification</p>
                                        <p class="text-xs text-gray-400 mt-1">
                                            Add a phone number first to enable
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="rounded-lg bg-blue-50 border border-blue-200 p-3">
                            <p class="text-xs text-blue-700">
                                <i class="ik ik-info mr-1"></i>
                                <strong>Recommended:</strong> Use an authenticator app for the strongest protection. SMS is convenient but less secure.
                            </p>
                        </div>
                    </div>
                @endif
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
        </div>
    </div>
</div>

{{-- ============================================ --}}
{{-- DISABLE 2FA CONFIRMATION MODAL                --}}
{{-- ============================================ --}}
@if($user->hasTwoFactorEnabled())
<div id="disable2faModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-md rounded-xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
            <h3 class="text-base font-semibold text-gray-800">
                <i class="ik ik-alert-triangle mr-2 text-red-600"></i>
                Disable Two-Factor Authentication
            </h3>
            <button type="button"
                    onclick="document.getElementById('disable2faModal').classList.add('hidden'); document.getElementById('disable2faModal').classList.remove('flex');"
                    class="text-gray-400 hover:text-gray-600">
                <i class="ik ik-x"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('2fa.disable') }}">
            @csrf
            <div class="px-5 py-4 text-sm">
                <div class="rounded-lg border border-red-200 bg-red-50 p-3 mb-4">
                    <p class="text-red-700 text-xs">
                        <i class="ik ik-alert-circle mr-1"></i>
                        <strong>Warning:</strong> Disabling 2FA will make your account less secure. You will only need your password to sign in.
                    </p>
                </div>

                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Confirm your password to continue
                </label>
                <input type="password"
                       name="password"
                       required
                       autocomplete="current-password"
                       class="w-full rounded-lg border border-gray-200 px-4 py-2 focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-200 px-5 py-4">
                <button type="button"
                        onclick="document.getElementById('disable2faModal').classList.add('hidden'); document.getElementById('disable2faModal').classList.remove('flex');"
                        class="rounded-lg bg-gray-200 px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300">
                    Cancel
                </button>
                <button type="submit"
                        class="rounded-lg bg-red-500 px-5 py-2 text-sm font-medium text-white hover:bg-red-600">
                    <i class="ik ik-x-circle"></i> Disable 2FA
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
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