@extends('layouts.main')
@section('title', 'Broadcast Notification')

@section('content')
<div class="max-w-3xl mx-auto">
    <x-page-header title="{{ __('Broadcast Notification') }}"
                   subtitle="{{ __('Send an in-app message to multiple users') }}"
                   icon="fas fa-bullhorn"
                   :breadcrumbs="['Home' => route('dashboard'), 'Communication' => null, 'Notifications' => route('communication.notifications.index'), 'Broadcast' => null]" />

    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('communication.notifications.broadcast.store') }}"
          class="bg-white rounded-xl shadow-sm p-6 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Message') }}</label>
            <textarea name="message" rows="5" maxlength="500"
                      class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                      placeholder="{{ __('Type your message (max 500 characters)...') }}"
                      required>{{ old('message') }}</textarea>
            <p class="mt-1 text-xs text-gray-400">{{ __('Max 500 characters') }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Recipients') }}</label>
            <select name="recipient_type" id="recipient_type"
                    class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                    required>
                <option value="all"      {{ old('recipient_type') === 'all'      ? 'selected' : '' }}>All Users</option>
                <option value="managers" {{ old('recipient_type') === 'managers' ? 'selected' : '' }}>Branch Managers</option>
                <option value="staff"    {{ old('recipient_type') === 'staff'    ? 'selected' : '' }}>Staff</option>
                <option value="branch"   {{ old('recipient_type') === 'branch'   ? 'selected' : '' }}>Specific Branch</option>
            </select>
        </div>

        <div id="branch_wrap" class="hidden">
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Branch') }}</label>
            <select name="branch_id" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                <option value="">— {{ __('Select a branch') }} —</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ (string) old('branch_id') === (string) $b->id ? 'selected' : '' }}>
                        {{ $b->name }} — {{ $b->location }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('communication.notifications.index') }}"
               class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                {{ __('Cancel') }}
            </a>
            <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition">
                <i class="fas fa-paper-plane mr-2"></i> {{ __('Broadcast') }}
            </button>
        </div>
    </form>
</div>

<script>
    const rt = document.getElementById('recipient_type');
    const bw = document.getElementById('branch_wrap');
    function toggleBranch() {
        bw.classList.toggle('hidden', rt.value !== 'branch');
    }
    rt.addEventListener('change', toggleBranch);
    toggleBranch();
</script>
@endsection