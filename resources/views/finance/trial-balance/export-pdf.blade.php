<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Trial Balance Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #333;
            background: #fff;
            padding: 20px;
        }
        .no-print { display: none; }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #1a237e;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 24px;
            color: #1a237e;
            margin: 0;
        }
        .header .subtitle {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        .header .meta {
            font-size: 11px;
            color: #888;
            margin-top: 3px;
        }

        .summary-box {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            background: #f9fafb;
        }
        .summary-grid {
            display: table;
            width: 100%;
        }
        .summary-item {
            display: table-cell;
            padding: 8px 15px;
            text-align: center;
            border-right: 1px solid #e5e7eb;
        }
        .summary-item:last-child {
            border-right: none;
        }
        .summary-item .label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .summary-item .value {
            font-size: 18px;
            font-weight: bold;
            margin-top: 3px;
        }
        .value.gold { color: #a16207; }
        .value.cash { color: #166534; }
        .value.assets { color: #1d4ed8; }
        .value.equity { color: #6b21a8; }

        .branch-title {
            background: #e8eaf6;
            padding: 8px 12px;
            font-weight: bold;
            font-size: 13px;
            margin-top: 20px;
            margin-bottom: 10px;
            border-left: 4px solid #1a237e;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10px;
        }
        th {
            background: #1a237e;
            color: white;
            padding: 6px 10px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td {
            padding: 5px 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }

        .bg-yellow { background: #fefce8; }
        .bg-gray { background: #f9fafb; }
        .bg-primary { background: #eff6ff; }
        .bg-green { background: #f0fdf4; }
        .bg-red { background: #fef2f2; }
        
        .text-yellow { color: #a16207; }
        .text-green { color: #166534; }
        .text-red { color: #b91c1c; }
        .text-primary { color: #1d4ed8; }
        .text-purple { color: #6b21a8; }
        
        .font-bold { font-weight: bold; }
        .font-xs { font-size: 9px; }
        .text-muted { color: #999; }

        .grand-totals {
            margin-top: 30px;
            border-top: 3px double #1a237e;
            padding-top: 20px;
        }
        .grand-totals .title {
            background: #1a237e;
            color: white;
            padding: 8px 12px;
            font-weight: bold;
            font-size: 13px;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            color: #999;
            font-size: 10px;
        }

        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #1a237e;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            z-index: 1000;
        }
        .print-btn:hover {
            background: #0d1442;
        }

        @media print {
            body { padding: 10px; }
            .no-print { display: none !important; }
            .print-btn { display: none !important; }
            .page-break { page-break-after: always; }
            
            .bg-yellow { background: #fefce8 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .bg-gray { background: #f9fafb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .bg-primary { background: #eff6ff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .bg-green { background: #f0fdf4 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .bg-red { background: #fef2f2 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            th { background: #1a237e !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            td, th { border: 1px solid #ddd; }
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">
        🖨️ Print / Save as PDF
    </button>

    <div class="header">
        <h1>TRIAL BALANCE REPORT</h1>
        <div class="subtitle">Ngemba Mining Management System</div>
        <div class="meta">
            Period: {{ \Carbon\Carbon::parse($period['start_date'])->format('d/m/Y') }} to {{ \Carbon\Carbon::parse($period['end_date'])->format('d/m/Y') }}
            | Generated: {{ now()->format('d/m/Y H:i:s') }}
            | Branch: {{ $branchName ?? 'All Branches' }}
        </div>
    </div>

    <div class="summary-box">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="label">Opening Gold Stock</div>
                <div class="value gold">{{ number_format($totals['gold_stock']['opening_stock'] ?? 0, 2) }} g</div>
                <div style="font-size:10px;color:#666;">TZS {{ number_format($totals['gold_stock']['opening_value'] ?? 0, 0) }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Total Cash</div>
                <div class="value cash">TZS {{ number_format($totals['cash']['closing'] ?? 0, 0) }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Total Assets</div>
                <div class="value assets">TZS {{ number_format($totals['summary']['total_assets'] ?? 0, 0) }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Total Equity</div>
                <div class="value equity">TZS {{ number_format($totals['summary']['total_equity'] ?? 0, 0) }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Loans Outstanding</div>
                <div class="value text-purple">TZS {{ number_format($totals['loans']['total_outstanding'] ?? 0, 0) }}</div>
            </div>
        </div>
    </div>

    @if(isset($data) && count($data) > 0)
        @foreach($data as $branchId => $branch)
            <div class="branch-title">{{ $branch['branch_name'] }}</div>
            
            <table>
                <thead>
                    <tr>
                        <th style="width:40%;">Description</th>
                        <th style="width:20%;text-align:right;">Debit (TZS)</th>
                        <th style="width:20%;text-align:right;">Credit (TZS)</th>
                        <th style="width:20%;text-align:right;">Balance (TZS)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="bg-yellow">
                        <td>
                            <span class="font-bold">Opening Gold Stock</span>
                            <span class="text-muted font-xs">(Balance)</span>
                            <br>
                            <span class="text-muted font-xs">{{ number_format($branch['gold_stock']['opening_stock'] ?? 0, 2) }} g @ TZS {{ number_format($branch['gold_stock']['rate'] ?? 0, 0) }}/g</span>
                        </td>
                        <td class="text-right font-bold">{{ number_format($branch['gold_stock']['opening_value'] ?? 0, 0) }}</td>
                        <td class="text-right">-</td>
                        <td class="text-right font-bold text-yellow">{{ number_format($branch['gold_stock']['opening_value'] ?? 0, 0) }}</td>
                    </tr>

                    <tr>
                        <td>
                            Gold Purchases (Period)
                            <br><span class="text-muted font-xs">{{ number_format($branch['transactions']['purchases'] ?? 0, 2) }} g</span>
                        </td>
                        <td class="text-right">{{ number_format($branch['transactions']['purchases_value'] ?? 0, 0) }}</td>
                        <td class="text-right">-</td>
                        <td class="text-right">{{ number_format(($branch['gold_stock']['opening_value'] ?? 0) + ($branch['transactions']['purchases_value'] ?? 0), 0) }}</td>
                    </tr>

                    <tr>
                        <td>
                            Gold Sales (Period)
                            <br><span class="text-muted font-xs">{{ number_format($branch['transactions']['sales'] ?? 0, 2) }} g</span>
                        </td>
                        <td class="text-right">-</td>
                        <td class="text-right">{{ number_format($branch['transactions']['sales_value'] ?? 0, 0) }}</td>
                        <td class="text-right">{{ number_format(($branch['gold_stock']['opening_value'] ?? 0) + ($branch['transactions']['purchases_value'] ?? 0) - ($branch['transactions']['sales_value'] ?? 0), 0) }}</td>
                    </tr>

                    <tr>
                        <td class="text-muted">
                            Net Change
                            <br><span class="text-muted font-xs">{{ number_format($branch['transactions']['net_change'] ?? 0, 2) }} g</span>
                        </td>
                        <td class="text-right">-</td>
                        <td class="text-right">-</td>
                        <td class="text-right">{{ number_format(($branch['gold_stock']['opening_value'] ?? 0) + ($branch['transactions']['purchases_value'] ?? 0) - ($branch['transactions']['sales_value'] ?? 0), 0) }}</td>
                    </tr>

                    <tr><td colspan="4" style="padding:2px;"></td></tr>

                    <tr>
                        <td>Opening Cash</td>
                        <td class="text-right">{{ number_format($branch['cash']['opening'] ?? 0, 0) }}</td>
                        <td class="text-right">-</td>
                        <td class="text-right">{{ number_format($branch['cash']['opening'] ?? 0, 0) }}</td>
                    </tr>
                    <tr>
                        <td>Cash Additions (Period)</td>
                        <td class="text-right">{{ number_format($branch['cash']['additions'] ?? 0, 0) }}</td>
                        <td class="text-right">-</td>
                        <td class="text-right">{{ number_format(($branch['cash']['opening'] ?? 0) + ($branch['cash']['additions'] ?? 0), 0) }}</td>
                    </tr>
                    <tr>
                        <td>Cash Withdrawals (Period)</td>
                        <td class="text-right">-</td>
                        <td class="text-right">{{ number_format($branch['cash']['withdrawals'] ?? 0, 0) }}</td>
                        <td class="text-right">{{ number_format(($branch['cash']['opening'] ?? 0) + ($branch['cash']['additions'] ?? 0) - ($branch['cash']['withdrawals'] ?? 0), 0) }}</td>
                    </tr>
                    <tr class="bg-gray">
                        <td class="font-bold">Closing Cash</td>
                        <td class="text-right font-bold">{{ number_format($branch['cash']['closing'] ?? 0, 0) }}</td>
                        <td class="text-right">-</td>
                        <td class="text-right font-bold text-green">{{ number_format($branch['cash']['closing'] ?? 0, 0) }}</td>
                    </tr>

                    <tr><td colspan="4" style="padding:2px;"></td></tr>

                    <tr>
                        <td>Loans Issued</td>
                        <td class="text-right">{{ number_format($branch['loans']['total_issued'] ?? 0, 0) }}</td>
                        <td class="text-right">-</td>
                        <td class="text-right">{{ number_format($branch['loans']['total_issued'] ?? 0, 0) }}</td>
                    </tr>
                    <tr>
                        <td>Loans Repaid</td>
                        <td class="text-right">-</td>
                        <td class="text-right">{{ number_format($branch['loans']['total_repaid'] ?? 0, 0) }}</td>
                        <td class="text-right">{{ number_format(($branch['loans']['total_issued'] ?? 0) - ($branch['loans']['total_repaid'] ?? 0), 0) }}</td>
                    </tr>
                    <tr class="bg-gray">
                        <td class="font-bold">Loans Outstanding</td>
                        <td class="text-right font-bold">{{ number_format($branch['loans']['total_outstanding'] ?? 0, 0) }}</td>
                        <td class="text-right">-</td>
                        <td class="text-right font-bold text-purple">{{ number_format($branch['loans']['total_outstanding'] ?? 0, 0) }}</td>
                    </tr>

                    <tr><td colspan="4" style="padding:2px;"></td></tr>

                    <tr>
                        <td>Customer Outstanding</td>
                        <td class="text-right">{{ number_format($branch['customers']['total_outstanding'] ?? 0, 0) }}</td>
                        <td class="text-right">-</td>
                        <td class="text-right">{{ number_format($branch['customers']['total_outstanding'] ?? 0, 0) }}</td>
                    </tr>
                    <tr>
                        <td>Supplier Outstanding</td>
                        <td class="text-right">-</td>
                        <td class="text-right">{{ number_format($branch['suppliers']['total_outstanding'] ?? 0, 0) }}</td>
                        <td class="text-right text-red">{{ number_format($branch['suppliers']['total_outstanding'] ?? 0, 0) }}</td>
                    </tr>

                    <tr><td colspan="4" style="padding:2px;"></td></tr>

                    <tr>
                        <td>Total Expenses</td>
                        <td class="text-right">{{ number_format($branch['expenses']['total_expenses'] ?? 0, 0) }}</td>
                        <td class="text-right">-</td>
                        <td class="text-right text-red">{{ number_format($branch['expenses']['total_expenses'] ?? 0, 0) }}</td>
                    </tr>

                    <tr><td colspan="4" style="padding:2px;"></td></tr>

                    <tr class="bg-primary">
                        <td class="font-bold">TOTAL ASSETS</td>
                        <td class="text-right font-bold">{{ number_format($branch['summary']['total_assets'] ?? 0, 0) }}</td>
                        <td class="text-right">-</td>
                        <td class="text-right font-bold text-primary">{{ number_format($branch['summary']['total_assets'] ?? 0, 0) }}</td>
                    </tr>
                    <tr class="bg-red">
                        <td class="font-bold">TOTAL LIABILITIES</td>
                        <td class="text-right">-</td>
                        <td class="text-right font-bold">{{ number_format($branch['summary']['total_liabilities'] ?? 0, 0) }}</td>
                        <td class="text-right font-bold text-red">{{ number_format($branch['summary']['total_liabilities'] ?? 0, 0) }}</td>
                    </tr>
                    <tr class="bg-green">
                        <td class="font-bold text-green">TOTAL EQUITY</td>
                        <td class="text-right font-bold text-green">{{ number_format($branch['summary']['total_equity'] ?? 0, 0) }}</td>
                        <td class="text-right">-</td>
                        <td class="text-right font-bold text-green">{{ number_format($branch['summary']['total_equity'] ?? 0, 0) }}</td>
                    </tr>
                    <tr class="bg-yellow">
                        <td class="font-bold text-yellow">OPENING STOCK BALANCE</td>
                        <td class="text-right font-bold text-yellow">{{ number_format($branch['summary']['opening_stock_balance'] ?? 0, 0) }}</td>
                        <td class="text-right">-</td>
                        <td class="text-right font-bold text-yellow">{{ number_format($branch['summary']['opening_stock_balance'] ?? 0, 0) }}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach

        @if(count($data) > 1)
            <div class="grand-totals">
                <div class="title">GRAND TOTALS (All Branches)</div>
                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th style="text-align:right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Opening Gold Stock</td>
                            <td class="text-right">{{ number_format($totals['gold_stock']['opening_stock'] ?? 0, 2) }} g</td>
                        </tr>
                        <tr>
                            <td>Opening Gold Value</td>
                            <td class="text-right">TZS {{ number_format($totals['gold_stock']['opening_value'] ?? 0, 0) }}</td>
                        </tr>
                        <tr>
                            <td>Total Cash</td>
                            <td class="text-right">TZS {{ number_format($totals['cash']['closing'] ?? 0, 0) }}</td>
                        </tr>
                        <tr>
                            <td>Total Assets</td>
                            <td class="text-right">TZS {{ number_format($totals['summary']['total_assets'] ?? 0, 0) }}</td>
                        </tr>
                        <tr>
                            <td>Total Liabilities</td>
                            <td class="text-right">TZS {{ number_format($totals['summary']['total_liabilities'] ?? 0, 0) }}</td>
                        </tr>
                        <tr>
                            <td>Total Equity</td>
                            <td class="text-right">TZS {{ number_format($totals['summary']['total_equity'] ?? 0, 0) }}</td>
                        </tr>
                        <tr>
                            <td>Loans Outstanding</td>
                            <td class="text-right">TZS {{ number_format($totals['loans']['total_outstanding'] ?? 0, 0) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    @else
        <p style="text-align:center;padding:40px;color:#999;">No data available for the selected period.</p>
    @endif

    <div class="footer">
        Generated on {{ now()->format('d/m/Y H:i:s') }} | Ngemba Mining Management System
        <br>
        <span style="font-size:9px;">This is a computer generated report. No signature required.</span>
    </div>
</body>
</html>