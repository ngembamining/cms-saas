@extends('layouts.main')

@section('title', 'Budget Management')

@section('content')
<x-page-header title="Budget Management" subtitle="Manage expense budgets" icon="ik ik-pie-chart"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Expenses' => route('finance.expenses.index'), 'Budgets' => null]">
    <div class="flex gap-2">
        <a href="{{ route('finance.expenses.budgets.create') }}" class="btn btn-primary">
            <i class="ik ik-plus"></i> New Budget
        </a>
    </div>
</x-page-header>

<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="text-center py-12">
        <i class="ik ik-pie-chart text-6xl text-gray-300 mb-4 block"></i>
        <h3 class="text-lg font-medium text-gray-600">Budget Management</h3>
        <p class="text-gray-400 mt-2">Budget management features coming soon.</p>
        <p class="text-gray-400 text-sm">You can set budgets per branch and category.</p>
    </div>
</div>
@endsection