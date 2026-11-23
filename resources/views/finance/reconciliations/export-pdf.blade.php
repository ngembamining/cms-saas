<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reconciliation Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; padding: 20px; }
        .header { text-align: center; border-bottom: 3px solid #1a237e; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 22px; color: #1a237e; }
        .header .meta { font-size: 11px; color: #666; margin-top: 5px; }
        .section-title { font-size: 14px; font-weight: bold; margin: 20px 0 10px; color: #1a237e; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 10px; }
        th { background: #1a237e; color: white; padding: 8px 10px; text-align: left; }
        td { padding: 6px 10px; border-bottom: 1px solid #e5e7eb; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .bg-gray { background: #f9fafb; }
        .footer { text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; color: #999; font-size: 10px; }
        .summary-box { background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
        .summary-item { text-align: center; }
        .summary-item .label { font-size: 10px; color: #666; }
        .summary-item .value { font-size: 14px; font-weight: bold; }
        .badge { padding: 2px 8px; border-radius: 3px; font-size: 9px; text-transform: uppercase; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-verified { background: #d1fae5; color: #065f46; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        .badge-active { background: #d1fae5; color: #065f46; }
        .text-red { color: #b91c1c; }
        .text-green { color: #065f46; }
        @media print {
            body { padding: 10px; }
            th { background: #1a237e !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .bg-gray { background: #f9fafb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .badge-pending { background: #fef3c7 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .badge-verified { background: #d1fae5 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .badge-rejected { background: #fee2e2 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>RECONCILIATION REPORT</h1>
        <div class="meta">Generated: {{ now()->format('d/m/Y H:i:s') }} | Ngemba Mining Management System</div>
    </div>

    <!-- Summary -->
    <div class="summary-box">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="label">Total Cash Value</div>
                <div class="value">TZS {{ number_format($totalCashValue, 0) }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Total Cash Variance</div>
                <div class="value {{ $totalCashVariance != 0 ? 'text-red' : 'text-green' }}">
                    TZS {{ number_format($totalCashVariance, 0) }}
                </div>
            </div>
            <div class="summary-item">
                <div class="label">Total Gold Weight</div>
                <div class="value">{{ number_format($totalGoldWeight, 2) }} g</div>
            </div>
            <div class="summary-item">
                <div class="label">Total Gold Difference</div>
                <div class="value {{ $totalGoldDifference != 0 ? 'text-red' : 'text-green' }}">
                    {{ number_format($totalGoldDifference, 2) }} g
                </div>
            </div>
        </div>
    </div>

    <!-- Cash Reconciliations -->
    <div class="section-title">Cash Reconciliations</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Branch</th>
                <th>Date</th>
                <th class="text-right">Opening (TZS)</th>
                <th class="text-right">Closing (TZS)</th>
                <th class="text-right">System Balance (TZS)</th>
                <th class="text-right">Variance (TZS)</th>
                <th>Status</th>
                <th>Reconciled By</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cashReconciliations as $rec)
            <tr>
                <td>#{{ $rec->id }}</td>
                <td>{{ $rec->branch->name ?? 'N/A' }}</td>
                <td>{{ $rec->reconciliation_date->format('d/m/Y') }}</td>
                <td class="text-right">{{ number_format($rec->opening_balance, 0) }}</td>
                <td class="text-right">{{ number_format($rec->closing_balance, 0) }}</td>
                <td class="text-right">{{ number_format($rec->system_balance ?? 0, 0) }}</td>
                <td class="text-right {{ $rec->variance != 0 ? 'text-red' : 'text-green' }}">
                    {{ number_format($rec->variance ?? 0, 0) }}
                </td>
                <td>
                    <span class="badge badge-{{ $rec->status }}">
                        {{ ucfirst($rec->status) }}
                    </span>
                </td>
                <td>{{ $rec->reconciledBy->name ?? 'N/A' }}</td>
            </tr>
            @empty
            <tr><td colspan="9" class="text-center" style="color:#999;">No cash reconciliations found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- Gold Reconciliations -->
    <div class="section-title">Gold Reconciliations</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Branch</th>
                <th>Date</th>
                <th class="text-right">Opening (g)</th>
                <th class="text-right">Closing (g)</th>
                <th class="text-right">Difference (g)</th>
                <th>Status</th>
                <th>Reconciled By</th>
            </tr>
        </thead>
        <tbody>
            @forelse($goldReconciliations as $rec)
            @php $diff = $rec->closing_weight - $rec->opening_weight; @endphp
            <tr>
                <td>#{{ $rec->id }}</td>
                <td>{{ $rec->branch->name ?? 'N/A' }}</td>
                <td>{{ $rec->reconciliation_date->format('d/m/Y') }}</td>
                <td class="text-right">{{ number_format($rec->opening_weight, 2) }}</td>
                <td class="text-right">{{ number_format($rec->closing_weight, 2) }}</td>
                <td class="text-right {{ $diff != 0 ? 'text-red' : 'text-green' }}">
                    {{ number_format($diff, 2) }}
                </td>
                <td>
                    <span class="badge {{ $rec->verified_by ? 'badge-verified' : 'badge-pending' }}">
                        {{ $rec->verified_by ? 'Verified' : 'Pending' }}
                    </span>
                </td>
                <td>{{ $rec->reconciledBy->name ?? 'N/A' }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center" style="color:#999;">No gold reconciliations found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ now()->format('d/m/Y H:i:s') }} | Ngemba Mining Management System
        <br>
        <span style="font-size:9px;">This is a computer generated report. No signature required.</span>
    </div>
</body>
</html>