<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\Loan;
use App\Models\Expense;
use App\Models\Branch;
use App\Models\BranchBalance;
use App\Models\Supplier;
use App\Models\Customer;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Check if user has full access to all branches
     * Only Director and Accountant have full access
     */
    private function hasFullAccess($user)
    {
        return $user->role === 'director' || $user->role === 'accountant';
    }

    /**
     * Apply branch filter to query based on user role
     */
    private function applyBranchFilter($query, $user, $branchId = null)
    {
        if ($this->hasFullAccess($user)) {
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
        } else {
            $query->where('branch_id', $user->assigned_branch);
            if ($branchId && $branchId != $user->assigned_branch) {
                $query->where('branch_id', $user->assigned_branch);
            }
        }
        return $query;
    }

    /**
     * Get branches for filter dropdown based on user role
     */
    private function getAvailableBranches($user)
    {
        if ($this->hasFullAccess($user)) {
            return Branch::all();
        }
        return Branch::where('id', $user->assigned_branch)->get();
    }

    /**
     * Display the reports dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $branchFilter = $request->filled('branch_id') ? $request->branch_id : null;
        $fromDate = $request->filled('from_date') ? $request->from_date : now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->filled('to_date') ? $request->to_date : now()->format('Y-m-d');
        
        $transactionQuery = Transaction::query();
        $loanQuery = Loan::query();
        $expenseQuery = Expense::query();
        
        $this->applyBranchFilter($transactionQuery, $user, $branchFilter);
        $this->applyBranchFilter($loanQuery, $user, $branchFilter);
        $this->applyBranchFilter($expenseQuery, $user, $branchFilter);
        
        if ($fromDate && $toDate) {
            $transactionQuery->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
            $loanQuery->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
            $expenseQuery->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
        }
        
        $totalSales = (clone $transactionQuery)->where('transaction_type', 'gold_sale')->sum('amount');
        $totalPurchases = (clone $transactionQuery)->where('transaction_type', 'gold_purchase')->sum('amount');
        $totalExpenses = (clone $expenseQuery)->sum('amount');
        
        $balanceQuery = BranchBalance::query();
        if (!$this->hasFullAccess($user)) {
            $balanceQuery->where('branch_id', $user->assigned_branch);
        }
        if ($branchFilter && !$this->hasFullAccess($user)) {
            $balanceQuery->where('branch_id', $user->assigned_branch);
        } elseif ($branchFilter && $this->hasFullAccess($user)) {
            $balanceQuery->where('branch_id', $branchFilter);
        }
        
        $totalCash = (clone $balanceQuery)->where('balance_type', 'cash')->sum('amount');
        $totalGold = (clone $balanceQuery)->where('balance_type', 'gold')->sum('amount');
        
        $branches = $this->getAvailableBranches($user);
        
        return view('finance.reports.index', compact(
            'totalSales',
            'totalPurchases',
            'totalExpenses',
            'totalCash',
            'totalGold',
            'branches',
            'fromDate',
            'toDate',
            'branchFilter'
        ));
    }

    /**
     * Daily report.
     */
    public function daily(Request $request)
    {
        $user = Auth::user();
        $date = $request->filled('date') ? $request->date : now()->format('Y-m-d');
        
        $transactionQuery = Transaction::with(['branch', 'user']);
        $expenseQuery = Expense::with(['branch', 'category']);
        
        $this->applyBranchFilter($transactionQuery, $user);
        $this->applyBranchFilter($expenseQuery, $user);
        
        $transactionQuery->whereDate('created_at', $date);
        $expenseQuery->whereDate('created_at', $date);
        
        $transactions = $transactionQuery->orderBy('created_at', 'desc')->get();
        $expenses = $expenseQuery->orderBy('created_at', 'desc')->get();
        
        $totalSales = $transactionQuery->where('transaction_type', 'gold_sale')->sum('amount');
        $totalPurchases = $transactionQuery->where('transaction_type', 'gold_purchase')->sum('amount');
        $totalExpenses = $expenseQuery->sum('amount');
        
        return view('finance.reports.daily', compact(
            'transactions',
            'expenses',
            'totalSales',
            'totalPurchases',
            'totalExpenses',
            'date'
        ));
    }

    /**
     * Weekly report.
     */
    public function weekly(Request $request)
    {
        $user = Auth::user();
        $startDate = $request->filled('start_date') ? $request->start_date : now()->startOfWeek()->format('Y-m-d');
        $endDate = $request->filled('end_date') ? $request->end_date : now()->endOfWeek()->format('Y-m-d');
        
        $transactionQuery = Transaction::with(['branch', 'user']);
        $this->applyBranchFilter($transactionQuery, $user);
        
        $transactionQuery->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        $transactions = $transactionQuery->orderBy('created_at', 'desc')->get();
        
        $totalSales = $transactionQuery->where('transaction_type', 'gold_sale')->sum('amount');
        $totalPurchases = $transactionQuery->where('transaction_type', 'gold_purchase')->sum('amount');
        
        $dailyData = [];
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime($startDate . ' +' . $i . ' days'));
            $salesQuery = Transaction::where('transaction_type', 'gold_sale')->whereDate('created_at', $date);
            $purchasesQuery = Transaction::where('transaction_type', 'gold_purchase')->whereDate('created_at', $date);
            
            if (!$this->hasFullAccess($user)) {
                $salesQuery->where('branch_id', $user->assigned_branch);
                $purchasesQuery->where('branch_id', $user->assigned_branch);
            }
            
            $dailyData[] = [
                'date' => $date,
                'sales' => $salesQuery->sum('amount'),
                'purchases' => $purchasesQuery->sum('amount'),
            ];
        }
        
        return view('finance.reports.weekly', compact(
            'transactions',
            'totalSales',
            'totalPurchases',
            'startDate',
            'endDate',
            'dailyData'
        ));
    }

    /**
     * Monthly report.
     */
    public function monthly(Request $request)
    {
        $user = Auth::user();
        $month = $request->filled('month') ? $request->month : now()->format('Y-m');
        
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $transactionQuery = Transaction::with(['branch', 'user']);
        $this->applyBranchFilter($transactionQuery, $user);
        
        $transactionQuery->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        $transactions = $transactionQuery->orderBy('created_at', 'desc')->get();
        
        $totalSales = $transactionQuery->where('transaction_type', 'gold_sale')->sum('amount');
        $totalPurchases = $transactionQuery->where('transaction_type', 'gold_purchase')->sum('amount');
        
        $dailyData = [];
        $daysInMonth = date('t', strtotime($startDate));
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $date = $month . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);
            $salesQuery = Transaction::where('transaction_type', 'gold_sale')->whereDate('created_at', $date);
            $purchasesQuery = Transaction::where('transaction_type', 'gold_purchase')->whereDate('created_at', $date);
            
            if (!$this->hasFullAccess($user)) {
                $salesQuery->where('branch_id', $user->assigned_branch);
                $purchasesQuery->where('branch_id', $user->assigned_branch);
            }
            
            $dailyData[] = [
                'date' => $date,
                'sales' => $salesQuery->sum('amount'),
                'purchases' => $purchasesQuery->sum('amount'),
            ];
        }
        
        return view('finance.reports.monthly', compact(
            'transactions',
            'totalSales',
            'totalPurchases',
            'month',
            'startDate',
            'endDate',
            'dailyData'
        ));
    }

    /**
     * Branch report.
     */
    public function branch(Request $request)
    {
        $user = Auth::user();
        
        if ($this->hasFullAccess($user)) {
            $branches = Branch::with(['balances'])->get();
        } else {
            $branches = Branch::with(['balances'])->where('id', $user->assigned_branch)->get();
        }
        
        $branchData = [];
        
        foreach ($branches as $branch) {
            $cash = $branch->balances->where('balance_type', 'cash')->first();
            $gold = $branch->balances->where('balance_type', 'gold')->first();
            
            $branchData[] = [
                'branch' => $branch,
                'cash' => $cash->amount ?? 0,
                'gold' => $gold->amount ?? 0,
                'transactions' => Transaction::where('branch_id', $branch->id)->count(),
                'sales' => Transaction::where('branch_id', $branch->id)->where('transaction_type', 'gold_sale')->sum('amount'),
                'purchases' => Transaction::where('branch_id', $branch->id)->where('transaction_type', 'gold_purchase')->sum('amount'),
            ];
        }
        
        return view('finance.reports.branch', compact('branchData'));
    }

    /**
     * Gold summary report.
     */
    public function goldSummary(Request $request)
    {
        $user = Auth::user();
        
        $transactionQuery = Transaction::with(['branch']);
        $this->applyBranchFilter($transactionQuery, $user);
        
        $purchases = (clone $transactionQuery)
            ->where('transaction_type', 'gold_purchase')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();
            
        $sales = (clone $transactionQuery)
            ->where('transaction_type', 'gold_sale')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();
        
        $totalGoldPurchasedQuery = Transaction::where('transaction_type', 'gold_purchase');
        $totalGoldSoldQuery = Transaction::where('transaction_type', 'gold_sale');
        
        if (!$this->hasFullAccess($user)) {
            $totalGoldPurchasedQuery->where('branch_id', $user->assigned_branch);
            $totalGoldSoldQuery->where('branch_id', $user->assigned_branch);
        }
        
        $totalGoldPurchased = $totalGoldPurchasedQuery->sum('amount');
        $totalGoldSold = $totalGoldSoldQuery->sum('amount');
        
        return view('finance.reports.gold', compact(
            'purchases',
            'sales',
            'totalGoldPurchased',
            'totalGoldSold'
        ));
    }

    /**
     * Cash flow report.
     */
    public function cashFlow(Request $request)
    {
        $user = Auth::user();
        
        $fromDate = $request->filled('from_date') ? $request->from_date : now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->filled('to_date') ? $request->to_date : now()->format('Y-m-d');
        
        $transactionQuery = Transaction::with(['branch']);
        $this->applyBranchFilter($transactionQuery, $user);
        
        $transactionQuery->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
        
        $inflows = (clone $transactionQuery)
            ->whereIn('transaction_type', ['gold_sale', 'cash_deposit'])
            ->sum('amount');
            
        $outflows = (clone $transactionQuery)
            ->whereIn('transaction_type', ['gold_purchase', 'cash_withdrawal', 'expense', 'loan_disbursement'])
            ->sum('amount');
        
        $netCashFlow = $inflows - $outflows;
        
        $dailyFlow = [];
        $start = new \DateTime($fromDate);
        $end = new \DateTime($toDate);
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($start, $interval, $end->modify('+1 day'));
        
        foreach ($period as $date) {
            $day = $date->format('Y-m-d');
            
            $dailyInflowsQuery = Transaction::whereIn('transaction_type', ['gold_sale', 'cash_deposit'])->whereDate('created_at', $day);
            $dailyOutflowsQuery = Transaction::whereIn('transaction_type', ['gold_purchase', 'cash_withdrawal', 'expense', 'loan_disbursement'])->whereDate('created_at', $day);
            
            if (!$this->hasFullAccess($user)) {
                $dailyInflowsQuery->where('branch_id', $user->assigned_branch);
                $dailyOutflowsQuery->where('branch_id', $user->assigned_branch);
            }
            
            $dailyFlow[] = [
                'date' => $day,
                'inflows' => $dailyInflowsQuery->sum('amount'),
                'outflows' => $dailyOutflowsQuery->sum('amount'),
                'net' => $dailyInflowsQuery->sum('amount') - $dailyOutflowsQuery->sum('amount'),
            ];
        }
        
        return view('finance.reports.cash-flow', compact(
            'inflows',
            'outflows',
            'netCashFlow',
            'dailyFlow',
            'fromDate',
            'toDate'
        ));
    }

    /**
     * Profit & Loss report.
     */
    public function profitLoss(Request $request)
    {
        $user = Auth::user();
        
        $fromDate = $request->filled('from_date') ? $request->from_date : now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->filled('to_date') ? $request->to_date : now()->format('Y-m-d');
        
        $transactionQuery = Transaction::query();
        $this->applyBranchFilter($transactionQuery, $user);
        
        $transactionQuery->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
        
        $revenue = (clone $transactionQuery)->where('transaction_type', 'gold_sale')->sum('amount');
        $costOfGoods = (clone $transactionQuery)->where('transaction_type', 'gold_purchase')->sum('amount');
        
        $expenseQuery = Expense::query();
        $this->applyBranchFilter($expenseQuery, $user);
        $expenses = $expenseQuery->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])->sum('amount');
        
        $grossProfit = $revenue - $costOfGoods;
        $netProfit = $grossProfit - $expenses;
        
        return view('finance.reports.profit-loss', compact(
            'revenue',
            'costOfGoods',
            'expenses',
            'grossProfit',
            'netProfit',
            'fromDate',
            'toDate'
        ));
    }

    /**
     * Balance Sheet report.
     */
    public function balanceSheet(Request $request)
    {
        $user = Auth::user();
        
        $asAtDate = $request->filled('as_at_date') ? $request->as_at_date : now()->format('Y-m-d');
        
        $balanceQuery = BranchBalance::query();
        if (!$this->hasFullAccess($user)) {
            $balanceQuery->where('branch_id', $user->assigned_branch);
        }
        
        $totalCash = (clone $balanceQuery)->where('balance_type', 'cash')->sum('amount');
        $totalGold = (clone $balanceQuery)->where('balance_type', 'gold')->sum('amount');
        
        $loanQuery = Loan::where('status', 'approved');
        if (!$this->hasFullAccess($user)) {
            $loanQuery->where('branch_id', $user->assigned_branch);
        }
        $totalLoans = $loanQuery->sum('amount');
        
        $revenueQuery = Transaction::where('transaction_type', 'gold_sale');
        $costQuery = Transaction::where('transaction_type', 'gold_purchase');
        $expenseQuery = Expense::query();
        
        if (!$this->hasFullAccess($user)) {
            $revenueQuery->where('branch_id', $user->assigned_branch);
            $costQuery->where('branch_id', $user->assigned_branch);
            $expenseQuery->where('branch_id', $user->assigned_branch);
        }
        
        $totalRevenue = $revenueQuery->sum('amount');
        $totalCost = $costQuery->sum('amount');
        $totalExpenses = $expenseQuery->sum('amount');
        $equity = $totalRevenue - $totalCost - $totalExpenses;
        
        $totalAssets = $totalCash + $totalGold;
        $totalLiabilitiesEquity = $totalLoans + $equity;
        
        return view('finance.reports.balance-sheet', compact(
            'totalCash',
            'totalGold',
            'totalAssets',
            'totalLoans',
            'equity',
            'totalLiabilitiesEquity',
            'asAtDate'
        ));
    }

    /**
     * Export report.
     */
    public function export($type, Request $request)
    {
        return redirect()->back()
            ->with('info', 'ℹ️ Export functionality coming soon!');
    }

    // ============================================
    // ✅ REPORTS MODULE METHODS
    // ============================================

    /**
     * Sales Report - Detailed sales analysis
     * FIXED: Gets gold weight from transaction_items table
     */
    public function salesReport(Request $request)
    {
        $user = Auth::user();
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $branchId = $request->input('branch_id');
        $customerId = $request->input('customer_id');

        $query = Transaction::where('transaction_type', 'gold_sale')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate]);

        $this->applyBranchFilter($query, $user, $branchId);

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        $sales = $query->with(['branch', 'customer', 'items'])->get();

        // Calculate total gold from items
        $totalGold = 0;
        foreach ($sales as $sale) {
            if ($sale->items && $sale->items->count() > 0) {
                $totalGold += $sale->items->sum('weight');
            } elseif (isset($sale->gold_amount) && $sale->gold_amount > 0) {
                $totalGold += $sale->gold_amount;
            }
        }

        $summary = [
            'total_sales' => $sales->sum('amount'),
            'total_gold' => $totalGold,
            'total_transactions' => $sales->count(),
            'average_sale' => $sales->avg('amount'),
            'max_sale' => $sales->max('amount'),
            'min_sale' => $sales->min('amount'),
        ];

        $byBranch = $sales->groupBy('branch_id')->map(function ($items) {
            $goldTotal = 0;
            foreach ($items as $item) {
                if ($item->items && $item->items->count() > 0) {
                    $goldTotal += $item->items->sum('weight');
                } elseif (isset($item->gold_amount) && $item->gold_amount > 0) {
                    $goldTotal += $item->gold_amount;
                }
            }
            return [
                'branch' => $items->first()->branch->name ?? 'Unknown',
                'total' => $items->sum('amount'),
                'gold' => $goldTotal,
                'count' => $items->count(),
            ];
        })->values();

        $byCustomer = $sales->groupBy('customer_id')->map(function ($items) {
            $goldTotal = 0;
            foreach ($items as $item) {
                if ($item->items && $item->items->count() > 0) {
                    $goldTotal += $item->items->sum('weight');
                } elseif (isset($item->gold_amount) && $item->gold_amount > 0) {
                    $goldTotal += $item->gold_amount;
                }
            }
            return [
                'customer' => $items->first()->customer->name ?? 'Unknown',
                'total' => $items->sum('amount'),
                'gold' => $goldTotal,
                'count' => $items->count(),
            ];
        })->values();

        $dailyTrends = $sales->groupBy(function ($item) {
            return $item->created_at->format('Y-m-d');
        })->map(function ($items) {
            $goldTotal = 0;
            foreach ($items as $item) {
                if ($item->items && $item->items->count() > 0) {
                    $goldTotal += $item->items->sum('weight');
                } elseif (isset($item->gold_amount) && $item->gold_amount > 0) {
                    $goldTotal += $item->gold_amount;
                }
            }
            return [
                'date' => $items->first()->created_at->format('Y-m-d'),
                'amount' => $items->sum('amount'),
                'gold' => $goldTotal,
                'count' => $items->count(),
            ];
        })->values();

        $branches = $this->getAvailableBranches($user);
        $customers = Customer::all();

        return view('reports.sales', compact(
            'sales', 'summary', 'byBranch', 'byCustomer', 'dailyTrends',
            'branches', 'customers', 'startDate', 'endDate', 'branchId', 'customerId'
        ));
    }

    /**
     * Purchases Report - Detailed purchase analysis
     * FIXED: Gets gold weight from transaction_items table
     */
    public function purchasesReport(Request $request)
    {
        $user = Auth::user();
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $branchId = $request->input('branch_id');
        $supplierId = $request->input('supplier_id');

        $query = Transaction::where('transaction_type', 'gold_purchase')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate]);

        $this->applyBranchFilter($query, $user, $branchId);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $purchases = $query->with(['branch', 'supplier', 'items'])->get();

        // Calculate total gold from items
        $totalGold = 0;
        foreach ($purchases as $purchase) {
            if ($purchase->items && $purchase->items->count() > 0) {
                $totalGold += $purchase->items->sum('weight');
            } elseif (isset($purchase->gold_amount) && $purchase->gold_amount > 0) {
                $totalGold += $purchase->gold_amount;
            }
        }

        $summary = [
            'total_purchases' => $purchases->sum('amount'),
            'total_gold' => $totalGold,
            'total_transactions' => $purchases->count(),
            'average_purchase' => $purchases->avg('amount'),
            'max_purchase' => $purchases->max('amount'),
            'min_purchase' => $purchases->min('amount'),
        ];

        $byBranch = $purchases->groupBy('branch_id')->map(function ($items) {
            $goldTotal = 0;
            foreach ($items as $item) {
                if ($item->items && $item->items->count() > 0) {
                    $goldTotal += $item->items->sum('weight');
                } elseif (isset($item->gold_amount) && $item->gold_amount > 0) {
                    $goldTotal += $item->gold_amount;
                }
            }
            return [
                'branch' => $items->first()->branch->name ?? 'Unknown',
                'total' => $items->sum('amount'),
                'gold' => $goldTotal,
                'count' => $items->count(),
            ];
        })->values();

        $bySupplier = $purchases->groupBy('supplier_id')->map(function ($items) {
            $goldTotal = 0;
            foreach ($items as $item) {
                if ($item->items && $item->items->count() > 0) {
                    $goldTotal += $item->items->sum('weight');
                } elseif (isset($item->gold_amount) && $item->gold_amount > 0) {
                    $goldTotal += $item->gold_amount;
                }
            }
            return [
                'supplier' => $items->first()->supplier->name ?? 'Unknown',
                'total' => $items->sum('amount'),
                'gold' => $goldTotal,
                'count' => $items->count(),
            ];
        })->values();

        $branches = $this->getAvailableBranches($user);
        $suppliers = Supplier::all();

        return view('reports.purchases', compact(
            'purchases', 'summary', 'byBranch', 'bySupplier',
            'branches', 'suppliers', 'startDate', 'endDate', 'branchId', 'supplierId'
        ));
    }

    /**
     * Expenses Report - Detailed expense analysis
     */
    public function expensesReport(Request $request)
    {
        $user = Auth::user();
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $branchId = $request->input('branch_id');
        $category = $request->input('category');

        $query = Expense::whereBetween('created_at', [$startDate, $endDate]);

        $this->applyBranchFilter($query, $user, $branchId);

        if ($category) {
            $query->where('category', $category);
        }

        $expenses = $query->with(['branch'])->get();

        $summary = [
            'total_expenses' => $expenses->sum('amount'),
            'total_transactions' => $expenses->count(),
            'average_expense' => $expenses->avg('amount'),
            'max_expense' => $expenses->max('amount'),
            'min_expense' => $expenses->min('amount'),
        ];

        $byCategory = $expenses->groupBy('category')->map(function ($items) {
            return [
                'category' => $items->first()->category ?? 'Other',
                'total' => $items->sum('amount'),
                'count' => $items->count(),
            ];
        })->values();

        $byBranch = $expenses->groupBy('branch_id')->map(function ($items) {
            return [
                'branch' => $items->first()->branch->name ?? 'Unknown',
                'total' => $items->sum('amount'),
                'count' => $items->count(),
            ];
        })->values();

        $categories = Expense::distinct()->pluck('category');
        $branches = $this->getAvailableBranches($user);

        return view('reports.expenses', compact(
            'expenses', 'summary', 'byCategory', 'byBranch',
            'categories', 'branches', 'startDate', 'endDate', 'branchId', 'category'
        ));
    }

    /**
     * ✅ LOANS REPORT - Detailed loans analysis (ACCURATE - uses ALL records for calculations)
     */
    public function loansReport(Request $request)
    {
        $user = Auth::user();
        
        // Get filter parameters
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $branchId = $request->input('branch_id');
        $status = $request->input('status');
        $supplierId = $request->input('supplier_id');

        // Build BASE query (without pagination) for calculations
        $baseQuery = Loan::with(['branch', 'supplier', 'payments'])
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);

        // Apply branch filter
        $this->applyBranchFilter($baseQuery, $user, $branchId);

        // Apply other filters
        if ($status) {
            $baseQuery->where('status', $status);
        }

        if ($supplierId) {
            $baseQuery->where('suppliers_id', $supplierId);
        }

        // ============================================
        // ✅ GET ALL LOANS FOR CALCULATIONS
        // ============================================
        $allLoans = (clone $baseQuery)->get();
        
        // ============================================
        // ✅ SEPARATE ACTIVE VS COMPLETED LOANS
        // ============================================
        $activeLoans = $allLoans->filter(function($loan) {
            return in_array($loan->status, ['pending', 'approved']);
        });
        
        $completedLoans = $allLoans->filter(function($loan) {
            return $loan->status === 'completed';
        });
        
        // ============================================
        // ✅ CALCULATIONS
        // ============================================
        
        // 1. Total Loans - Count of ALL loans
        $totalLoans = $allLoans->count();
        
        // 2. Amount Loaned Principal - ONLY ACTIVE LOANS (principal still at risk)
        $principalAmount = $activeLoans->sum('amount');
        
        // 3. Total Payments - All payments from ALL loans
        $totalPayments = $allLoans->sum(function($loan) {
            return $loan->payments->sum('amount');
        });
        
        // 4. Interest Amount Expected - ONLY ACTIVE LOANS (expected future interest)
        $expectedInterest = $activeLoans->sum(function($loan) {
            // Full interest expected for the entire duration
            return $loan->amount * ($loan->interest_rate / 100) * ($loan->duration_months / 12);
        });
        
        // 5. Total Paid (Active) - Payments received from ACTIVE loans only
        $activePayments = $activeLoans->sum(function($loan) {
            return $loan->payments->sum('amount');
        });
        
        // 6. Total Interest Collected - From ALL loans (completed + partial)
        $interestCollected = $allLoans->sum(function($loan) {
            $paid = $loan->payments->sum('amount');
            $totalInterestExpected = $loan->amount * ($loan->interest_rate / 100) * ($loan->duration_months / 12);
            return min($paid, $totalInterestExpected);
        });
        
        // 7. Outstanding Balance - Remaining principal from ALL loans
        $outstandingBalance = $allLoans->sum('amount') - $totalPayments;
        
        // 8. Other metrics
        $pendingApprovals = $allLoans->where('status', 'pending')->count();
        $activeCount = $activeLoans->count();
        $completedCount = $completedLoans->count();
        $rejectedLoans = $allLoans->where('status', 'rejected')->count();
        $avgInterest = $allLoans->avg('interest_rate') ?? 0;
        $avgDuration = $allLoans->avg('duration_months') ?? 0;
        $totalSuppliers = $allLoans->unique('suppliers_id')->count();

        $summary = [
            'total_loans' => $totalLoans,
            'principal_amount' => $principalAmount,
            'total_payments' => $totalPayments,
            'expected_interest' => $expectedInterest,
            'active_payments' => $activePayments,
            'interest_collected' => $interestCollected,
            'pending_approvals' => $pendingApprovals,
            'active_loans' => $activeCount,
            'completed_loans' => $completedCount,
            'rejected_loans' => $rejectedLoans,
            'avg_interest' => $avgInterest,
            'outstanding_balance' => $outstandingBalance,
            'avg_duration' => $avgDuration,
            'total_suppliers' => $totalSuppliers,
        ];

        // ============================================
        // ✅ GET PAGINATED LOANS FOR TABLE DISPLAY
        // ============================================
        
        // Clone the base query for pagination
        $paginatedQuery = clone $baseQuery;
        $loans = $paginatedQuery->latest()->paginate(20);

        // Get loans by branch (using ALL loans)
        $byBranch = $allLoans->groupBy('branch_id')->map(function($branchLoans) {
            $branch = $branchLoans->first()->branch;
            return [
                'branch' => $branch ? $branch->name : 'N/A',
                'total' => $branchLoans->sum('amount'),
                'count' => $branchLoans->count(),
                'avg_interest' => $branchLoans->avg('interest_rate'),
            ];
        })->values();

        // Get loans by supplier (using ALL loans)
        $bySupplier = $allLoans->groupBy('suppliers_id')->map(function($supplierLoans) {
            $supplier = $supplierLoans->first()->supplier;
            return [
                'supplier' => $supplier ? $supplier->name : 'N/A',
                'total' => $supplierLoans->sum('amount'),
                'count' => $supplierLoans->count(),
            ];
        })->sortByDesc('total')->values();

        // Get branches and suppliers for filter dropdowns
        $branches = $this->getAvailableBranches($user);
        $suppliers = Supplier::all();

        return view('reports.loans', compact(
            'loans',
            'summary',
            'byBranch',
            'bySupplier',
            'branches',
            'suppliers',
            'startDate',
            'endDate',
            'branchId',
            'status',
            'supplierId'
        ));
    }

    /**
     * Print Loans Report (PDF/Printable version) - ACCURATE calculations
     */
    public function printLoansReport(Request $request)
    {
        $user = Auth::user();
        
        // Get filter parameters
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $branchId = $request->input('branch_id');
        $status = $request->input('status');
        $supplierId = $request->input('supplier_id');

        // Build query - SAME as loansReport() base query
        $query = Loan::with(['branch', 'supplier', 'payments'])
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);

        $this->applyBranchFilter($query, $user, $branchId);

        if ($status) {
            $query->where('status', $status);
        }

        if ($supplierId) {
            $query->where('suppliers_id', $supplierId);
        }

        // Get ALL loans (no pagination for print)
        $allLoans = $query->get();
        
        // ============================================
        // ✅ SEPARATE ACTIVE VS COMPLETED LOANS
        // ============================================
        $activeLoans = $allLoans->filter(function($loan) {
            return in_array($loan->status, ['pending', 'approved']);
        });
        
        // ============================================
        // ✅ CALCULATIONS
        // ============================================
        
        $totalLoans = $allLoans->count();
        $principalAmount = $activeLoans->sum('amount');
        $totalPayments = $allLoans->sum(function($loan) {
            return $loan->payments->sum('amount');
        });
        $expectedInterest = $activeLoans->sum(function($loan) {
            return $loan->amount * ($loan->interest_rate / 100) * ($loan->duration_months / 12);
        });
        $activePayments = $activeLoans->sum(function($loan) {
            return $loan->payments->sum('amount');
        });
        $interestCollected = $allLoans->sum(function($loan) {
            $paid = $loan->payments->sum('amount');
            $totalInterestExpected = $loan->amount * ($loan->interest_rate / 100) * ($loan->duration_months / 12);
            return min($paid, $totalInterestExpected);
        });
        $pendingApprovals = $allLoans->where('status', 'pending')->count();
        $activeCount = $activeLoans->count();
        $completedLoans = $allLoans->where('status', 'completed')->count();
        $rejectedLoans = $allLoans->where('status', 'rejected')->count();
        $avgInterest = $allLoans->avg('interest_rate') ?? 0;

        $summary = [
            'total_loans' => $totalLoans,
            'principal_amount' => $principalAmount,
            'total_payments' => $totalPayments,
            'expected_interest' => $expectedInterest,
            'active_payments' => $activePayments,
            'interest_collected' => $interestCollected,
            'pending_approvals' => $pendingApprovals,
            'active_loans' => $activeCount,
            'completed_loans' => $completedLoans,
            'rejected_loans' => $rejectedLoans,
            'avg_interest' => $avgInterest,
        ];

        return view('reports.loans-print', compact(
            'allLoans',
            'summary',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Export Loans Report to Excel/CSV
     */
    public function exportLoansReport(Request $request)
    {
        $user = Auth::user();
        
        // Get filter parameters
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $branchId = $request->input('branch_id');
        $status = $request->input('status');
        $supplierId = $request->input('supplier_id');

        // Build query
        $query = Loan::with(['branch', 'supplier', 'payments'])
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);

        $this->applyBranchFilter($query, $user, $branchId);

        if ($status) {
            $query->where('status', $status);
        }

        if ($supplierId) {
            $query->where('suppliers_id', $supplierId);
        }

        $loans = $query->get();

        if ($loans->isEmpty()) {
            return redirect()->back()->with('error', 'No loans found to export.');
        }

        $filename = 'loans_report_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function() use ($loans) {
            $file = fopen('php://output', 'w');
            
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($file, [
                'Loan ID',
                'Branch',
                'Supplier',
                'Amount (TZS)',
                'Interest Rate (%)',
                'Interest Amount (TZS)',
                'Duration (Months)',
                'Status',
                'Paid Amount (TZS)',
                'Outstanding (TZS)',
                'Created Date'
            ]);

            foreach ($loans as $loan) {
                $paid = $loan->payments->sum('amount');
                $interestAmount = $loan->amount * ($loan->interest_rate / 100) * ($loan->duration_months / 12);
                
                fputcsv($file, [
                    $loan->id,
                    $loan->branch->name ?? 'N/A',
                    $loan->supplier->name ?? 'N/A',
                    number_format($loan->amount, 2),
                    $loan->interest_rate,
                    number_format($interestAmount, 2),
                    $loan->duration_months,
                    ucfirst($loan->status),
                    number_format($paid, 2),
                    number_format($loan->amount - $paid, 2),
                    $loan->created_at ? $loan->created_at->format('Y-m-d') : '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * General Report - Monthly business overview
     */
    public function generalReport(Request $request)
    {
        $user = Auth::user();
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);
        $branchId = $request->input('branch_id');

        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        $salesQuery = Transaction::where('transaction_type', 'gold_sale')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate]);
        $this->applyBranchFilter($salesQuery, $user, $branchId);
        $sales = $salesQuery->get();

        $purchasesQuery = Transaction::where('transaction_type', 'gold_purchase')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate]);
        $this->applyBranchFilter($purchasesQuery, $user, $branchId);
        $purchases = $purchasesQuery->get();

        $expensesQuery = Expense::whereBetween('created_at', [$startDate, $endDate]);
        $this->applyBranchFilter($expensesQuery, $user, $branchId);
        $expenses = $expensesQuery->get();

        $loansQuery = Loan::whereBetween('created_at', [$startDate, $endDate]);
        $this->applyBranchFilter($loansQuery, $user, $branchId);
        $loans = $loansQuery->get();

        $data = [
            'sales' => [
                'total' => $sales->sum('amount'),
                'gold' => $sales->sum('gold_amount'),
                'count' => $sales->count(),
            ],
            'purchases' => [
                'total' => $purchases->sum('amount'),
                'gold' => $purchases->sum('gold_amount'),
                'count' => $purchases->count(),
            ],
            'expenses' => [
                'total' => $expenses->sum('amount'),
                'count' => $expenses->count(),
            ],
            'loans' => [
                'total' => $loans->sum('amount'),
                'count' => $loans->count(),
                'pending' => $loans->where('status', 'pending')->count(),
                'approved' => $loans->where('status', 'approved')->count(),
            ],
            'profit' => $sales->sum('amount') - $expenses->sum('amount'),
        ];

        $months = range(1, 12);
        $years = range(Carbon::now()->year - 5, Carbon::now()->year);
        $branches = $this->getAvailableBranches($user);

        return view('reports.general', compact(
            'data', 'month', 'year', 'branchId', 'months', 'years', 'branches'
        ));
    }

    /**
     * Export Report - CSV Export
     */
    public function exportReport(Request $request)
    {
        $type = $request->input('type', 'sales');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $format = $request->input('format', 'csv');

        switch ($type) {
            case 'sales':
                $data = $this->exportSalesData($startDate, $endDate);
                break;
            case 'purchases':
                $data = $this->exportPurchasesData($startDate, $endDate);
                break;
            case 'expenses':
                $data = $this->exportExpensesData($startDate, $endDate);
                break;
            case 'loans':
                return $this->exportLoansReport($request);
            default:
                return redirect()->back()->with('error', 'Invalid report type');
        }

        if ($format === 'csv') {
            return $this->exportCSV($data, $type . '_report_' . date('Y-m-d'));
        }

        return redirect()->back()->with('error', 'Format not supported yet');
    }

    /**
     * Export Sales Data
     */
    protected function exportSalesData($startDate, $endDate)
    {
        $user = Auth::user();
        $query = Transaction::where('transaction_type', 'gold_sale')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate]);
        
        $this->applyBranchFilter($query, $user);
        
        $sales = $query->with(['branch', 'customer', 'items'])->get();
        
        return $sales->map(function ($item) {
            // Calculate gold from items
            $goldWeight = 0;
            if ($item->items && $item->items->count() > 0) {
                $goldWeight = $item->items->sum('weight');
            } elseif (isset($item->gold_amount) && $item->gold_amount > 0) {
                $goldWeight = $item->gold_amount;
            }
            
            return [
                'Date' => $item->created_at->format('Y-m-d'),
                'Branch' => $item->branch->name ?? 'N/A',
                'Customer' => $item->customer->name ?? 'N/A',
                'Amount' => $item->amount,
                'Gold (g)' => $goldWeight,
                'Status' => $item->status,
            ];
        })->toArray();
    }

    /**
     * Export Purchases Data
     */
    protected function exportPurchasesData($startDate, $endDate)
    {
        $user = Auth::user();
        $query = Transaction::where('transaction_type', 'gold_purchase')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate]);
        
        $this->applyBranchFilter($query, $user);
        
        $purchases = $query->with(['branch', 'supplier', 'items'])->get();
        
        return $purchases->map(function ($item) {
            // Calculate gold from items
            $goldWeight = 0;
            if ($item->items && $item->items->count() > 0) {
                $goldWeight = $item->items->sum('weight');
            } elseif (isset($item->gold_amount) && $item->gold_amount > 0) {
                $goldWeight = $item->gold_amount;
            }
            
            return [
                'Date' => $item->created_at->format('Y-m-d'),
                'Branch' => $item->branch->name ?? 'N/A',
                'Supplier' => $item->supplier->name ?? 'N/A',
                'Amount' => $item->amount,
                'Gold (g)' => $goldWeight,
                'Status' => $item->status,
            ];
        })->toArray();
    }

    /**
     * Export Expenses Data
     */
    protected function exportExpensesData($startDate, $endDate)
    {
        $user = Auth::user();
        $query = Expense::whereBetween('created_at', [$startDate, $endDate]);
        
        $this->applyBranchFilter($query, $user);
        
        return $query->with(['branch'])
            ->get()
            ->map(function ($item) {
                return [
                    'Date' => $item->created_at->format('Y-m-d'),
                    'Branch' => $item->branch->name ?? 'N/A',
                    'Category' => $item->category ?? 'Other',
                    'Amount' => $item->amount,
                    'Description' => $item->description ?? '',
                ];
            })
            ->toArray();
    }

    /**
     * Export as CSV
     */
    protected function exportCSV($data, $filename)
    {
        if (empty($data)) {
            return redirect()->back()->with('error', 'No data to export');
        }

        $headers = array_keys((array) $data[0]);
        
        $csv = fopen('php://temp', 'w+');
        fprintf($csv, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($csv, $headers);

        foreach ($data as $row) {
            fputcsv($csv, (array) $row);
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return response($content)
            ->withHeaders([
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename={$filename}.csv",
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);
    }
}