@extends('layouts.main')

@section('title', 'Trial Balance')

@section('content')
<x-page-header title="Trial Balance" subtitle="View trial balance reports" icon="ik ik-file-text"
    :breadcrumbs="['Home' => url('dashboard'), 'Finance' => route('finance.balances.index'), 'Trial Balance' => null]">
    <div class="flex gap-2 no-print">
        <a href="{{ route('finance.trial-balance.export', request()->all()) }}" class="btn btn-success">
            <i class="ik ik-download"></i> Export CSV
        </a>
        <a href="{{ route('finance.trial-balance.export-excel', request()->all()) }}" class="btn btn-success" style="background-color: #217346;">
            <i class="ik ik-file-text"></i> Export Excel
        </a>
        <a href="{{ route('finance.trial-balance.export-pdf', request()->all()) }}" class="btn btn-danger" target="_blank">
            <i class="ik ik-file-text"></i> Export PDF
        </a>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="ik ik-printer"></i> Print
        </button>
    </div>
</x-page-header>

<!-- Filter Section -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6 no-print">
    <form action="{{ route('finance.trial-balance.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Start Date</label>
            <input type="date" name="start_date" value="{{ request('start_date', $period['start_date'] ?? now()->startOfMonth()->format('Y-m-d')) }}" 
                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">End Date</label>
            <input type="date" name="end_date" value="{{ request('end_date', $period['end_date'] ?? now()->format('Y-m-d')) }}" 
                   class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Branch</label>
            <select name="branch_id" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                <option value="">All Branches</option>
                @foreach($branches ?? [] as $branch)
                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                <i class="ik ik-search mr-2"></i> Generate
            </button>
            <a href="{{ route('finance.trial-balance.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <i class="ik ik-rotate-ccw"></i>
            </a>
        </div>
    </form>
</div>

<!-- Trial Balance Summary -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h4 class="text-lg font-semibold text-gray-800">
            <i class="ik ik-bar-chart-2 mr-2 text-primary-600"></i> Trial Balance Summary
        </h4>
        <span class="text-sm text-gray-500">
            Generated: {{ $generated_at ?? now()->format('d/m/Y H:i') }}
            @if(isset($generated_by))
                by {{ $generated_by }}
            @endif
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Opening Gold Stock - This is the balance -->
        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 p-4 rounded-lg border border-yellow-200">
            <p class="text-sm text-yellow-700">Opening Gold Stock <span class="text-xs font-normal">(Balance)</span></p>
            <p class="text-2xl font-bold text-yellow-800">{{ number_format($totals['gold_stock']['opening_stock'] ?? 0, 2) }} g</p>
            <p class="text-xs text-yellow-600 mt-1">
                Value: TZS {{ number_format($totals['gold_stock']['opening_value'] ?? 0, 0) }}
            </p>
        </div>

        <!-- Total Cash -->
        <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg border border-green-200">
            <p class="text-sm text-green-700">Total Cash Balance</p>
            <p class="text-2xl font-bold text-green-800">TZS {{ number_format($totals['cash']['closing'] ?? 0, 0) }}</p>
            <p class="text-xs text-green-600 mt-1">
                Opening: TZS {{ number_format($totals['cash']['opening'] ?? 0, 0) }}
            </p>
        </div>

        <!-- Total Assets -->
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg border border-blue-200">
            <p class="text-sm text-blue-700">Total Assets</p>
            <p class="text-2xl font-bold text-blue-800">TZS {{ number_format($totals['summary']['total_assets'] ?? 0, 0) }}</p>
            <p class="text-xs text-blue-600 mt-1">
                Equity: TZS {{ number_format($totals['summary']['total_equity'] ?? 0, 0) }}
            </p>
        </div>

        <!-- Total Loans -->
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-4 rounded-lg border border-purple-200">
            <p class="text-sm text-purple-700">Loans Outstanding</p>
            <p class="text-2xl font-bold text-purple-800">TZS {{ number_format($totals['loans']['total_outstanding'] ?? 0, 0) }}</p>
            <p class="text-xs text-purple-600 mt-1">
                Issued: TZS {{ number_format($totals['loans']['total_issued'] ?? 0, 0) }}
            </p>
        </div>
    </div>
</div>

