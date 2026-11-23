@extends('layouts.main')

@section('title', 'Reconciliation Reports')

@section('content')
<x-page-header title="Reconciliation Reports" subtitle="View reconciliation reports" icon="ik ik-pie-chart"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Reconciliations' => route('finance.reconciliations.index'), 'Reports' => null]" />

<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="text-center py-12">
        <i class="ik ik-pie-chart text-6xl text-gray-300 mb-4 block"></i>
        <h3 class="text-lg font-medium text-gray-600">Reconciliation Reports</h3>
        <p class="text-gray-400 mt-2">Reports and analytics coming soon.</p>
    </div>
</div>
@endsection