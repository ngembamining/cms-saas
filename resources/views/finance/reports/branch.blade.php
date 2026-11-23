@extends('layouts.main')

@section('title', 'Branch Report')

@section('content')
<x-page-header title="Branch Report" subtitle="View branch performance" icon="fas fa-store"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Reports' => route('finance.reports.index'), 'Branch' => null]" />

<div class="max-w-6xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 font-medium">Branch</th>
                        <th class="px-5 py-3 font-medium">Cash (TZS)</th>
                        <th class="px-5 py-3 font-medium">Gold (g)</th>
                        <th class="px-5 py-3 font-medium">Transactions</th>
                        <th class="px-5 py-3 font-medium">Sales (TZS)</th>
                        <th class="px-5 py-3 font-medium">Purchases (TZS)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($branchData as $data)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-semibold text-gray-800">{{ $data['branch']->name }}</td>
                        <td class="px-5 py-3 text-green-600">{{ number_format($data['cash'], 0) }}</td>
                        <td class="px-5 py-3 text-amber-600">{{ number_format($data['gold'], 2) }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ $data['transactions'] }}</td>
                        <td class="px-5 py-3 text-green-600">{{ number_format($data['sales'], 0) }}</td>
                        <td class="px-5 py-3 text-red-600">{{ number_format($data['purchases'], 0) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-gray-400">No branch data available</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection