@extends('layouts.main')
@section('title', 'Broadcast Notification')
@section('content')

<div class="max-w-3xl mx-auto">
    <x-page-header title="{{ __('Broadcast Notification') }}" 
                   subtitle="{{ __('Send notification to users') }}" 
                   icon="ik ik-send"
                   :breadcrumbs="['Home' => route('dashboard'), 'Communication' => null, 'Notifications' => route('communication.notifications.index'), 'Broadcast' => null]" />

    <div class="bg-white rounded-xl shadow-sm p-6">
        <form action="{{ route('communication.notifications.broadcast') }}" method="POST">
            @csrf

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Message') }}</label>
                    <textarea name="message" rows="4" required
                              placeholder="Enter notification message..."
                              class="w-full rounded-lg border border-gray-200 px-4 py-2 focus:border-primary-400 focus:ring-2 focus:ring-primary-100"></textarea>
                    <p class="text-xs text-gray-400 mt-1">Maximum 500 characters</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Recipients') }}</label>
                    <select name="recipient_type" id="recipient_type" class="w-full rounded-lg border border-gray-200 px-4 py-2 focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        <option value="all">{{ __('All Users') }}</option>
                        <option value="managers">{{ __('Branch Managers') }}</option>
                        <option value="staff">{{ __('Staff') }}</option>
                        <option value="branch">{{ __('Branch Specific') }}</option>
                    </select>
                </div>

                <div id="branch-select" style="display: none;">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Select Branch') }}</label>
                    <select name="branch_id" class="w-full rounded-lg border border-gray-200 px-4 py-2 focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        <option value="">{{ __('Select Branch') }}</option>
                        @foreach($branches ?? [] as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-6 flex gap-3">
                <button type="submit" class="px-6 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition">
                    <i class="ik ik-send mr-2"></i> {{ __('Send Notification') }}
                </button>
                <a href="{{ route('communication.notifications.index') }}" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('recipient_type').addEventListener('change', function() {
    document.getElementById('branch-select').style.display = this.value === 'branch' ? 'block' : 'none';
});
</script>

@endsection