@extends('layouts.main')

@section('title', 'SMS Management')

@section('content')
<x-page-header title="SMS Management" subtitle="Send and manage SMS messages" icon="fas fa-sms"
    :breadcrumbs="['Home' => url('dashboard'), 'Communication' => route('communication.sms.index'), 'SMS' => null]">
    <div class="flex gap-2">
        <a href="{{ route('communication.sms.send') }}" class="btn btn-primary">
            <i class="ik ik-plus"></i> Send SMS
        </a>
        <a href="{{ route('communication.sms.balance.refresh') }}" class="btn btn-secondary">
            <i class="ik ik-refresh-ccw"></i> Refresh Balance
        </a>
    </div>
</x-page-header>

{{-- Stats source notice --}}
@php
    $source     = $statsSource ?? 'local';
    $windowLbl  = 'last 30 days';
@endphp

@if($source === 'kingosms')
    <div class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-4 py-2 flex items-center gap-2">
        <i class="fas fa-satellite-dish"></i>
        <span>Statistics are <strong>live from KingoSMS</strong> ({{ $windowLbl }}).</span>
    </div>
@else
    <div class="mb-4 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-4 py-2 flex items-center gap-2">
        <i class="fas fa-exclamation-triangle"></i>
        <span>
            Showing <strong>local logs only</strong>. KingoSMS stats unavailable
            @if(!empty($statsError)) — <em>{{ $statsError }}</em> @endif
        </span>
    </div>
@endif

<!-- Statistics -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4 mb-6">
    <x-stat-card value="{{ number_format($totalSent) }}"
                 label="Total SMS"
                 icon="fas fa-sms"
                 color="primary" />

    <x-stat-card value="{{ number_format($totalSuccess) }}"
                 label="Delivered"
                 icon="ik ik-check-circle"
                 color="green" />

    <x-stat-card value="{{ number_format($totalFailed) }}"
                 label="Failed"
                 icon="ik ik-x-circle"
                 color="danger" />

    <x-stat-card value="{{ number_format($creditBalance ?? 0) }}"
                 label="Credits Available"
                 icon="ik ik-dollar-sign"
                 color="amber" />
</div>

<!-- SMS Balance Alert -->
@if(($balanceStatus ?? null) === 'error')
<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
    <div class="flex items-center">
        <i class="ik ik-alert-triangle text-yellow-600 mr-2"></i>
        <span class="text-sm text-yellow-700">
            ⚠️ {{ $balanceMessage ?? 'Could not connect to SMS API' }}
            <a href="{{ route('communication.sms.balance.refresh') }}" class="ml-2 text-primary-600 hover:underline">
                Retry
            </a>
        </span>
    </div>
</div>
@endif

<!-- Recent SMS Logs -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-800">Recent SMS Logs</h3>
        <a href="{{ route('communication.sms.logs') ?? '#' }}" class="text-sm text-primary-600 hover:underline">
            View all
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 font-medium">Phone</th>
                    <th class="px-5 py-3 font-medium">Message</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Type</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($logs as $log)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-medium text-gray-700">{{ $log->phone ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ Str::limit($log->message ?? '', 30) }}</td>
                    <td class="px-5 py-3">
                        @php $st = strtolower($log->status ?? 'pending'); @endphp
                        @if(in_array($st, ['sent', 'delivered', 'success']))
                            <span class="badge badge-success">{{ ucfirst($st) }}</span>
                        @elseif(in_array($st, ['failed', 'undelivered', 'rejected', 'expired']))
                            <span class="badge badge-danger">{{ ucfirst($st) }}</span>
                        @else
                            <span class="badge badge-amber">{{ ucfirst($st) }}</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-700">{{ $log->event_type ?? 'N/A' }}</td>
                    <td class="px-5 py-3 text-gray-500">
                        @if(!empty($log->sent_at))
                            {{ \Carbon\Carbon::parse($log->sent_at)->format('d/m/Y H:i') }}
                        @else
                            N/A
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-5 py-8 text-center text-gray-400">
                        <i class="fas fa-sms text-3xl block mb-2"></i>
                        No SMS logs found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs instanceof \Illuminate\Pagination\LengthAwarePaginator && $logs->hasPages())
        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection