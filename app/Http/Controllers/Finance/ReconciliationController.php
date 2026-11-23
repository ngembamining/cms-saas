<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\CashReconciliation;
use App\Models\GoldReconciliation;
use App\Models\BranchBalance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReconciliationController extends Controller
{
    /**
     * Check if user can access all branches (Accountant or Director)
     */
    private function canAccessAllBranches()
    {
        return in_array(Auth::user()->role, ['accountant', 'director']);
    }

    /**
     * Display a listing of reconciliations.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Cash Reconciliations
        $cashQuery = CashReconciliation::with(['branch', 'reconciledBy', 'verifiedBy']);
        
        if (!$this->canAccessAllBranches()) {
            $cashQuery->where('branch_id', $user->assigned_branch);
        }
        
        if ($request->filled('branch_id')) {
            $cashQuery->where('branch_id', $request->branch_id);
        }
        
        if ($request->filled('status')) {
            $cashQuery->where('status', $request->status);
        }
        
        if ($request->filled('from_date')) {
            $cashQuery->whereDate('reconciliation_date', '>=', $request->from_date);
        }
        
        if ($request->filled('to_date')) {
            $cashQuery->whereDate('reconciliation_date', '<=', $request->to_date);
        }
        
        $cashReconciliations = $cashQuery->orderBy('created_at', 'desc')->paginate(10)->appends($request->all());
        
        // Gold Reconciliations
        $goldQuery = GoldReconciliation::with(['branch', 'reconciledBy', 'verifiedBy']);
        
        if (!$this->canAccessAllBranches()) {
            $goldQuery->where('branch_id', $user->assigned_branch);
        }
        
        if ($request->filled('branch_id')) {
            $goldQuery->where('branch_id', $request->branch_id);
        }
        
        if ($request->filled('from_date')) {
            $goldQuery->whereDate('reconciliation_date', '>=', $request->from_date);
        }
        
        if ($request->filled('to_date')) {
            $goldQuery->whereDate('reconciliation_date', '<=', $request->to_date);
        }
        
        $goldReconciliations = $goldQuery->orderBy('created_at', 'desc')->paginate(10)->appends($request->all());
        
        // Branches for filter
        $branches = Branch::when(!$this->canAccessAllBranches(), function ($q) use ($user) {
            return $q->where('id', $user->assigned_branch);
        })->get();
        
        $statuses = ['pending', 'verified', 'rejected'];
        
        // Statistics
        $totalReconciliations = CashReconciliation::count() + GoldReconciliation::count();
        $pendingReconciliations = CashReconciliation::where('status', 'pending')->count();
        $verifiedReconciliations = CashReconciliation::where('status', 'verified')->count();
        $rejectedReconciliations = CashReconciliation::where('status', 'rejected')->count();
        
        return view('finance.reconciliations.index', compact(
            'cashReconciliations',
            'goldReconciliations',
            'branches',
            'statuses',
            'totalReconciliations',
            'pendingReconciliations',
            'verifiedReconciliations',
            'rejectedReconciliations'
        ));
    }

    /**
     * Show the form for creating a new cash reconciliation.
     */
    public function createCash()
    {
        $user = Auth::user();
        
        $branches = Branch::when(!$this->canAccessAllBranches(), function ($q) use ($user) {
            return $q->where('id', $user->assigned_branch);
        })->get();
        
        return view('finance.reconciliations.create-cash', compact('branches'));
    }

    /**
     * Store a newly created cash reconciliation.
     */
    public function storeCash(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'reconciliation_date' => 'required|date',
            'opening_balance' => 'required|numeric|min:0',
            'closing_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Check if reconciliation already exists for this branch and date
            $exists = CashReconciliation::where('branch_id', $request->branch_id)
                ->where('reconciliation_date', $request->reconciliation_date)
                ->exists();

            if ($exists) {
                throw new \Exception('Reconciliation already exists for this branch and date.');
            }

            // Get system calculated balance (net cash flow)
            $systemBalance = $this->calculateSystemBalance($request->branch_id, $request->reconciliation_date);
            
            // Calculate variance
            $variance = $request->closing_balance - ($request->opening_balance + $systemBalance);

            $reconciliation = CashReconciliation::create([
                'branch_id' => $request->branch_id,
                'reconciliation_date' => $request->reconciliation_date,
                'opening_balance' => $request->opening_balance,
                'closing_balance' => $request->closing_balance,
                'system_balance' => $systemBalance,
                'variance' => $variance,
                'notes' => $request->notes,
                'reconciled_by' => Auth::id(),
                'status' => 'pending',
            ]);

            DB::commit();

            return redirect()->route('finance.reconciliations.index')
                ->with('success', '✅ Cash reconciliation created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to create reconciliation: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified cash reconciliation.
     */
    public function showCash($id)
    {
        $reconciliation = CashReconciliation::with(['branch', 'reconciledBy', 'verifiedBy'])->findOrFail($id);
        
        if (!$this->canAccessAllBranches() && $reconciliation->branch_id != Auth::user()->assigned_branch) {
            abort(403, 'You do not have access to this reconciliation.');
        }
        
        return view('finance.reconciliations.show-cash', compact('reconciliation'));
    }

    /**
     * Verify a cash reconciliation.
     */
    public function verifyCash($id)
    {
        $reconciliation = CashReconciliation::findOrFail($id);
        
        if (!$this->canAccessAllBranches()) {
            abort(403, 'Only directors and accountants can verify reconciliations.');
        }
        
        try {
            $reconciliation->update([
                'status' => 'verified',
                'verified_by' => Auth::id(),
                'verified_at' => now(),
            ]);

            return redirect()->route('finance.reconciliations.index')
                ->with('success', '✅ Cash reconciliation verified successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', '❌ Failed to verify reconciliation: ' . $e->getMessage());
        }
    }

    /**
     * Reject a cash reconciliation.
     */
    public function rejectCash($id)
    {
        $reconciliation = CashReconciliation::findOrFail($id);
        
        if (!$this->canAccessAllBranches()) {
            abort(403, 'Only directors and accountants can reject reconciliations.');
        }
        
        try {
            $reconciliation->update([
                'status' => 'rejected',
                'verified_by' => Auth::id(),
                'verified_at' => now(),
            ]);

            return redirect()->route('finance.reconciliations.index')
                ->with('info', 'ℹ️ Cash reconciliation rejected successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', '❌ Failed to reject reconciliation: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new gold reconciliation.
     */
    public function createGold()
    {
        $user = Auth::user();
        
        $branches = Branch::when(!$this->canAccessAllBranches(), function ($q) use ($user) {
            return $q->where('id', $user->assigned_branch);
        })->get();
        
        return view('finance.reconciliations.create-gold', compact('branches'));
    }

    /**
     * Store a newly created gold reconciliation.
     */
    public function storeGold(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'reconciliation_date' => 'required|date',
            'opening_weight' => 'required|numeric|min:0',
            'closing_weight' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Check if reconciliation already exists
            $exists = GoldReconciliation::where('branch_id', $request->branch_id)
                ->where('reconciliation_date', $request->reconciliation_date)
                ->exists();

            if ($exists) {
                throw new \Exception('Gold reconciliation already exists for this branch and date.');
            }

            $reconciliation = GoldReconciliation::create([
                'branch_id' => $request->branch_id,
                'reconciliation_date' => $request->reconciliation_date,
                'opening_weight' => $request->opening_weight,
                'closing_weight' => $request->closing_weight,
                'notes' => $request->notes,
                'reconciled_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('finance.reconciliations.index')
                ->with('success', '✅ Gold reconciliation created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to create gold reconciliation: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified gold reconciliation.
     */
    public function showGold($id)
    {
        $reconciliation = GoldReconciliation::with(['branch', 'reconciledBy', 'verifiedBy'])->findOrFail($id);
        
        if (!$this->canAccessAllBranches() && $reconciliation->branch_id != Auth::user()->assigned_branch) {
            abort(403, 'You do not have access to this reconciliation.');
        }
        
        return view('finance.reconciliations.show-gold', compact('reconciliation'));
    }

    /**
     * Verify a gold reconciliation.
     */
    public function verifyGold($id)
    {
        $reconciliation = GoldReconciliation::findOrFail($id);
        
        if (!$this->canAccessAllBranches()) {
            abort(403, 'Only directors and accountants can verify reconciliations.');
        }
        
        try {
            $reconciliation->update([
                'verified_by' => Auth::id(),
                'verified_at' => now(),
            ]);

            return redirect()->route('finance.reconciliations.index')
                ->with('success', '✅ Gold reconciliation verified successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', '❌ Failed to verify gold reconciliation: ' . $e->getMessage());
        }
    }

    /**
     * Reject a gold reconciliation.
     */
    public function rejectGold($id)
    {
        $reconciliation = GoldReconciliation::findOrFail($id);
        
        if (!$this->canAccessAllBranches()) {
            abort(403, 'Only directors and accountants can reject reconciliations.');
        }
        
        try {
            $reconciliation->delete();

            return redirect()->route('finance.reconciliations.index')
                ->with('info', 'ℹ️ Gold reconciliation rejected and deleted successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', '❌ Failed to reject gold reconciliation: ' . $e->getMessage());
        }
    }

    /**
     * Display reconciliation reports.
     */
    public function reports()
    {
        $user = Auth::user();
        
        $branches = Branch::when(!$this->canAccessAllBranches(), function ($q) use ($user) {
            return $q->where('id', $user->assigned_branch);
        })->get();
        
        return view('finance.reconciliations.reports', compact('branches'));
    }

    /**
     * Calculate system balance for a branch at a specific date.
     */
    private function calculateSystemBalance($branchId, $date)
    {
        // Get opening balance from branch_balances
        $openingBalance = BranchBalance::where('branch_id', $branchId)
            ->where('balance_type', 'cash')
            ->value('amount') ?? 0;
        
        // Calculate net transactions up to the date
        $transactions = DB::table('transactions')
            ->where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereDate('created_at', '<=', $date)
            ->select(
                DB::raw('SUM(CASE WHEN transaction_type IN ("cash_deposit", "gold_sale") THEN amount ELSE 0 END) as inflows'),
                DB::raw('SUM(CASE WHEN transaction_type IN ("cash_withdrawal", "expense", "loan_disbursement") THEN amount ELSE 0 END) as outflows')
            )
            ->first();
        
        $inflows = $transactions->inflows ?? 0;
        $outflows = $transactions->outflows ?? 0;
        
        return $inflows - $outflows;
    }

    /**
     * Print reconciliations report
     */
    public function print(Request $request)
    {
        $user = Auth::user();
        
        // Get cash reconciliations
        $cashQuery = CashReconciliation::with(['branch', 'reconciledBy']);
        if (!$this->canAccessAllBranches()) {
            $cashQuery->where('branch_id', $user->assigned_branch);
        }
        if ($request->filled('branch_id')) {
            $cashQuery->where('branch_id', $request->branch_id);
        }
        if ($request->filled('from_date')) {
            $cashQuery->whereDate('reconciliation_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $cashQuery->whereDate('reconciliation_date', '<=', $request->to_date);
        }
        $cashReconciliations = $cashQuery->orderBy('reconciliation_date', 'desc')->get();
        
        // Get gold reconciliations
        $goldQuery = GoldReconciliation::with(['branch', 'reconciledBy']);
        if (!$this->canAccessAllBranches()) {
            $goldQuery->where('branch_id', $user->assigned_branch);
        }
        if ($request->filled('branch_id')) {
            $goldQuery->where('branch_id', $request->branch_id);
        }
        if ($request->filled('from_date')) {
            $goldQuery->whereDate('reconciliation_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $goldQuery->whereDate('reconciliation_date', '<=', $request->to_date);
        }
        $goldReconciliations = $goldQuery->orderBy('reconciliation_date', 'desc')->get();
        
        // Calculate totals
        $totalCashValue = $cashReconciliations->sum('closing_balance');
        $totalCashVariance = $cashReconciliations->sum('variance');
        $totalGoldWeight = $goldReconciliations->sum('closing_weight');
        $totalGoldDifference = $goldReconciliations->sum(function($rec) {
            return $rec->closing_weight - $rec->opening_weight;
        });
        
        return view('finance.reconciliations.print', compact(
            'cashReconciliations',
            'goldReconciliations',
            'totalCashValue',
            'totalCashVariance',
            'totalGoldWeight',
            'totalGoldDifference'
        ));
    }

    /**
     * Export to Excel (simple HTML)
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        
        // Get reconciliations
        $cashQuery = CashReconciliation::with(['branch']);
        if (!$this->canAccessAllBranches()) {
            $cashQuery->where('branch_id', $user->assigned_branch);
        }
        if ($request->filled('branch_id')) {
            $cashQuery->where('branch_id', $request->branch_id);
        }
        if ($request->filled('from_date')) {
            $cashQuery->whereDate('reconciliation_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $cashQuery->whereDate('reconciliation_date', '<=', $request->to_date);
        }
        $cashReconciliations = $cashQuery->orderBy('reconciliation_date', 'desc')->get();
        
        $goldQuery = GoldReconciliation::with(['branch']);
        if (!$this->canAccessAllBranches()) {
            $goldQuery->where('branch_id', $user->assigned_branch);
        }
        if ($request->filled('branch_id')) {
            $goldQuery->where('branch_id', $request->branch_id);
        }
        if ($request->filled('from_date')) {
            $goldQuery->whereDate('reconciliation_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $goldQuery->whereDate('reconciliation_date', '<=', $request->to_date);
        }
        $goldReconciliations = $goldQuery->orderBy('reconciliation_date', 'desc')->get();
        
        // Build HTML content for Excel
        $html = '<html><head><meta charset="utf-8"><style>
            table { border-collapse: collapse; width: 100%; font-family: Arial; font-size: 11px; }
            th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
            th { background: #1a237e; color: #fff; }
            .text-right { text-align: right; }
            .title { font-size: 16px; font-weight: bold; text-align: center; }
            .sub-title { font-size: 14px; font-weight: bold; margin-top: 15px; }
            .bg-gray { background: #f5f5f5; }
        </style></head><body>';
        
        $html .= '<h2 class="title">RECONCILIATION REPORT</h2>';
        $html .= '<p style="text-align:center;">Generated: ' . now()->format('d/m/Y H:i:s') . '</p>';
        if ($request->filled('branch_id')) {
            $branch = Branch::find($request->branch_id);
            $html .= '<p style="text-align:center;">Branch: ' . ($branch->name ?? 'All Branches') . '</p>';
        }
        $html .= '<hr>';
        
        // Cash Reconciliations
        $html .= '<h3 class="sub-title">Cash Reconciliations</h3>';
        $html .= '<table><thead><tr>
            <th>#</th><th>Branch</th><th>Date</th>
            <th class="text-right">Opening (TZS)</th>
            <th class="text-right">Closing (TZS)</th>
            <th class="text-right">System Balance (TZS)</th>
            <th class="text-right">Variance (TZS)</th>
            <th>Status</th>
            <th>Reconciled By</th>
        </tr></thead><tbody>';
        
        if ($cashReconciliations->count() > 0) {
            foreach ($cashReconciliations as $rec) {
                $html .= '<tr>
                    <td>#' . $rec->id . '</td>
                    <td>' . ($rec->branch->name ?? 'N/A') . '</td>
                    <td>' . $rec->reconciliation_date->format('d/m/Y') . '</td>
                    <td class="text-right">' . number_format($rec->opening_balance, 0) . '</td>
                    <td class="text-right">' . number_format($rec->closing_balance, 0) . '</td>
                    <td class="text-right">' . number_format($rec->system_balance ?? 0, 0) . '</td>
                    <td class="text-right">' . number_format($rec->variance ?? 0, 0) . '</td>
                    <td>' . ucfirst($rec->status) . '</td>
                    <td>' . ($rec->reconciledBy->name ?? 'N/A') . '</td>
                </tr>';
            }
        } else {
            $html .= '<tr><td colspan="9" style="text-align:center;color:#999;">No cash reconciliations found.</td></tr>';
        }
        $html .= '</tbody></table>';
        
        // Gold Reconciliations
        $html .= '<h3 class="sub-title">Gold Reconciliations</h3>';
        $html .= '<table><thead><tr>
            <th>#</th><th>Branch</th><th>Date</th>
            <th class="text-right">Opening (g)</th>
            <th class="text-right">Closing (g)</th>
            <th class="text-right">Difference (g)</th>
            <th>Status</th>
            <th>Reconciled By</th>
        </tr></thead><tbody>';
        
        if ($goldReconciliations->count() > 0) {
            foreach ($goldReconciliations as $rec) {
                $diff = $rec->closing_weight - $rec->opening_weight;
                $diffClass = $diff != 0 ? 'style="color:#b91c1c;font-weight:bold;"' : 'style="color:#065f46;"';
                $html .= '<tr>
                    <td>#' . $rec->id . '</td>
                    <td>' . ($rec->branch->name ?? 'N/A') . '</td>
                    <td>' . $rec->reconciliation_date->format('d/m/Y') . '</td>
                    <td class="text-right">' . number_format($rec->opening_weight, 2) . '</td>
                    <td class="text-right">' . number_format($rec->closing_weight, 2) . '</td>
                    <td class="text-right" ' . $diffClass . '>' . number_format($diff, 2) . '</td>
                    <td>' . ($rec->verified_by ? 'Verified' : 'Pending') . '</td>
                    <td>' . ($rec->reconciledBy->name ?? 'N/A') . '</td>
                </tr>';
            }
        } else {
            $html .= '<tr><td colspan="8" style="text-align:center;color:#999;">No gold reconciliations found.</td></tr>';
        }
        $html .= '</tbody></table>';
        
        // Summary
        $html .= '<h3 class="sub-title">Summary</h3>';
        $html .= '<table><thead><tr>
            <th>Type</th>
            <th class="text-right">Total Closing</th>
            <th class="text-right">Total Variance/Difference</th>
            <th class="text-right">Count</th>
        </tr></thead><tbody>';
        $html .= '<tr>
            <td>Cash</td>
            <td class="text-right">TZS ' . number_format($cashReconciliations->sum('closing_balance'), 0) . '</td>
            <td class="text-right">TZS ' . number_format($cashReconciliations->sum('variance'), 0) . '</td>
            <td class="text-right">' . $cashReconciliations->count() . '</td>
        </tr>';
        $html .= '<tr>
            <td>Gold</td>
            <td class="text-right">' . number_format($goldReconciliations->sum('closing_weight'), 2) . ' g</td>
            <td class="text-right">' . number_format($goldReconciliations->sum(function($rec) {
                return $rec->closing_weight - $rec->opening_weight;
            }), 2) . ' g</td>
            <td class="text-right">' . $goldReconciliations->count() . '</td>
        </tr>';
        $html .= '</tbody></table>';
        
        $html .= '<p style="text-align:center;margin-top:20px;color:#999;font-size:10px;">Generated on ' . now()->format('d/m/Y H:i:s') . ' | Ngemba Mining Management System</p>';
        $html .= '</body></html>';
        
        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="reconciliations_' . date('Y-m-d') . '.xls"')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Export to PDF (print-friendly view)
     */
    public function exportPdf(Request $request)
    {
        $user = Auth::user();
        
        // Get reconciliations
        $cashQuery = CashReconciliation::with(['branch', 'reconciledBy']);
        if (!$this->canAccessAllBranches()) {
            $cashQuery->where('branch_id', $user->assigned_branch);
        }
        if ($request->filled('branch_id')) {
            $cashQuery->where('branch_id', $request->branch_id);
        }
        if ($request->filled('from_date')) {
            $cashQuery->whereDate('reconciliation_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $cashQuery->whereDate('reconciliation_date', '<=', $request->to_date);
        }
        $cashReconciliations = $cashQuery->orderBy('reconciliation_date', 'desc')->get();
        
        $goldQuery = GoldReconciliation::with(['branch', 'reconciledBy']);
        if (!$this->canAccessAllBranches()) {
            $goldQuery->where('branch_id', $user->assigned_branch);
        }
        if ($request->filled('branch_id')) {
            $goldQuery->where('branch_id', $request->branch_id);
        }
        if ($request->filled('from_date')) {
            $goldQuery->whereDate('reconciliation_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $goldQuery->whereDate('reconciliation_date', '<=', $request->to_date);
        }
        $goldReconciliations = $goldQuery->orderBy('reconciliation_date', 'desc')->get();
        
        $totalCashValue = $cashReconciliations->sum('closing_balance');
        $totalCashVariance = $cashReconciliations->sum('variance');
        $totalGoldWeight = $goldReconciliations->sum('closing_weight');
        $totalGoldDifference = $goldReconciliations->sum(function($rec) {
            return $rec->closing_weight - $rec->opening_weight;
        });
        
        return view('finance.reconciliations.export-pdf', compact(
            'cashReconciliations',
            'goldReconciliations',
            'totalCashValue',
            'totalCashVariance',
            'totalGoldWeight',
            'totalGoldDifference'
        ));
    }
}