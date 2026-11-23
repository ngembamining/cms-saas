@extends('layouts.main')
@section('title', 'SMS Logs')
@section('content')

<div class="max-w-7xl mx-auto">
    <x-page-header title="{{ __('SMS Logs') }}"
                   subtitle="{{ __('View all sent SMS messages') }}"
                   icon="fas fa-sms"
                   :breadcrumbs="['Home' => route('dashboard'), 'Communication' => null, 'SMS' => route('communication.sms.index'), 'Logs' => null]" />

    {{-- API error banner --}}
    @if(isset($apiOk) && !$apiOk)
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            Could not load live SMS logs from KingoSMS
            @if(!empty($apiError)) — <em>{{ $apiError }}</em> @endif
        </div>
    @endif

    {{-- Active window hint --}}
    <div class="mb-4 text-xs text-gray-500">
        Showing results between
        <strong>{{ \Carbon\Carbon::parse($from)->format('d/m/Y') }}</strong>
        and
        <strong>{{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</strong>.
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card value="{{ number_format($totalMessages ?? 0) }}"
                     label="{{ __('Total Messages') }}"
                     icon="fas fa-sms"
                     color="primary" />

        <x-stat-card value="{{ number_format($sentCount ?? 0) }}"
                     label="{{ __('Sent / Delivered') }}"
                     icon="ik ik-check-circle"
                     color="green" />

        <x-stat-card value="{{ number_format($pendingCount ?? 0) }}"
                     label="{{ __('Pending') }}"
                     icon="ik ik-clock"
                     color="amber" />

        <x-stat-card value="{{ number_format($failedCount ?? 0) }}"
                     label="{{ __('Failed') }}"
                     icon="ik ik-x-circle"
                     color="red" />
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4 mt-6">
        <form action="{{ route('communication.sms.logs') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('From Date') }}</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}"
                       class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('To Date') }}</label>
                <input type="date" name="to_date" value="{{ request('to_date') }}"
                       class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Phone') }}</label>
                <input type="text" name="phone" value="{{ request('phone') }}" placeholder="255..."
                       class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Status') }}</label>
                <select name="status" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    <option value="">{{ __('All Status') }}</option>
                    <option value="sent"    {{ request('status') === 'sent'    ? 'selected' : '' }}>{{ __('Sent / Delivered') }}</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                    <option value="failed"  {{ request('status') === 'failed'  ? 'selected' : '' }}>{{ __('Failed') }}</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Type') }}</label>
                <select name="sms_type" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    <option value="">{{ __('All Types') }}</option>
                    <option value="plain"    {{ request('sms_type') === 'plain'    ? 'selected' : '' }}>Plain</option>
                    <option value="unicode"  {{ request('sms_type') === 'unicode'  ? 'selected' : '' }}>Unicode</option>
                    <option value="voice"    {{ request('sms_type') === 'voice'    ? 'selected' : '' }}>Voice</option>
                    <option value="otp"      {{ request('sms_type') === 'otp'      ? 'selected' : '' }}>OTP</option>
                    <option value="whatsapp" {{ request('sms_type') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Direction') }}</label>
                <select name="direction" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    <option value="">{{ __('All Directions') }}</option>
                    <option value="outgoing" {{ request('direction') === 'outgoing' ? 'selected' : '' }}>Outgoing</option>
                    <option value="incoming" {{ request('direction') === 'incoming' ? 'selected' : '' }}>Incoming</option>
                    <option value="api"      {{ request('direction') === 'api'      ? 'selected' : '' }}>API</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition">
                <i class="ik ik-search mr-2"></i> {{ __('Apply') }}
            </button>
            <a href="{{ route('communication.sms.logs') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="ik ik-rotate-ccw mr-2"></i> {{ __('Reset') }}
            </a>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden mt-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-6 py-3 font-medium">{{ __('Date') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Sender') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Phone') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Message') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Source') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-gray-500">
                                {{ $log->sent_at ? \Carbon\Carbon::parse($log->sent_at)->format('d/m/Y H:i') : 'N/A' }}
                            </td>
                            <td class="px-6 py-3 text-gray-700">{{ $log->sender_id ?? 'N/A' }}</td>
                            <td class="px-6 py-3 text-gray-700">{{ $log->phone ?? '—' }}</td>
                            <td class="px-6 py-3 text-gray-600 max-w-xs">
                                <span title="{{ $log->message }}">{{ Str::limit($log->message ?? '', 50) }}</span>
                            </td>
                            <td class="px-6 py-3">
                                @php
                                    $st = strtolower($log->status ?? 'pending');
                                    $statusColor = match($st) {
                                        'sent', 'delivered', 'success' => 'success',
                                        'pending'                      => 'warning',
                                        'failed', 'undelivered', 'rejected', 'expired' => 'danger',
                                        default                        => 'secondary'
                                    };
                                @endphp
                                <x-badge color="{{ $statusColor }}">{{ ucfirst($st) }}</x-badge>
                            </td>
                            <td class="px-6 py-3 text-gray-600">{{ $log->sent_by ?? 'System' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                                <i class="ik ik-inbox text-4xl block mb-2"></i>
                                {{ __('No SMS logs found for this filter') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-gray-100">
            @if($logs instanceof \Illuminate\Pagination\LengthAwarePaginator && $logs->hasPages())
                {{ $logs->links() }}
            @else
                <span class="text-sm text-gray-400">{{ __('Showing all records') }}</span>
            @endif
        </div>
    </div>
</div>

@endsection