@extends('layouts.main')

@section('title', 'Create Budget')

@section('content')
<x-page-header title="Create Budget" subtitle="Set a new budget" icon="ik ik-plus"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Expenses' => route('finance.expenses.index'), 'Budgets' => route('finance.expenses.budgets.index'), 'Create' => null]" />

<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="text-center py-12">
        <i class="ik ik-pie-chart text-6xl text-gray-300 mb-4 block"></i>
        <h3 class="text-lg font-medium text-gray-600">Create Budget</h3>
        <p class="text-gray-400 mt-2">Budget creation form coming soon.</p>
    </div>
</div>
@endsection