<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Expenses Report</title>
    <style>
        /* Same styling as assets print view */
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
        .bg-gray { background: #f9fafb; }
        .bg-red { background: #fef2f2; }
        .footer { text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; color: #999; font-size: 10px; }
        .category-title { background: #e8eaf6; padding: 6px 10px; font-weight: bold; margin-top: 15px; }
        .text-red { color: #b91c1c; }
        @media print {
            body { padding: 10px; }
            .no-print { display: none !important; }
            .summary-item { background: #f9fafb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            th { background: #1a237e !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .bg-gray { background: #f9fafb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .bg-red { background: #fef2f2 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>EXPENSES REPORT</h1>
        <div class="meta">Generated: {{ now()->format('d/m/Y H:i:s') }} | Ngemba Mining Management System</div>
    </div>

    <!-- Summary -->
    <div class="summary">
        <div class="summary-item">
            <div class="label">Total Expenses</div>
            <div class="value text-red">TZS {{ number_format($totalExpenses, 0) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Total Records</div>
            <div class="value">{{ $expenses->count() }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Average Expense</div>
            <div class="value">TZS {{ number_format($averageExpense ?? 0, 0) }}</div>
        </div>
        <div class="summary-item">
            <div class="label">Highest Expense</div>
            <div class="value text-red">TZS {{ number_format($maxExpense ?? 0, 0) }}</div>
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
                    <th class="text-right">Total Amount</th>
                    <th class="text-right">Average</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byCategory ?? [] as $category => $data)
                <tr>
                    <td>{{ $category }}</td>
                    <td class="text-right">{{ $data['count'] }}</td>
                    <td class="text-right text-red">TZS {{ number_format($data['total'], 0) }}</td>
                    <td class="text-right">TZS {{ number_format($data['average'], 0) }}</td>
                </tr>
                @endforeach
                <tr class="bg-gray font-bold">
                    <td>TOTAL</td>
                    <td class="text-right">{{ $expenses->count() }}</td>
                    <td class="text-right text-red">TZS {{ number_format($totalExpenses, 0) }}</td>
                    <td class="text-right">TZS {{ number_format($averageExpense ?? 0, 0) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Detailed Expenses List -->
    <h3 style="font-size: 12px; margin: 15px 0 5px;">Detailed Expenses List</h3>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Category</th>
                <th>Branch</th>
                <th class="text-right">Amount</th>
                <th>Description</th>
                <th class="text-center">Date</th>
                <th>Recorded By</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expenses as $expense)
            <tr>
                <td>#{{ $expense->id }}</td>
                <td>{{ $expense->category ?? 'Uncategorized' }}</td>
                <td>{{ $expense->branch->name ?? 'N/A' }}</td>
                <td class="text-right text-red">TZS {{ number_format($expense->amount, 0) }}</td>
                <td>{{ Str::limit($expense->description ?? 'No description', 40) }}</td>
                <td class="text-center">{{ $expense->created_at->format('d/m/Y') }}</td>
                <td>{{ $expense->recordedBy->full_name ?? $expense->recordedBy->username ?? 'N/A' }}</td>
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