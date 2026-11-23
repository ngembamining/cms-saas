@extends('layouts.main')
@section('title', 'Email Settings')
@section('content')

<div class="max-w-3xl mx-auto">
    <x-page-header title="{{ __('Email Settings') }}" 
                   subtitle="{{ __('Configure your personal email account') }}" 
                   icon="ik ik-settings"
                   :breadcrumbs="['Home' => route('dashboard'), 'Webmail' => route('email.index'), 'Settings' => null]" />

    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-lg mb-6">
            <i class="ik ik-info mr-2"></i>
            {{ __('These settings are for your personal email account. System notifications (2FA, alerts) use the system email configuration.') }}
        </div>

        <form action="{{ route('email.settings') }}" method="POST" id="email-settings-form">
            @csrf

            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Your Email Address') }}</label>
                    <input type="email" value="{{ $user->email }}" disabled
                           class="w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 text-gray-500">
                    <p class="text-xs text-gray-400 mt-1">{{ __('This is your account email address used as sender') }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Email Password') }}</label>
                    <input type="password" name="email_password" 
                           placeholder="Enter your email password"
                           class="w-full rounded-lg border border-gray-200 px-4 py-2 focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    <p class="text-xs text-gray-400 mt-1">{{ __('Your email password is encrypted and stored securely. Required for sending emails.') }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('SMTP Host') }}</label>
                        <input type="text" name="email_smtp_host" value="{{ old('email_smtp_host', $user->email_smtp_host ?? 'smtp.gmail.com') }}"
                               placeholder="smtp.gmail.com"
                               class="w-full rounded-lg border border-gray-200 px-4 py-2 focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        <p class="text-xs text-gray-400 mt-1">{{ __('For Gmail: smtp.gmail.com, Outlook: smtp-mail.outlook.com, Your domain: mail.yourdomain.com') }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('SMTP Port') }}</label>
                        <input type="number" name="email_smtp_port" value="{{ old('email_smtp_port', $user->email_smtp_port ?? 587) }}"
                               placeholder="587"
                               class="w-full rounded-lg border border-gray-200 px-4 py-2 focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        <p class="text-xs text-gray-400 mt-1">{{ __('Gmail/Outlook: 587 (TLS) or 465 (SSL)') }}</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Encryption') }}</label>
                    <select name="email_encryption" class="w-full rounded-lg border border-gray-200 px-4 py-2 focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        <option value="tls" {{ ($user->email_encryption ?? 'tls') == 'tls' ? 'selected' : '' }}>TLS (Recommended)</option>
                        <option value="ssl" {{ ($user->email_encryption ?? 'tls') == 'ssl' ? 'selected' : '' }}>SSL</option>
                    </select>
                </div>

                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <h5 class="text-sm font-semibold text-gray-700 mb-2">{{ __('Common Email Settings') }}</h5>
                    <div class="grid grid-cols-2 gap-2 text-xs text-gray-600">
                        <div><span class="font-medium">Gmail:</span> smtp.gmail.com:587 (TLS)</div>
                        <div><span class="font-medium">Outlook:</span> smtp-mail.outlook.com:587 (TLS)</div>
                        <div><span class="font-medium">Yahoo:</span> smtp.mail.yahoo.com:587 (TLS)</div>
                        <div><span class="font-medium">Custom Domain:</span> mail.yourdomain.com:465 (SSL)</div>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-4">
                    <button type="submit" class="px-6 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition">
                        <i class="ik ik-save mr-2"></i> {{ __('Save Settings') }}
                    </button>
                    <button type="button" id="test-connection" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                        <i class="ik ik-refresh-cw mr-2"></i> {{ __('Test Connection') }}
                    </button>
                    <a href="{{ route('email.index') }}" class="px-6 py-2 text-gray-600 hover:text-gray-800 transition">
                        {{ __('Back to Inbox') }}
                    </a>
                </div>

                <div id="connection-result" class="mt-4 hidden"></div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const testBtn = document.getElementById('test-connection');
    const resultDiv = document.getElementById('connection-result');
    const form = document.getElementById('email-settings-form');

    if (testBtn) {
        testBtn.addEventListener('click', function() {
            const formData = new FormData(form);
            
            resultDiv.className = 'mt-4 px-4 py-3 rounded-lg bg-blue-50 text-blue-700 border border-blue-200';
            resultDiv.textContent = 'Testing connection...';
            resultDiv.classList.remove('hidden');

            fetch('{{ route("email.test") }}', {
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
                    resultDiv.className = 'mt-4 px-4 py-3 rounded-lg bg-green-50 text-green-700 border border-green-200';
                    resultDiv.textContent = '✅ ' + data.message;
                } else {
                    resultDiv.className = 'mt-4 px-4 py-3 rounded-lg bg-red-50 text-red-700 border border-red-200';
                    resultDiv.textContent = '❌ ' + data.message;
                }
                resultDiv.classList.remove('hidden');
            })
            .catch(error => {
                resultDiv.className = 'mt-4 px-4 py-3 rounded-lg bg-red-50 text-red-700 border border-red-200';
                resultDiv.textContent = '❌ Connection test failed: ' + error.message;
                resultDiv.classList.remove('hidden');
            });
        });
    }
});
</script>

@endsection