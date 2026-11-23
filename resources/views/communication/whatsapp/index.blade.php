<div>
    <!-- Well begun is half done. - Aristotle -->
</div>
@extends('layouts.main')

@section('title', 'WhatsApp Management')

@section('content')
<x-page-header title="WhatsApp Management" subtitle="Send and manage WhatsApp messages" icon="fab fa-whatsapp"
    :breadcrumbs="['Home' => url('dashboard'), 'Communication' => route('communication.whatsapp.index'), 'WhatsApp' => null]">
    <div class="flex gap-2">
        <a href="{{ route('communication.whatsapp.send') }}" class="btn btn-primary">
            <i class="ik ik-plus"></i> Send WhatsApp
        </a>
        <a href="{{ route('communication.whatsapp.status.refresh') }}" class="btn btn-secondary">
            <i class="ik ik-refresh-ccw"></i> Refresh Status
        </a>
    </div>
</x-page-header>

<!-- Statistics -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4 mb-6">
    <x-stat-card value="{{ number_format($totalSent) }}" 
                 label="Total Messages" 
                 icon="fab fa-whatsapp" 
                 color="primary" />
    
    <x-stat-card value="{{ number_format($totalDelivered) }}" 
                 label="Delivered" 
                 icon="ik ik-check-circle" 
                 color="green" />
    
    <x-stat-card value="{{ number_format($totalRead) }}" 
                 label="Read" 
                 icon="ik ik-eye" 
                 color="blue" />
    
    <x-stat-card value="{{ number_format($totalFailed) }}" 
                 label="Failed" 
                 icon="ik ik-x-circle" 
                 color="danger" />
</div>

<!-- API Status Alert -->
@if($apiStatus === 'error')
<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
    <div class="flex items-center">
        <i class="ik ik-alert-triangle text-yellow-600 mr-2"></i>
        <span class="text-sm text-yellow-700">
            ⚠️ {{ $apiMessage ?? 'Could not connect to WhatsApp API' }}
            <a href="{{ route('communication.whatsapp.status.refresh') }}" class="ml-2 text-primary-600 hover:underline">
                Retry
            </a>
        </span>
    </div>
</div>
@endif

<!-- Recent WhatsApp Logs -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent WhatsApp Logs</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 font-medium">Phone</th>
                    <th class="px-5 py-3 font-medium">Message</th>
                    <th class="px-5 py-3 font-medium">Type</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($logs as $log)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-medium text-gray-700">{{ $log->phone }}</td>
                    <td class="px-5 py-3 text-gray-700">
                        @if($log->message)
                            {{ Str::limit($log->message, 30) }}
                        @else
                            <span class="text-gray-400 italic">[template: {{ $log->template_name ?? 'N/A' }}]</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-700">
                        <span class="badge badge-info">{{ ucfirst($log->message_type ?? 'text') }}</span>
                    </td>
                    <td class="px-5 py-3">
                        @if($log->status == 'sent' || $log->status == 'delivered')
                            <span class="badge badge-success">{{ ucfirst($log->status) }}</span>
                        @elseif($log->status == 'read')
                            <span class="badge badge-info">Read</span>
                        @elseif($log->status == 'failed')
                            <span class="badge badge-danger">Failed</span>
                        @else
                            <span class="badge badge-amber">{{ ucfirst($log->status ?? 'pending') }}</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-gray-500">
                        @if($log->sent_at)
                            {{ \Carbon\Carbon::parse($log->sent_at)->format('d/m/Y H:i') }}
                        @else
                            {{ $log->created_at?->format('d/m/Y H:i') ?? 'N/A' }}
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-5 py-8 text-center text-gray-400">
                        <i class="fab fa-whatsapp text-3xl block mb-2"></i>
                        No WhatsApp logs found.
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