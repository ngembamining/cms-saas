<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Branch;
use App\Models\Transaction;
use App\Models\Loan;
use App\Models\Expense;
use App\Models\BranchBalance;
use App\Models\User;
use Carbon\Carbon;

class BranchController extends Controller
{
    /**
     * Check if user is a branch manager
     */
    private function checkBranchManager()
    {
        $user = Auth::user();
        if ($user->role !== 'branch_manager' || !$user->assigned_branch) {
            abort(403, 'Unauthorized - Branch Manager access required');
        }
        return $user;
    }

    /**
     * Get the user's branch
     */
    private function getBranch($user)
    {
        return Branch::find($user->assigned_branch);
    }

    /**
     * Branch Dashboard - Overview
     */
    public function dashboard()
    {
        $user = $this->checkBranchManager();
        $branch = $this->getBranch($user);

        if (!$branch) {
            return redirect()->route('dashboard')->with('error', 'No branch assigned to you.');
        }

        // Get branch statistics
        $stats = $this->getBranchStats($branch->id);

        // Get recent transactions
        $recentTransactions = Transaction::where('branch_id', $branch->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get pending loans
        $pendingLoans = Loan::where('branch_id', $branch->id)
            ->where('status', 'pending')
            ->get();

        // Get staff count
        $staffCount = User::where('assigned_branch', $branch->id)->count();

        return view('branch.dashboard', compact(
            'branch',
            'stats',
            'recentTransactions',
            'pendingLoans',
            'staffCount'
        ));
    }

    /**
     * Branch Balance
     */
    public function balance()
    {
        $user = $this->checkBranchManager();
        $branch = $this->getBranch($user);

        if (!$branch) {
            return redirect()->route('dashboard')->with('error', 'No branch assigned to you.');
        }

        // Get branch balances
        $cashBalance = BranchBalance::where('branch_id', $branch->id)
            ->where('balance_type', 'cash')
            ->first();

        $goldBalance = BranchBalance::where('branch_id', $branch->id)
            ->where('balance_type', 'gold')
            ->first();

        // Get all directors
        $directors = User::where('role', 'director')->get();

        // Get recent transfers
        $recentTransfers = Transaction::where('branch_id', $branch->id)
            ->where('transaction_type', 'fund_transfer')
            ->where('description', 'LIKE', '%Balance Transfer%')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get balance history (last 30 days)
        $history = $this->getBalanceHistory($branch->id);

        return view('branch.balance', compact(
            'branch',
            'cashBalance',
            'goldBalance',
            'directors',
            'recentTransfers',
            'history'
        ));
    }

    /**
     * Send balance to director's bank account
     */
    public function sendBalance(Request $request)
    {
        $user = $this->checkBranchManager();
        $branch = $this->getBranch($user);

        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'No branch assigned to you.'
            ], 400);
        }

        $request->validate([
            'amount' => 'required|numeric|min:1000',
            'director_id' => 'required|exists:users,id',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'account_name' => 'required|string|max:255',
            'remarks' => 'nullable|string|max:500',
        ]);

        // Check if cash balance is sufficient
        $cashBalance = BranchBalance::where('branch_id', $branch->id)
            ->where('balance_type', 'cash')
            ->first();

        if (!$cashBalance || $cashBalance->amount < $request->amount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient cash balance. Available: ' . number_format($cashBalance->amount ?? 0, 2)
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Deduct from branch cash balance
            $cashBalance->amount -= $request->amount;
            $cashBalance->save();

            // Create transaction record
            $transaction = Transaction::create([
                'transaction_code' => 'BT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'branch_id' => $branch->id,
                'transaction_type' => 'fund_transfer',
                'amount' => $request->amount,
                'customer_id' => null,
                'supplier_id' => null,
                'loan_id' => null,
                'description' => "Balance Transfer to Director ({$request->bank_name})",
                'reference_no' => 'BT-' . time(),
                'payment_method' => 'bank_transfer',
                'payment_reference' => 'BT-' . time(),
                'recorded_by' => $user->id,
                'verified_by' => null,
                'status' => 'completed',
                'comments' => json_encode([
                    'director_id' => $request->director_id,
                    'bank_name' => $request->bank_name,
                    'account_number' => $request->account_number,
                    'account_name' => $request->account_name,
                    'remarks' => $request->remarks,
                    'date' => now()->toDateTimeString()
                ]),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Balance sent successfully!',
                'transaction' => $transaction
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to send balance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Branch Transactions
     */
    public function transactions(Request $request)
    {
        $user = $this->checkBranchManager();
        $branch = $this->getBranch($user);

        if (!$branch) {
            return redirect()->route('dashboard')->with('error', 'No branch assigned to you.');
        }

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $type = $request->input('type');

        $query = Transaction::where('branch_id', $branch->id)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($type) {
            $query->where('transaction_type', $type);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(20);

        // Summary
        $summary = [
            'total_sales' => Transaction::where('branch_id', $branch->id)
                ->where('transaction_type', 'gold_sale')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount'),
            'total_purchases' => Transaction::where('branch_id', $branch->id)
                ->where('transaction_type', 'gold_purchase')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount'),
            'total_transactions' => $query->count(),
        ];

        $transactionTypes = [
            'gold_purchase' => 'Gold Purchase',
            'gold_sale' => 'Gold Sale',
            'cash_deposit' => 'Cash Deposit',
            'cash_withdrawal' => 'Cash Withdrawal',
            'expense' => 'Expense',
            'loan_disbursement' => 'Loan Disbursement',
            'loan_repayment' => 'Loan Repayment',
        ];

        return view('branch.transactions', compact(
            'branch',
            'transactions',
            'summary',
            'startDate',
            'endDate',
            'type',
            'transactionTypes'
        ));
    }

    /**
     * Branch Staff
     */
    public function staff()
    {
        $user = $this->checkBranchManager();
        $branch = $this->getBranch($user);

        if (!$branch) {
            return redirect()->route('dashboard')->with('error', 'No branch assigned to you.');
        }

        $staff = User::where('assigned_branch', $branch->id)->get();

        // Staff statistics
        $stats = [
            'total' => $staff->count(),
            'by_role' => $staff->groupBy('role')->map(function ($group) {
                return $group->count();
            }),
        ];

        return view('branch.staff', compact('branch', 'staff', 'stats'));
    }

    /**
     * Branch Reports
     */
    public function reports(Request $request)
    {
        $user = $this->checkBranchManager();
        $branch = $this->getBranch($user);

        if (!$branch) {
            return redirect()->route('dashboard')->with('error', 'No branch assigned to you.');
        }

        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        // Monthly sales
        $monthlySales = Transaction::where('branch_id', $branch->id)
            ->where('transaction_type', 'gold_sale')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        // Monthly purchases
        $monthlyPurchases = Transaction::where('branch_id', $branch->id)
            ->where('transaction_type', 'gold_purchase')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        // Monthly expenses
        $monthlyExpenses = Expense::where('branch_id', $branch->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        // Daily summary for the month
        $dailySummary = [];
        for ($i = 1; $i <= $endDate->day; $i++) {
            $date = Carbon::create($year, $month, $i);
            $dailySummary[] = [
                'date' => $date->format('Y-m-d'),
                'sales' => Transaction::where('branch_id', $branch->id)
                    ->where('transaction_type', 'gold_sale')
                    ->whereDate('created_at', $date)
                    ->sum('amount'),
                'purchases' => Transaction::where('branch_id', $branch->id)
                    ->where('transaction_type', 'gold_purchase')
                    ->whereDate('created_at', $date)
                    ->sum('amount'),
                'expenses' => Expense::where('branch_id', $branch->id)
                    ->whereDate('created_at', $date)
                    ->sum('amount'),
            ];
        }

        $summary = [
            'total_sales' => $monthlySales->sum('amount'),
            'total_purchases' => $monthlyPurchases->sum('amount'),
            'total_expenses' => $monthlyExpenses->sum('amount'),
            'net_profit' => $monthlySales->sum('amount') - $monthlyExpenses->sum('amount'),
            'sales_count' => $monthlySales->count(),
            'purchases_count' => $monthlyPurchases->count(),
            'expenses_count' => $monthlyExpenses->count(),
        ];

        $months = range(1, 12);
        $years = range(Carbon::now()->year - 5, Carbon::now()->year);

        return view('branch.reports', compact(
            'branch',
            'summary',
            'dailySummary',
            'month',
            'year',
            'months',
            'years'
        ));
    }

    /**
     * Get branch statistics
     */
    private function getBranchStats($branchId)
    {
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        return [
            'total_gold' => BranchBalance::where('branch_id', $branchId)
                ->where('balance_type', 'gold')
                ->value('amount') ?? 0,
            'total_cash' => BranchBalance::where('branch_id', $branchId)
                ->where('balance_type', 'cash')
                ->value('amount') ?? 0,
            'monthly_sales' => Transaction::where('branch_id', $branchId)
                ->where('transaction_type', 'gold_sale')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('amount'),
            'monthly_purchases' => Transaction::where('branch_id', $branchId)
                ->where('transaction_type', 'gold_purchase')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('amount'),
            'monthly_expenses' => Expense::where('branch_id', $branchId)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('amount'),
            'pending_loans' => Loan::where('branch_id', $branchId)
                ->where('status', 'pending')
                ->count(),
            'today_transactions' => Transaction::where('branch_id', $branchId)
                ->whereDate('created_at', $today)
                ->count(),
            'total_transactions' => Transaction::where('branch_id', $branchId)->count(),
        ];
    }

    /**
     * Get balance history (last 30 days)
     */
    private function getBalanceHistory($branchId)
    {
        $history = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $history[] = [
                'date' => $date->format('Y-m-d'),
                'cash' => BranchBalance::where('branch_id', $branchId)
                    ->where('balance_type', 'cash')
                    ->value('amount') ?? 0,
                'gold' => BranchBalance::where('branch_id', $branchId)
                    ->where('balance_type', 'gold')
                    ->value('amount') ?? 0,
            ];
        }
        return $history;
    }
}