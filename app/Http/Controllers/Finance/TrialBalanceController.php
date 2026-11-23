<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\TrialBalanceService;
use App\Models\Branch;
use App\Models\TrialBalance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TrialBalanceController extends Controller
{
    protected $trialBalanceService;

    public function __construct(TrialBalanceService $trialBalanceService)
    {
        $this->trialBalanceService = $trialBalanceService;
    }

    /**
     * Display the trial balance index page.
     */
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $branchId = $request->input('branch_id');

        $branches = Branch::all();

        try {
            $result = $this->trialBalanceService->generateTrialBalance($startDate, $endDate, $branchId);
            $result['branches'] = $branches;

            return view('finance.trial-balance.index', $result);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate trial balance: ' . $e->getMessage());
        }
    }

    /**
     * Show a specific trial balance
     */
    public function show($id)
    {
        try {
            $trialBalance = TrialBalance::with(['branch'])->findOrFail($id);
            $data = json_decode($trialBalance->data, true);
            return view('finance.trial-balance.show', compact('trialBalance', 'data'));
        } catch (\Exception $e) {
            return redirect()->route('finance.trial-balance.index')
                ->with('error', 'Trial balance not found.');
        }
    }

    /**
     * Branch-wise trial balance
     */
    public function branchWise(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $branches = Branch::with(['cashBalance', 'goldBalance'])->get();
        
        $branchData = [];
        foreach ($branches as $branch) {
            $data = $this->trialBalanceService->generateTrialBalance($startDate, $endDate, $branch->id);
            $branchData[$branch->id] = [
                'branch' => $branch,
                'data' => $data
            ];
        }

        return view('finance.trial-balance.branch-wise', compact('branchData', 'startDate', 'endDate'));
    }

    /**
     * Comparative trial balance
     */
    public function comparative(Request $request)
    {
        $period1Start = $request->input('period1_start', now()->subMonth()->startOfMonth()->format('Y-m-d'));
        $period1End = $request->input('period1_end', now()->subMonth()->endOfMonth()->format('Y-m-d'));
        $period2Start = $request->input('period2_start', now()->startOfMonth()->format('Y-m-d'));
        $period2End = $request->input('period2_end', now()->format('Y-m-d'));
        $branchId = $request->input('branch_id');

        $branches = Branch::all();

        try {
            $comparison = $this->trialBalanceService->getComparativeTrialBalance(
                $branchId,
                $period1Start, 
                $period1End,
                $period2Start, 
                $period2End
            );

            return view('finance.trial-balance.comparative', compact(
                'comparison', 
                'branches',
                'period1Start',
                'period1End',
                'period2Start',
                'period2End'
            ));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate comparative trial balance: ' . $e->getMessage());
        }
    }

    /**
     * Save trial balance
     */
    public function save(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'branch_id' => 'nullable|exists:branches,id',
            'notes' => 'nullable|string'
        ]);

        try {
            $data = $this->trialBalanceService->generateTrialBalance(
                $request->start_date,
                $request->end_date,
                $request->branch_id
            );

            $trialBalance = TrialBalance::create([
                'branch_id' => $request->branch_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'data' => json_encode($data),
                'notes' => $request->notes,
                'created_by' => Auth::id(),
                'generated_at' => now()
            ]);

            return redirect()->route('finance.trial-balance.show', $trialBalance->id)
                ->with('success', '✅ Trial balance saved successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to save trial balance: ' . $e->getMessage());
        }
    }

    /**
     * Export trial balance to CSV
     */
    public function export(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $branchId = $request->input('branch_id');

        try {
            $data = $this->trialBalanceService->generateTrialBalance($startDate, $endDate, $branchId);
            $export = $this->trialBalanceService->exportToCsv($data);

            return response($export['content'])
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $export['filename'] . '"')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to export trial balance: ' . $e->getMessage());
        }
    }

    /**
     * Export trial balance to simple Excel (HTML format with .xls extension)
     * No external package required - uses HTML table format that Excel can read
     */
    public function exportExcel(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $branchId = $request->input('branch_id');

        try {
            $data = $this->trialBalanceService->generateTrialBalance($startDate, $endDate, $branchId);
            
            // Get branch name for the report
            $branchName = 'All Branches';
            if ($branchId) {
                $branch = Branch::find($branchId);
                $branchName = $branch ? $branch->name : 'All Branches';
            }

            // Build Excel content
            $content = $this->buildExcelContent($data, $branchName);

            return response($content)
                ->header('Content-Type', 'application/vnd.ms-excel')
                ->header('Content-Disposition', 'attachment; filename="trial_balance_' . date('Y-m-d') . '.xls"')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to export Excel: ' . $e->getMessage());
        }
    }

    /**
     * Build Excel content as HTML table
     */
    private function buildExcelContent($data, $branchName)
    {
        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" 
                      xmlns:x="urn:schemas-microsoft-com:office:excel" 
                      xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta charset="utf-8">
            <!--[if gte mso 9]>
            <xml>
                <x:ExcelWorkbook>
                    <x:ExcelWorksheets>
                        <x:ExcelWorksheet>
                            <x:Name>Trial Balance</x:Name>
                            <x:WorksheetOptions>
                                <x:DisplayGridlines/>
                            </x:WorksheetOptions>
                        </x:ExcelWorksheet>
                    </x:ExcelWorksheets>
                </x:ExcelWorkbook>
            </xml>
            <![endif]-->
            <style>
                table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; font-size: 11px; }
                th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
                th { background-color: #1a237e; color: #ffffff; font-weight: bold; }
                .title { font-size: 16px; font-weight: bold; text-align: center; border: none; padding: 10px; }
                .subtitle { text-align: center; border: none; padding: 5px; }
                .total { background-color: #e8eaf6; font-weight: bold; }
                .text-right { text-align: right; }
                .text-center { text-align: center; }
                .bg-yellow { background-color: #fefce8; }
                .bg-gray { background-color: #f9fafb; }
                .bg-primary { background-color: #eff6ff; }
                .bg-green { background-color: #f0fdf4; }
                .bg-purple { background-color: #faf5ff; }
            </style>
        </head>
        <body>
            <table>
                <tr>
                    <td colspan="5" class="title">TRIAL BALANCE AS AT ' . strtoupper(\Carbon\Carbon::parse($data['period']['end_date'])->format('d M Y')) . '</td>
                </tr>
                <tr>
                    <td colspan="5" class="subtitle">Branch: ' . $branchName . '</td>
                </tr>
                <tr>
                    <td colspan="5" class="subtitle">Generated: ' . now()->format('d/m/Y H:i:s') . '</td>
                </tr>
                <tr><td colspan="5" style="border: none; height: 10px;"></td></tr>
                
                <tr>
                    <th style="width:8%;">S/NO</th>
                    <th style="width:47%;">DETAILS</th>
                    <th style="width:10%;">FOLIO</th>
                    <th style="width:17.5%;text-align:right;">DR (TZS)</th>
                    <th style="width:17.5%;text-align:right;">CR (TZS)</th>
                </tr>';

        $rowNumber = 1;

        // If multiple branches, show each branch
        if (count($data['data']) > 0) {
            foreach ($data['data'] as $branchId => $branch) {
                // Branch header
                $html .= '<tr>
                    <td colspan="5" style="background-color: #e8eaf6; font-weight: bold; text-align: center;">' . $branch['branch_name'] . '</td>
                </tr>';
                
                // Opening Stock
                $html .= '<tr class="bg-yellow">
                    <td class="text-center">' . $rowNumber++ . '</td>
                    <td><b>Opening Gold Stock</b></td>
                    <td>GL</td>
                    <td class="text-right"><b>' . number_format($branch['gold_stock']['opening_value'] ?? 0, 0) . '</b></td>
                    <td class="text-right">-</td>
                </tr>';
                
                // Cash
                $html .= '<tr>
                    <td class="text-center">' . $rowNumber++ . '</td>
                    <td>Cash</td>
                    <td>GL</td>
                    <td class="text-right">' . number_format($branch['cash']['closing'] ?? 0, 0) . '</td>
                    <td class="text-right">-</td>
                </tr>';
                
                // Sales
                $html .= '<tr>
                    <td class="text-center">' . $rowNumber++ . '</td>
                    <td>Sales</td>
                    <td>SL</td>
                    <td class="text-right">-</td>
                    <td class="text-right">' . number_format($branch['transactions']['sales_value'] ?? 0, 0) . '</td>
                </tr>';
                
                // Purchases
                $html .= '<tr>
                    <td class="text-center">' . $rowNumber++ . '</td>
                    <td>Purchases</td>
                    <td>PL</td>
                    <td class="text-right">' . number_format($branch['transactions']['purchases_value'] ?? 0, 0) . '</td>
                    <td class="text-right">-</td>
                </tr>';
                
                // Loans Issued
                $html .= '<tr>
                    <td class="text-center">' . $rowNumber++ . '</td>
                    <td>Loans Issued</td>
                    <td>GL</td>
                    <td class="text-right">' . number_format($branch['loans']['total_issued'] ?? 0, 0) . '</td>
                    <td class="text-right">-</td>
                </tr>';
                
                // Loans Repaid
                $html .= '<tr>
                    <td class="text-center">' . $rowNumber++ . '</td>
                    <td>Loans Repaid</td>
                    <td>GL</td>
                    <td class="text-right">-</td>
                    <td class="text-right">' . number_format($branch['loans']['total_repaid'] ?? 0, 0) . '</td>
                </tr>';
                
                // Accounts Receivable
                $html .= '<tr>
                    <td class="text-center">' . $rowNumber++ . '</td>
                    <td>Accounts Receivable</td>
                    <td>SL</td>
                    <td class="text-right">' . number_format($branch['customers']['total_outstanding'] ?? 0, 0) . '</td>
                    <td class="text-right">-</td>
                </tr>';
                
                // Accounts Payable
                $html .= '<tr>
                    <td class="text-center">' . $rowNumber++ . '</td>
                    <td>Accounts Payable</td>
                    <td>PL</td>
                    <td class="text-right">-</td>
                    <td class="text-right">' . number_format($branch['suppliers']['total_outstanding'] ?? 0, 0) . '</td>
                </tr>';
                
                // Expenses
                $html .= '<tr>
                    <td class="text-center">' . $rowNumber++ . '</td>
                    <td>Expenses</td>
                    <td>GL</td>
                    <td class="text-right">' . number_format($branch['expenses']['total_expenses'] ?? 0, 0) . '</td>
                    <td class="text-right">-</td>
                </tr>';
                
                // Calculate totals for this branch
                $totalDebit = 0;
                $totalCredit = 0;
                $totalDebit += $branch['gold_stock']['opening_value'] ?? 0;
                $totalDebit += $branch['cash']['closing'] ?? 0;
                $totalDebit += $branch['transactions']['purchases_value'] ?? 0;
                $totalDebit += $branch['loans']['total_issued'] ?? 0;
                $totalDebit += $branch['customers']['total_outstanding'] ?? 0;
                $totalDebit += $branch['expenses']['total_expenses'] ?? 0;
                $totalCredit += $branch['transactions']['sales_value'] ?? 0;
                $totalCredit += $branch['loans']['total_repaid'] ?? 0;
                $totalCredit += $branch['suppliers']['total_outstanding'] ?? 0;
                
                // Total Row
                $html .= '<tr class="total">
                    <td colspan="3"><b>TOTAL</b></td>
                    <td class="text-right"><b>' . number_format($totalDebit, 0) . '</b></td>
                    <td class="text-right"><b>' . number_format($totalCredit, 0) . '</b></td>
                </tr>';
                
                // Add blank row between branches
                $html .= '<tr><td colspan="5" style="border: none; height: 8px;"></td></tr>';
            }

            // Grand Totals if multiple branches
            if (count($data['data']) > 1) {
                $html .= '<tr>
                    <td colspan="5" style="background-color: #1a237e; color: #ffffff; font-weight: bold; text-align: center;">GRAND TOTALS (All Branches)</td>
                </tr>';
                $html .= '<tr>
                    <td><b>Opening Gold Stock</b></td>
                    <td colspan="4" class="text-right"><b>' . number_format($data['totals']['gold_stock']['opening_stock'] ?? 0, 2) . ' g</b></td>
                </tr>';
                $html .= '<tr>
                    <td><b>Opening Gold Value</b></td>
                    <td colspan="4" class="text-right"><b>TZS ' . number_format($data['totals']['gold_stock']['opening_value'] ?? 0, 0) . '</b></td>
                </tr>';
                $html .= '<tr>
                    <td><b>Total Cash</b></td>
                    <td colspan="4" class="text-right"><b>TZS ' . number_format($data['totals']['cash']['closing'] ?? 0, 0) . '</b></td>
                </tr>';
                $html .= '<tr>
                    <td><b>Total Assets</b></td>
                    <td colspan="4" class="text-right"><b>TZS ' . number_format($data['totals']['summary']['total_assets'] ?? 0, 0) . '</b></td>
                </tr>';
                $html .= '<tr>
                    <td><b>Total Liabilities</b></td>
                    <td colspan="4" class="text-right"><b>TZS ' . number_format($data['totals']['summary']['total_liabilities'] ?? 0, 0) . '</b></td>
                </tr>';
                $html .= '<tr>
                    <td><b>Total Equity</b></td>
                    <td colspan="4" class="text-right"><b>TZS ' . number_format($data['totals']['summary']['total_equity'] ?? 0, 0) . '</b></td>
                </tr>';
                $html .= '<tr>
                    <td><b>Loans Outstanding</b></td>
                    <td colspan="4" class="text-right"><b>TZS ' . number_format($data['totals']['loans']['total_outstanding'] ?? 0, 0) . '</b></td>
                </tr>';
            }
        } else {
            $html .= '<tr><td colspan="5" style="text-align:center;padding:20px;color:#999;">No data available for the selected period.</td></tr>';
        }

        $html .= '</table>
        </body>
        </html>';

        return $html;
    }

    /**
     * Export trial balance to PDF
     */
    public function exportPdf(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $branchId = $request->input('branch_id');

        try {
            $data = $this->trialBalanceService->generateTrialBalance($startDate, $endDate, $branchId);
            
            // Get branch name for the report
            $branchName = 'All Branches';
            if ($branchId) {
                $branch = Branch::find($branchId);
                $branchName = $branch ? $branch->name : 'All Branches';
            }

            $data['branchName'] = $branchName;
            
            return view('finance.trial-balance.export-pdf', $data);

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Generate report view
     */
    public function generateReport(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $branchId = $request->input('branch_id');

        try {
            $data = $this->trialBalanceService->generateTrialBalance($startDate, $endDate, $branchId);
            return view('finance.trial-balance.report', $data);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate report: ' . $e->getMessage());
        }
    }

    /**
     * Get trial balance summary for dashboard (API)
     */
    public function summary(Request $request)
    {
        $branchId = $request->input('branch_id');

        try {
            $summary = $this->trialBalanceService->getDashboardSummary($branchId);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $summary
                ]);
            }

            return view('finance.trial-balance.summary', compact('summary'));

        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 500);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete a saved trial balance
     */
    public function destroy($id)
    {
        try {
            $trialBalance = TrialBalance::findOrFail($id);
            
            // Check permission (only creator or admin can delete)
            if ($trialBalance->created_by != Auth::id() && Auth::user()->role !== 'director') {
                abort(403, 'You do not have permission to delete this trial balance.');
            }

            $trialBalance->delete();

            return redirect()->route('finance.trial-balance.index')
                ->with('success', '✅ Trial balance deleted successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete trial balance: ' . $e->getMessage());
        }
    }

    /**
     * Get trial balance history
     */
    public function history(Request $request)
    {
        $query = TrialBalance::with(['branch', 'user'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $history = $query->paginate(20);
        $branches = Branch::all();

        return view('finance.trial-balance.history', compact('history', 'branches'));
    }
}