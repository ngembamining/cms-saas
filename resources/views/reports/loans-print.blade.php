<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Loans Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; padding: 20px; }
        .header { text-align: center; border-bottom: 3px solid #1a237e; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 22px; color: #1a237e; }
        .header .meta { font-size: 11px; color: #666; margin-top: 5px; }
        .header .filters { font-size: 10px; color: #888; margin-top: 5px; }
        .summary { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-bottom: 15px; }
        .summary-item { background: #f9fafb; padding: 10px; border-radius: 5px; text-align: center; border: 1px solid #e5e7eb; }
        .summary-item .label { font-size: 9px; color: #666; text-transform: uppercase; }
        .summary-item .value { font-size: 14px; font-weight: bold; margin-top: 3px; }
        .summary-row2 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 15px; }
        .summary-row2 .summary-item { background: #f9fafb; padding: 8px; border-radius: 5px; text-align: center; border: 1px solid #e5e7eb; }
        .summary-row2 .summary-item .value { font-size: 12px; }
        .text-red { color: #b91c1c; }
        .text-green { color: #065f46; }
        .text-purple { color: #5b21b6; }
        .text-teal { color: #0d9488; }
        .text-amber { color: #d97706; }
        .text-blue { color: #1a56db; }
        .text-indigo { color: #4338ca; }
        .text-pink { color: #be185d; }
        .table-title { font-size: 13px; font-weight: bold; margin: 15px 0 8px; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th { background: #1a237e; color: white; padding: 6px 8px; text-align: left; }
        td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .bg-gray { background: #f9fafb; }
        .badge { padding: 2px 6px; border-radius: 3px; font-size: 8px; text-transform: uppercase; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #dbeafe; color: #1e40af; }
        .badge-completed { background: #d1fae5; color: #065f46; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        .footer { text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; color: #999; font-size: 10px; }
        .quick-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin: 15px 0; }
        .quick-item { background: #f9fafb; padding: 8px 12px; border-radius: 5px; border: 1px solid #e5e7eb; text-align: center; }
        .quick-item .label { font-size: 9px; color: #666; }
        .quick-item .value { font-size: 13px; font-weight: bold; margin-top: 2px; }
        @media print {
            body { padding: 10px; }
            .no-print { display: none !important; }
            .summary-item, .summary-row2 .summary-item, .quick-item { background: #f9fafb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            th { background: #1a237e !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .badge-pending { background: #fef3c7 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .badge-approved { background: #dbeafe !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .badge-completed { background: #d1fae5 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .badge-rejected { background: #fee2e2 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LOANS REPORT</h1>
        <div class="meta">Generated: {{ now()->format('d/m/Y H:i:s') }} | Ngemba Mining Management System</div>
        <div class="filters">
            Period: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
            @if(request('branch_id'))
                | Branch: {{ \App\Models\Branch::find(request('branch_id'))->name ?? 'N/A' }}
            @endif
            @if(request('status'))
                | Status: {{ ucfirst(request('status')) }}
            @endif
            @if(request('supplier_id'))
                | Supplier: {{ \App\Models\Supplier::find(request('supplier_id'))->name ?? 'N/A' }}
            @endif
        </div>
    </div>

    <!-- Summary Row 1 -->
    <div class="summary">
        <div class="summary-item">
            <div class="label">Total Loans</div>
            <div class="value text-blue">{{ number_format($summary['total_loans'] ?? 0, 0) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Amount Loaned Principal</div>
            <div class="value text-green">TZS {{ number_format($summary['principal_amount'] ?? 0, 0) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Interest Expected</div>
            <div class="value text-purple">TZS {{ number_format($summary['expected_interest'] ?? 0, 0) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Total Paid (Active)</div>
            <div class="value text-teal">TZS {{ number_format($summary['active_payments'] ?? 0, 0) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Interest Collected</div>
            <div class="value text-amber">TZS {{ number_format($summary['interest_collected'] ?? 0, 0) }}</div>
        </div>
    </div>

    <!-- Summary Row 2 -->
    <div class="summary-row2">
        <div class="summary-item">
            <div class="label">Active Loans</div>
            <div class="value text-indigo">{{ number_format($summary['active_loans'] ?? 0, 0) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Completed</div>
            <div class="value text-green">{{ number_format($summary['completed_loans'] ?? 0, 0) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Rejected</div>
            <div class="value text-red">{{ number_format($summary['rejected_loans'] ?? 0, 0) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Avg Interest Rate</div>
            <div class="value text-pink">{{ number_format($summary['avg_interest'] ?? 0, 2) }}%</div>
        </div>
    </div>

    <!-- Quick Summary -->
    <div class="quick-summary">
        <div class="quick-item">
            <div class="label">Outstanding Balance</div>
            <div class="value text-amber">TZS {{ number_format($summary['principal_amount'] - $summary['total_payments'], 0) }}</div>
        </div>
        <div class="quick-item">
            <div class="label">Pending Approvals</div>
            <div class="value text-blue">{{ number_format($summary['pending_approvals'] ?? 0, 0) }}</div>
        </div>
        <div class="quick-item">
            <div class="label">Total Payments</div>
            <div class="value text-teal">TZS {{ number_format($summary['total_payments'] ?? 0, 0) }}</div>
        </div>
        <div class="quick-item">
            <div class="label">Total Suppliers</div>
            <div class="value text-gray">{{ $allLoans->groupBy('suppliers_id')->count() }}</div>
        </div>
    </div>

    <!-- Detailed Loans Table -->
    <div class="table-title">Detailed Loans List</div>
    <table>
        <thead>
            <tr>
                <th>Loan ID</th>
                <th>Branch</th>
                <th>Supplier</th>
                <th class="text-right">Amount</th>
                <th class="text-right">Interest (%)</th>
                <th class="text-right">Interest Amount</th>
                <th class="text-center">Duration</th>
                <th class="text-center">Status</th>
                <th class="text-right">Paid</th>
                <th class="text-center">Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($allLoans as $loan)
                @php
                    $interestAmount = $loan->amount * ($loan->interest_rate / 100);
                    $paid = $loan->payments->sum('amount');
                @endphp
                <tr>
                    <td>#{{ $loan->id }}</td>
                    <td>{{ $loan->branch->name ?? 'N/A' }}</td>
                    <td>{{ $loan->supplier->name ?? 'N/A' }}</td>
                    <td class="text-right">TZS {{ number_format($loan->amount, 0) }}</td>
                    <td class="text-right">{{ number_format($loan->interest_rate, 1) }}%</td>
                    <td class="text-right">TZS {{ number_format($interestAmount, 0) }}</td>
                    <td class="text-center">{{ $loan->duration_months }} mo</td>
                    <td class="text-center">
                        <span class="badge badge-{{ $loan->status }}">
                            {{ ucfirst($loan->status) }}
                        </span>
                    </td>
                    <td class="text-right">TZS {{ number_format($paid, 0) }}</td>
                    <td class="text-center">{{ $loan->created_at ? $loan->created_at->format('d/m/Y') : 'N/A' }}</td>
                </tr>
            @endforeach
            @if($allLoans->isEmpty())
                <tr>
                    <td colspan="10" class="text-center" style="padding: 20px; color: #999;">
                        No loans found for the selected period.
                    </td>
                </tr>
            @endif
        </tbody>
        <tfoot class="bg-gray font-bold">
            <tr>
                <td colspan="3">GRAND TOTAL</td>
                <td class="text-right">TZS {{ number_format($summary['principal_amount'] ?? 0, 0) }}</td>
                <td class="text-right">{{ number_format($summary['avg_interest'] ?? 0, 1) }}%</td>
                <td class="text-right">TZS {{ number_format($summary['expected_interest'] ?? 0, 0) }}</td>
                <td colspan="2" class="text-center">{{ $summary['total_loans'] ?? 0 }} Loans</td>
                <td class="text-right">TZS {{ number_format($summary['total_payments'] ?? 0, 0) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Generated on {{ now()->format('d/m/Y H:i:s') }} | Ngemba Mining Management System
        <br>
        <span style="font-size:9px;">This is a computer generated report. No signature required.</span>
    </div>
</body>
</html>