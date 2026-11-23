<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Assets Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; padding: 20px; }
        .header { text-align: center; border-bottom: 3px solid #1a237e; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 22px; color: #1a237e; }
        .header .meta { font-size: 11px; color: #666; margin-top: 5px; }
        .summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px; }
        .summary-item { background: #f9fafb; padding: 10px; border-radius: 5px; text-align: center; border: 1px solid #e5e7eb; }
        .summary-item .label { font-size: 10px; color: #666; text-transform: uppercase; }
        .summary-item .value { font-size: 16px; font-weight: bold; margin-top: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 10px; }
        th { background: #1a237e; color: white; padding: 8px 10px; text-align: left; }
        td { padding: 6px 10px; border-bottom: 1px solid #e5e7eb; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .bg-yellow { background: #fefce8; }
        .bg-gray { background: #f9fafb; }
        .badge { padding: 2px 8px; border-radius: 3px; font-size: 9px; text-transform: uppercase; }
        .badge-active { background: #d1fae5; color: #065f46; }
        .badge-maintenance { background: #fef3c7; color: #92400e; }
        .badge-disposed { background: #fee2e2; color: #991b1b; }
        .footer { text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; color: #999; font-size: 10px; }
        .page-break { page-break-after: always; }
        .category-title { background: #e8eaf6; padding: 6px 10px; font-weight: bold; margin-top: 15px; }
        @media print {
            body { padding: 10px; }
            .no-print { display: none !important; }
            .summary-item { background: #f9fafb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            th { background: #1a237e !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .badge-active { background: #d1fae5 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .badge-maintenance { background: #fef3c7 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .badge-disposed { background: #fee2e2 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>ASSETS MANAGEMENT REPORT</h1>
        <div class="meta">Generated: {{ now()->format('d/m/Y H:i:s') }} | Ngemba Mining Management System</div>
    </div>

    <!-- Summary -->
    <div class="summary">
        <div class="summary-item">
            <div class="label">Total Assets</div>
            <div class="value">{{ $assets->count() }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Total Purchase Value</div>
            <div class="value">TZS {{ number_format($totalPurchaseValue, 0) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Total Current Value</div>
            <div class="value" style="color:#166534;">TZS {{ number_format($totalValue, 0) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Total Depreciation</div>
            <div class="value" style="color:#b91c1c;">TZS {{ number_format($totalDepreciation, 0) }}</div>
        </div>
    </div>

    <!-- By Category Summary -->
    <div style="margin-bottom: 15px;">
        <h3 style="font-size: 12px; margin-bottom: 5px;">Summary by Category</h3>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th class="text-right">Count</th>
                    <th class="text-right">Purchase Value</th>
                    <th class="text-right">Current Value</th>
                    <th class="text-right">Depreciation</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byCategory as $category => $data)
                <tr>
                    <td>{{ $category }}</td>
                    <td class="text-right">{{ $data['count'] }}</td>
                    <td class="text-right">TZS {{ number_format($data['purchase_value'], 0) }}</td>
                    <td class="text-right">TZS {{ number_format($data['value'], 0) }}</td>
                    <td class="text-right" style="color:#b91c1c;">TZS {{ number_format($data['purchase_value'] - $data['value'], 0) }}</td>
                </tr>
                @endforeach
                <tr class="bg-gray font-bold">
                    <td>TOTAL</td>
                    <td class="text-right">{{ $assets->count() }}</td>
                    <td class="text-right">TZS {{ number_format($totalPurchaseValue, 0) }}</td>
                    <td class="text-right">TZS {{ number_format($totalValue, 0) }}</td>
                    <td class="text-right" style="color:#b91c1c;">TZS {{ number_format($totalDepreciation, 0) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Detailed Assets List -->
    <h3 style="font-size: 12px; margin: 15px 0 5px;">Detailed Assets List</h3>
    <table>
        <thead>
            <tr>
                <th>Asset Code</th>
                <th>Name</th>
                <th>Category</th>
                <th>Branch</th>
                <th class="text-right">Purchase Price</th>
                <th class="text-right">Current Value</th>
                <th class="text-right">Depreciation</th>
                <th>Status</th>
                <th>Assigned To</th>
            </tr>
        </thead>
        <tbody>
            @foreach($assets as $asset)
            <tr>
                <td>{{ $asset->asset_code }}</td>
                <td>{{ $asset->name }}</td>
                <td>{{ $asset->category }}</td>
                <td>{{ $asset->branch->name ?? 'N/A' }}</td>
                <td class="text-right">TZS {{ number_format($asset->purchase_price, 0) }}</td>
                <td class="text-right font-bold">TZS {{ number_format($asset->current_value, 0) }}</td>
                <td class="text-right" style="color:#b91c1c;">TZS {{ number_format($asset->purchase_price - $asset->current_value, 0) }}</td>
                <td>
                    <span class="badge badge-{{ $asset->status }}">
                        {{ $asset->status }}
                    </span>
                </td>
                <td>{{ $asset->assignedTo->full_name ?? $asset->assignedTo->name ?? $asset->assignedTo->username ?? 'Unassigned' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ now()->format('d/m/Y H:i:s') }} | Ngemba Mining Management System
        <br>
        <span style="font-size:9px;">This is a computer generated report. No signature required.</span>
    </div>
</body>
</html>