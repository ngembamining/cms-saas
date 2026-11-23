<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Branch;
use App\Models\Transaction;
use App\Models\Loan;
use App\Models\Expense;
use App\Models\BranchBalance;
use App\Models\User;
use App\Models\CashReconciliation;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Show the dashboard with role-based metrics.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $role = $user->role;

        // Get period from request
        $period = $request->input('period', 'month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Calculate date range
        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
            $periodLabel = $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y');
        } else {
            switch ($period) {
                case 'today':
                    $start = Carbon::today()->startOfDay();
                    $end = Carbon::today()->endOfDay();
                    $periodLabel = 'Today';
                    break;
                case 'week':
                    $start = Carbon::now()->startOfWeek();
                    $end = Carbon::now()->endOfWeek();
                    $periodLabel = 'This Week';
                    break;
                case 'quarter':
                    $start = Carbon::now()->startOfQuarter();
                    $end = Carbon::now()->endOfQuarter();
                    $periodLabel = 'This Quarter';
                    break;
                case 'year':
                    $start = Carbon::now()->startOfYear();
                    $end = Carbon::now()->endOfYear();
                    $periodLabel = 'This Year';
                    break;
                case 'month':
                default:
                    $start = Carbon::now()->startOfMonth();
                    $end = Carbon::now()->endOfMonth();
                    $periodLabel = 'This Month';
                    break;
            }
        }

        // Get common data for all users
        $data = [
            'user' => $user,
            'role' => $role,
            'roleLabel' => $this->getRoleLabel($role),
            'period' => $period,
            'periodLabel' => $periodLabel,
            'start' => $start,
            'end' => $end,
        ];

        // Role-specific dashboard data
        switch ($role) {
            case 'director':
                $data = array_merge($data, $this->getDirectorDashboard($start, $end));
                break;

            case 'branch_manager':
                $data = array_merge($data, $this->getBranchManagerDashboard($user, $start, $end));
                break;

            case 'accountant':
                $data = array_merge($data, $this->getAccountantDashboard($user, $start, $end));
                break;

            case 'secretary':
                $data = array_merge($data, $this->getSecretaryDashboard($user, $start, $end));
                break;

            case 'hr':
                $data = array_merge($data, $this->getHRDashboard($user, $start, $end));
                break;

            case 'staff':
                $data = array_merge($data, $this->getStaffDashboard($user, $start, $end));
                break;

            default:
                $data = array_merge($data, $this->getDefaultDashboard());
                break;
        }

        return view('dashboard.index', $data);
    }

    /**
     * Get Director Dashboard Data
     */
    private function getDirectorDashboard($start, $end)
    {
        // System-wide statistics
        $totalBranches = Branch::count();
        $totalUsers = User::count();
        $totalSuppliers = DB::table('suppliers')->count();
        $totalCustomers = DB::table('customers')->count();

        // Financial overview
        // Gold stock comes from branches.gold_available (single source of truth).
        // branch_balances (balance_type='gold') was a second, unsynced copy — do not use it for gold.
        $totalGold = Branch::sum('gold_available') ?? 0;
        $totalCash = BranchBalance::where('balance_type', 'cash')->sum('amount') ?? 0;

        // Loan statistics
        $totalLoans = Loan::count();
        $pendingLoans = Loan::where('status', 'pending')->count();
        $approvedLoans = Loan::where('status', 'approved')->count();
        $completedLoans = Loan::where('status', 'completed')->count();
        $totalLoanAmount = Loan::sum('amount') ?? 0;

        // Transaction statistics for the period
        $recentTransactions = Transaction::whereBetween('created_at', [$start, $end])->count();
        $recentGoldPurchases = Transaction::where('transaction_type', 'gold_purchase')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount') ?? 0;
        $recentGoldSales = Transaction::where('transaction_type', 'gold_sale')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount') ?? 0;

        // Expense statistics for the period
        $recentExpenses = Expense::whereBetween('created_at', [$start, $end])->sum('amount') ?? 0;

        // Net profit
        $netProfit = $recentGoldSales - $recentExpenses;

        // Recent activities (last 10 transactions)
        $recentActivities = Transaction::with(['branch', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Branch performance
        // 'balances' is eager-loaded here because the view still reads Cash from
        // branch_balances (balance_type='cash'). Gold is read from branches.gold_available
        // directly in the view now, not from this relationship.
        $branchPerformance = Branch::with(['balances'])
            ->select('branches.*')
            ->withCount(['transactions as transaction_count' => function ($q) use ($start, $end) {
                $q->whereBetween('created_at', [$start, $end]);
            }])
            ->withSum(['transactions as total_sales' => function ($query) use ($start, $end) {
                $query->where('transaction_type', 'gold_sale')
                      ->where('status', 'completed')
                      ->whereBetween('created_at', [$start, $end]);
            }], 'amount')
            ->withSum(['transactions as total_purchases' => function ($query) use ($start, $end) {
                $query->where('transaction_type', 'gold_purchase')
                      ->where('status', 'completed')
                      ->whereBetween('created_at', [$start, $end]);
            }], 'amount')
            ->get();

        // Chart data
        $chartData = $this->getChartData($start, $end);

        return [
            'totalBranches' => $totalBranches,
            'totalUsers' => $totalUsers,
            'totalSuppliers' => $totalSuppliers,
            'totalCustomers' => $totalCustomers,
            'totalGold' => $totalGold,
            'totalCash' => $totalCash,
            'totalLoans' => $totalLoans,
            'pendingLoans' => $pendingLoans,
            'approvedLoans' => $approvedLoans,
            'completedLoans' => $completedLoans,
            'totalLoanAmount' => $totalLoanAmount,
            'recentTransactions' => $recentTransactions,
            'recentGoldPurchases' => $recentGoldPurchases,
            'recentGoldSales' => $recentGoldSales,
            'recentExpenses' => $recentExpenses,
            'netProfit' => $netProfit,
            'recentActivities' => $recentActivities,
            'branchPerformance' => $branchPerformance,
            'chartData' => $chartData,
            'isDirector' => true,
        ];
    }

    /**
     * Get Branch Manager Dashboard Data
     */
    private function getBranchManagerDashboard($user, $start, $end)
    {
        $branchId = $user->assigned_branch;
        $branch = Branch::find($branchId);

        if (!$branch) {
            return $this->getDefaultDashboard();
        }

        // Branch statistics
        // Gold stock comes from branches.gold_available (single source of truth).
        $totalGold = $branch->gold_available ?? 0;

        $totalCash = BranchBalance::where('branch_id', $branchId)
            ->where('balance_type', 'cash')
            ->value('amount') ?? 0;

        // Loan statistics for this branch
        $totalLoans = Loan::where('branch_id', $branchId)->count();
        $pendingLoans = Loan::where('branch_id', $branchId)->where('status', 'pending')->count();
        $approvedLoans = Loan::where('branch_id', $branchId)->where('status', 'approved')->count();
        $totalLoanAmount = Loan::where('branch_id', $branchId)->sum('amount') ?? 0;

        // Transaction statistics for the period
        $recentTransactions = Transaction::where('branch_id', $branchId)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $recentGoldPurchases = Transaction::where('branch_id', $branchId)
            ->where('transaction_type', 'gold_purchase')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount') ?? 0;

        $recentGoldSales = Transaction::where('branch_id', $branchId)
            ->where('transaction_type', 'gold_sale')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount') ?? 0;

        // Expense statistics for the period
        $recentExpenses = Expense::where('branch_id', $branchId)
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount') ?? 0;

        // Net profit for this branch
        $netProfit = $recentGoldSales - $recentExpenses;

        // Recent activities (last 10 transactions)
        $recentActivities = Transaction::with(['user'])
            ->where('branch_id', $branchId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Staff in this branch
        $staffCount = User::where('assigned_branch', $branchId)->count();

        // Today's summary
        $today = now()->startOfDay();
        $todayTransactions = Transaction::where('branch_id', $branchId)
            ->where('created_at', '>=', $today)
            ->count();
        $todayRevenue = Transaction::where('branch_id', $branchId)
            ->where('transaction_type', 'gold_sale')
            ->where('status', 'completed')
            ->where('created_at', '>=', $today)
            ->sum('amount') ?? 0;

        return [
            'branch' => $branch,
            'branchName' => $branch->name,
            'totalGold' => $totalGold,
            'totalCash' => $totalCash,
            'totalLoans' => $totalLoans,
            'pendingLoans' => $pendingLoans,
            'approvedLoans' => $approvedLoans,
            'totalLoanAmount' => $totalLoanAmount,
            'recentTransactions' => $recentTransactions,
            'recentGoldPurchases' => $recentGoldPurchases,
            'recentGoldSales' => $recentGoldSales,
            'recentExpenses' => $recentExpenses,
            'netProfit' => $netProfit,
            'recentActivities' => $recentActivities,
            'staffCount' => $staffCount,
            'todayTransactions' => $todayTransactions,
            'todayRevenue' => $todayRevenue,
            'isBranchManager' => true,
            'branchId' => $branchId,
        ];
    }

    /**
     * Get Accountant Dashboard Data
     */
    private function getAccountantDashboard($user, $start, $end)
    {
        // All branch summary - get branches with their balances
        $branches = Branch::with(['balances'])->get();

        // Financial summary
        $totalCash = BranchBalance::where('balance_type', 'cash')->sum('amount') ?? 0;
        // Gold stock comes from branches.gold_available (single source of truth).
        $totalGold = Branch::sum('gold_available') ?? 0;

        // Revenue and expenses for the period
        $monthlySales = Transaction::where('transaction_type', 'gold_sale')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount') ?? 0;

        $monthlyExpenses = Expense::whereBetween('created_at', [$start, $end])
            ->sum('amount') ?? 0;

        $netProfit = $monthlySales - $monthlyExpenses;

        // Loan summary
        $totalLoanAmount = Loan::sum('amount') ?? 0;
        $pendingLoans = Loan::where('status', 'pending')->count();
        $activeLoans = Loan::whereIn('status', ['approved', 'pending'])->count();

        // Transaction counts by type for the period
        $transactionStats = Transaction::select('transaction_type', DB::raw('count(*) as count'), DB::raw('SUM(amount) as total'))
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('transaction_type')
            ->get();

        // Pending reconciliations
        $pendingReconciliations = CashReconciliation::where('status', 'pending')->count();
        $verifiedReconciliations = CashReconciliation::where('status', 'verified')->count();
        $totalReconciliations = CashReconciliation::count();

        // Expense by category for the period
        $expenseByCategory = Expense::whereBetween('created_at', [$start, $end])
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get();

        return [
            // Financial stats
            'totalCash' => $totalCash,
            'totalGold' => $totalGold,
            'monthlySales' => $monthlySales,
            'monthlyExpenses' => $monthlyExpenses,
            'netProfit' => $netProfit,

            // Loan stats
            'totalLoanAmount' => $totalLoanAmount,
            'pendingLoans' => $pendingLoans,
            'activeLoans' => $activeLoans,

            // Branch balances (for the table)
            'branches' => $branches,

            // Reconciliations
            'pendingReconciliations' => $pendingReconciliations,
            'verifiedReconciliations' => $verifiedReconciliations,
            'totalReconciliations' => $totalReconciliations,

            // Additional stats
            'transactionStats' => $transactionStats,
            'expenseByCategory' => $expenseByCategory,

            // Role flag
            'isAccountant' => true,
        ];
    }

    /**
     * Get Secretary Dashboard Data
     */
    private function getSecretaryDashboard($user, $start, $end)
    {
        // Communication statistics for the period
        $smsSent = DB::table('sms_logs')
            ->whereBetween('sent_at', [$start, $end])
            ->count();

        $whatsappSent = DB::table('whatsapp_logs')
            ->whereBetween('sent_at', [$start, $end])
            ->count();

        $emailsSent = DB::table('email_logs')
            ->whereBetween('created_at', [$start, $end])
            ->count() ?? 0;

        // Recent customer/supplier activity for the period
        $newCustomers = DB::table('customers')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $newSuppliers = DB::table('suppliers')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        // Pending notifications
        $unreadNotifications = DB::table('notifications')
            ->where('user_id', $user->id)
            ->where('is_read', 0)
            ->count();

        // Recent messages
        $recentMessages = DB::table('messages')
            ->where('recipient_id', $user->id)
            ->orWhere('sender_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return [
            'smsSent' => $smsSent,
            'whatsappSent' => $whatsappSent,
            'emailsSent' => $emailsSent,
            'newCustomers' => $newCustomers,
            'newSuppliers' => $newSuppliers,
            'unreadNotifications' => $unreadNotifications,
            'recentMessages' => $recentMessages,
            'isSecretary' => true,
        ];
    }

    /**
     * Get HR Dashboard Data
     */
    private function getHRDashboard($user, $start, $end)
    {
        // Staff statistics
        $totalStaff = User::count();
        $branchManagers = User::where('role', 'branch_manager')->count();
        $accountants = User::where('role', 'accountant')->count();
        $secretaries = User::where('role', 'secretary')->count();
        $regularStaff = User::where('role', 'staff')->count();

        // Recent hires for the period
        $newHires = User::whereBetween('created_at', [$start, $end])->count();

        // Staff by branch
        $staffByBranch = User::with('branch')
            ->whereIn('role', ['branch_manager', 'accountant', 'secretary', 'staff'])
            ->get()
            ->groupBy('assigned_branch')
            ->map(function ($group) {
                return $group->count();
            });

        return [
            'totalStaff' => $totalStaff,
            'branchManagers' => $branchManagers,
            'accountants' => $accountants,
            'secretaries' => $secretaries,
            'regularStaff' => $regularStaff,
            'newHires' => $newHires,
            'staffByBranch' => $staffByBranch,
            'isHR' => true,
        ];
    }

    /**
     * Get Staff Dashboard Data
     */
    private function getStaffDashboard($user, $start, $end)
    {
        // My recent transactions for the period
        $myTransactions = Transaction::where('recorded_by', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $myTransactionCount = Transaction::where('recorded_by', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        // My branch summary (if assigned)
        $branch = null;
        $branchGold = 0;
        $branchCash = 0;

        if ($user->assigned_branch) {
            $branch = Branch::find($user->assigned_branch);
            // Gold stock comes from branches.gold_available (single source of truth).
            $branchGold = $branch->gold_available ?? 0;
            $branchCash = BranchBalance::where('branch_id', $user->assigned_branch)
                ->where('balance_type', 'cash')
                ->value('amount') ?? 0;
        }

        // My notifications
        $myNotifications = DB::table('notifications')
            ->where('user_id', $user->id)
            ->where('is_read', 0)
            ->count();

        return [
            'myTransactions' => $myTransactions,
            'myTransactionCount' => $myTransactionCount,
            'branch' => $branch,
            'branchGold' => $branchGold,
            'branchCash' => $branchCash,
            'myNotifications' => $myNotifications,
            'isStaff' => true,
        ];
    }

    /**
     * Get Default Dashboard Data
     */
    private function getDefaultDashboard()
    {
        return [
            'message' => 'Welcome to Ngemba Mining Management System',
            'isDefault' => true,
        ];
    }

    /**
     * Get Chart Data for Director Dashboard
     */
    private function getChartData($start, $end)
    {
        $days = $start->diffInDays($end) + 1;
        if ($days > 30) {
            $days = 30;
        }

        $chartData = [];
        $currentDate = $start->copy();

        for ($i = 0; $i < $days; $i++) {
            $date = $currentDate->copy()->startOfDay();
            $nextDate = $date->copy()->addDay();

            $sales = Transaction::where('transaction_type', 'gold_sale')
                ->where('status', 'completed')
                ->whereBetween('created_at', [$date, $nextDate])
                ->sum('amount') ?? 0;

            $purchases = Transaction::where('transaction_type', 'gold_purchase')
                ->where('status', 'completed')
                ->whereBetween('created_at', [$date, $nextDate])
                ->sum('amount') ?? 0;

            $chartData['labels'][] = $date->format('d M');
            $chartData['sales'][] = (float) $sales;
            $chartData['purchases'][] = (float) $purchases;

            $currentDate->addDay();
        }

        return $chartData;
    }

    /**
     * Get Role Label
     */
    private function getRoleLabel($role)
    {
        $labels = [
            'director' => 'Director',
            'branch_manager' => 'Branch Manager',
            'accountant' => 'Accountant',
            'secretary' => 'Secretary',
            'hr' => 'HR',
            'staff' => 'Staff',
        ];

        return $labels[$role] ?? ucfirst($role);
    }

    /**
     * Clear cache (utility method)
     */
    public function clearCache()
    {
        \Artisan::call('cache:clear');
        \Artisan::call('config:clear');
        \Artisan::call('view:clear');
        \Artisan::call('route:clear');

        return redirect()->back()->with('success', 'Cache cleared successfully!');
    }
}