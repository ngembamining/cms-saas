@extends('layouts.main')

@section('title', 'Expense Approvals')

@section('content')
<x-page-header title="Expense Approvals" subtitle="Review and approve expenses" icon="ik ik-check-square"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Expenses' => route('finance.expenses.index'), 'Approvals' => null]" />

<!-- Statistics -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4 mb-6">
    <x-stat-card value="{{ $expenses->total() }}" 
                 label="Pending Approval" 
                 icon="ik ik-clock" 
                 color="amber" />
</div>

<!-- Expenses Table -->
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3 font-medium">#</th>
                    <th class="px-5 py-3 font-medium">Category</th>
                    <th class="px-5 py-3 font-medium">Branch</th>
                    <th class="px-5 py-3 font-medium">Amount</th>
                    <th class="px-5 py-3 font-medium">Description</th>
                    <th class="px-5 py-3 font-medium">Recorded By</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                    <th class="px-5 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($expenses as $expense)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 text-gray-700">#{{ $expense->id }}</td>
                    <td class="px-5 py-3">
                        <span class="bg-gray-100 px-2 py-1 rounded text-xs">{{ $expense->category }}</span>
                    </td>
                    <td class="px-5 py-3 text-gray-700">{{ $expense->branch->name ?? 'N/A' }}</td>
                    <td class="px-5 py-3 font-semibold text-red-600">{{ number_format($expense->amount, 0) }} TZS</td>
                    <td class="px-5 py-3 text-gray-700">{{ Str::limit($expense->description, 30) }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $expense->user->full_name ?? $expense->user->username ?? 'N/A' }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $expense->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-5 py-3">
                        <div class="flex gap-1">
                            <form action="{{ route('finance.expenses.approvals.approve', $expense->id) }}" 
                                  method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-green-500 hover:text-green-600" 
                                        onclick="return confirm('Approve this expense?')">
                                    <i class="ik ik-check-circle"></i>
                                </button>
                            </form>
                            <form action="{{ route('finance.expenses.approvals.reject', $expense->id) }}" 
                                  method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-red-500 hover:text-red-600" 
                                        onclick="return confirm('Reject this expense?')">
                                    <i class="ik ik-x-circle"></i>
                                </button>
                            </form>
                            <form action="{{ route('finance.expenses.approvals.hold', $expense->id) }}" 
                                  method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-amber-500 hover:text-amber-600" 
                                        onclick="return confirm('Put this expense on hold?')">
                                    <i class="ik ik-pause-circle"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-5 py-8 text-center text-gray-400">
                        <i class="ik ik-check-square text-3xl block mb-2"></i>
                        No pending expenses for approval.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $expenses->links() }}
    </div>
</div>
@endsection