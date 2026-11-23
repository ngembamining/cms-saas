@extends('layouts.main')
@section('title', 'HR Reports')
@section('content')

<div class="max-w-7xl mx-auto">
    <x-page-header title="{{ __('HR Reports') }}" 
                   subtitle="{{ __('HR analytics and reports') }}" 
                   icon="ik ik-pie-chart"
                   :breadcrumbs="['Home' => route('dashboard'), 'HR' => route('hr.index'), 'Reports' => null]" />

    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="text-center py-12">
            <i class="ik ik-pie-chart text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-700">{{ __('HR Reports') }}</h3>
            <p class="text-gray-400 mt-2">{{ __('View HR analytics, staffing reports, and employee metrics.') }}</p>
            <p class="text-gray-400 text-sm mt-1">{{ __('Coming soon: Staffing reports, turnover analysis, and headcount tracking.') }}</p>
        </div>
    </div>
</div>

@endsection