<!-- Detailed Trial Balance -->
@if(isset($data) && count($data) > 0)
    @foreach($data as $branchId => $branch)
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-semibold text-gray-800">
                    <i class="ik ik-home mr-2 text-primary-600"></i> {{ $branch['branch_name'] }}
                </h4>
                <span class="text-sm text-gray-500">
                    {{ \Carbon\Carbon::parse($branch['period']['start_date'])->format('d/m/Y') }} to {{ \Carbon\Carbon::parse($branch['period']['end_date'])->format('d/m/Y') }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-5 py-3 font-medium">#</th>
                            <th class="px-5 py-3 font-medium">Details</th>
                            <th class="px-5 py-3 font-medium">Folio</th>
                            <th class="px-5 py-3 font-medium text-right">DR (TZS)</th>
                            <th class="px-5 py-3 font-medium text-right">CR (TZS)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @php $rowNumber = 1; @endphp
                        
                        <!-- Opening Gold Stock -->
                        <tr class="bg-yellow-50">
                            <td class="px-5 py-3">{{ $rowNumber++ }}</td>
                            <td class="px-5 py-3 font-semibold">Opening Gold Stock</td>
                            <td class="px-5 py-3">GL</td>
                            <td class="px-5 py-3 text-right font-semibold">{{ number_format($branch['gold_stock']['opening_value'] ?? 0, 0) }}</td>
                            <td class="px-5 py-3 text-right">-</td>
                        </tr>

                        <!-- Cash -->
                        <tr>
                            <td class="px-5 py-3">{{ $rowNumber++ }}</td>
                            <td class="px-5 py-3">Cash</td>
                            <td class="px-5 py-3">GL</td>
                            <td class="px-5 py-3 text-right">{{ number_format($branch['cash']['closing'] ?? 0, 0) }}</td>
                            <td class="px-5 py-3 text-right">-</td>
                        </tr>

                        <!-- Sales -->
                        <tr>
                            <td class="px-5 py-3">{{ $rowNumber++ }}</td>
                            <td class="px-5 py-3">Sales</td>
                            <td class="px-5 py-3">SL</td>
                            <td class="px-5 py-3 text-right">-</td>
                            <td class="px-5 py-3 text-right">{{ number_format($branch['transactions']['sales_value'] ?? 0, 0) }}</td>
                        </tr>

                        <!-- Purchases -->
                        <tr>
                            <td class="px-5 py-3">{{ $rowNumber++ }}</td>
                            <td class="px-5 py-3">Purchases</td>
                            <td class="px-5 py-3">PL</td>
                            <td class="px-5 py-3 text-right">{{ number_format($branch['transactions']['purchases_value'] ?? 0, 0) }}</td>
                            <td class="px-5 py-3 text-right">-</td>
                        </tr>

                        <!-- Loans Issued -->
                        <tr>
                            <td class="px-5 py-3">{{ $rowNumber++ }}</td>
                            <td class="px-5 py-3">Loans Issued</td>
                            <td class="px-5 py-3">GL</td>
                            <td class="px-5 py-3 text-right">{{ number_format($branch['loans']['total_issued'] ?? 0, 0) }}</td>
                            <td class="px-5 py-3 text-right">-</td>
                        </tr>

                        <!-- Loans Repaid -->
                        <tr>
                            <td class="px-5 py-3">{{ $rowNumber++ }}</td>
                            <td class="px-5 py-3">Loans Repaid</td>
                            <td class="px-5 py-3">GL</td>
                            <td class="px-5 py-3 text-right">-</td>
                            <td class="px-5 py-3 text-right">{{ number_format($branch['loans']['total_repaid'] ?? 0, 0) }}</td>
                        </tr>

                        <!-- Accounts Receivable -->
                        <tr>
                            <td class="px-5 py-3">{{ $rowNumber++ }}</td>
                            <td class="px-5 py-3">Accounts Receivable</td>
                            <td class="px-5 py-3">SL</td>
                            <td class="px-5 py-3 text-right">{{ number_format($branch['customers']['total_outstanding'] ?? 0, 0) }}</td>
                            <td class="px-5 py-3 text-right">-</td>
                        </tr>

                        <!-- Accounts Payable -->
                        <tr>
                            <td class="px-5 py-3">{{ $rowNumber++ }}</td>
                            <td class="px-5 py-3">Accounts Payable</td>
                            <td class="px-5 py-3">PL</td>
                            <td class="px-5 py-3 text-right">-</td>
                            <td class="px-5 py-3 text-right">{{ number_format($branch['suppliers']['total_outstanding'] ?? 0, 0) }}</td>
                        </tr>

                        <!-- Expenses -->
                        <tr>
                            <td class="px-5 py-3">{{ $rowNumber++ }}</td>
                            <td class="px-5 py-3">Expenses</td>
                            <td class="px-5 py-3">GL</td>
                            <td class="px-5 py-3 text-right">{{ number_format($branch['expenses']['total_expenses'] ?? 0, 0) }}</td>
                            <td class="px-5 py-3 text-right">-</td>
                        </tr>

                        <!-- Total Row -->
                        <tr class="bg-gray-100 font-bold">
                            <td class="px-5 py-3" colspan="3">TOTAL</td>
                            <td class="px-5 py-3 text-right">{{ number_format($branch['summary']['total_debit'] ?? 0, 0) }}</td>
                            <td class="px-5 py-3 text-right">{{ number_format($branch['summary']['total_credit'] ?? 0, 0) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    <!-- Grand Totals -->
    @if(count($data) > 1)
        <div class="bg-white rounded-xl shadow-sm p-6 border-2 border-primary-200">
            <h4 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="ik ik-award mr-2 text-primary-600"></i> Grand Totals (All Branches)
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                    <p class="text-sm text-yellow-700">Opening Gold Stock <span class="text-xs">(Balance)</span></p>
                    <p class="text-xl font-bold text-yellow-800">{{ number_format($totals['gold_stock']['opening_stock'] ?? 0, 2) }} g</p>
                    <p class="text-xs text-yellow-600">TZS {{ number_format($totals['gold_stock']['opening_value'] ?? 0, 0) }}</p>
                </div>
                <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                    <p class="text-sm text-green-700">Total Cash</p>
                    <p class="text-xl font-bold text-green-800">TZS {{ number_format($totals['cash']['closing'] ?? 0, 0) }}</p>
                </div>
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                    <p class="text-sm text-blue-700">Total Assets</p>
                    <p class="text-xl font-bold text-blue-800">TZS {{ number_format($totals['summary']['total_assets'] ?? 0, 0) }}</p>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                    <p class="text-sm text-purple-700">Total Equity</p>
                    <p class="text-xl font-bold text-purple-800">TZS {{ number_format($totals['summary']['total_equity'] ?? 0, 0) }}</p>
                </div>
            </div>
        </div>
    @endif

@else
    <!-- No Data -->
    <div class="bg-white rounded-xl shadow-sm p-12 text-center">
        <i class="ik ik-file-text text-6xl text-gray-300 block mb-4"></i>
        <h3 class="text-lg font-medium text-gray-600">No Trial Balance Data</h3>
        <p class="text-gray-400 mt-2">Select a date range and branch to generate the trial balance.</p>
    </div>
@endif

<!-- Print Styles -->
<style>
    @media print {
        .no-print {
            display: none !important;
        }
        .bg-white {
            background: white !important;
            box-shadow: none !important;
            border: 1px solid #e5e7eb !important;
        }
        .shadow-sm {
            box-shadow: none !important;
        }
        .mb-6 {
            margin-bottom: 1.5rem !important;
        }
        .mb-4 {
            margin-bottom: 1rem !important;
        }
        .p-6 {
            padding: 1.5rem !important;
        }
        .p-4 {
            padding: 1rem !important;
        }
        .grid {
            display: grid !important;
        }
        .page-break {
            page-break-after: always;
        }
        .bg-yellow-50 { background: #fefce8 !important; }
        .bg-green-50 { background: #f0fdf4 !important; }
        .bg-blue-50 { background: #eff6ff !important; }
        .bg-purple-50 { background: #faf5ff !important; }
        .bg-primary-50 { background: #eff6ff !important; }
        .bg-gray-100 { background: #f3f4f6 !important; }
        .text-yellow-800 { color: #92400e !important; }
        .text-green-800 { color: #065f46 !important; }
        .text-blue-800 { color: #1e40af !important; }
        .text-purple-800 { color: #5b21b6 !important; }
    }
</style>

<script>
    // Auto-submit form when dates change
    document.querySelectorAll('input[type="date"], select[name="branch_id"]').forEach(function(element) {
        element.addEventListener('change', function() {
            this.closest('form').submit();
        });
    });
</script>
@endsection