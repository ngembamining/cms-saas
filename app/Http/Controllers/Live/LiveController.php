<?php

namespace App\Http\Controllers\Live;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Expense;
use App\Models\Loan;
use App\Models\Branch;
use App\Models\BranchBalance;
use App\Models\Supplier;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LiveController extends Controller
{
    /**
     * Display the live view dashboard.
     */
    public function index()
    {
        return view('live.index');
    }

    /**
     * Display live cameras view.
     */
    public function cameras()
    {
        // Fetch camera data from your camera system
        $branches = Branch::all();
        $cameras = [];
        $cameraNames = [
            'Main Entrance', 'Gold Vault', 'Office Area', 'Staff Room', 
            'Security Gate', 'Processing Area', 'Warehouse', 'Parking Lot',
            'Reception', 'Loading Bay'
        ];
        
        foreach ($branches as $index => $branch) {
            $count = rand(2, 4);
            for ($i = 0; $i < $count; $i++) {
                $status = rand(1, 10) > 2 ? 'online' : 'offline';
                $cameras[] = [
                    'id' => count($cameras) + 1,
                    'name' => $cameraNames[count($cameras) % count($cameraNames)],
                    'branch_id' => $branch->id,
                    'branch_name' => $branch->name,
                    'status' => $status,
                    'stream_url' => null,
                    'last_activity' => Carbon::now()->subMinutes(rand(1, 30)),
                ];
            }
        }
        
        $totalCameras = count($cameras);
        $onlineCameras = collect($cameras)->where('status', 'online')->count();
        $offlineCameras = $totalCameras - $onlineCameras;
        $avgUptime = $totalCameras > 0 ? round(($onlineCameras / $totalCameras) * 100, 1) . '%' : 'N/A';

        return view('live.cameras', compact('cameras', 'branches', 'totalCameras', 'onlineCameras', 'offlineCameras', 'avgUptime'));
    }

    /**
     * Display live charts view.
     */
    public function charts(Request $request)
    {
        $branches = Branch::all();
        $period = $request->input('period', 'today');
        $branchId = $request->input('branch_id', 'all');
        $metric = $request->input('metric', 'sales');
        
        $stats = $this->getLiveStats($period, $branchId);
        $chartData = $this->getChartData($period, $branchId, $metric);
        $branchPerformance = $this->getBranchPerformance($period);
        $recentTransactions = $this->getRecentTransactions(10);
        $topCustomers = $this->getTopCustomers($period);
        $topSuppliers = $this->getTopSuppliers($period);
        
        return view('live.charts', compact(
            'branches', 
            'stats', 
            'chartData', 
            'branchPerformance',
            'recentTransactions',
            'topCustomers',
            'topSuppliers',
            'period',
            'branchId',
            'metric'
        ));
    }

    /**
     * Display transaction charts.
     */
    public function chartTransactions(Request $request)
    {
        $period = $request->input('period', '7days');
        $branchId = $request->input('branch_id', 'all');
        $data = $this->getTransactionChartData($period, $branchId);
        return view('live.charts.transactions', compact('data', 'period', 'branchId'));
    }

    /**
     * Display sales charts.
     */
    public function chartSales(Request $request)
    {
        $period = $request->input('period', '7days');
        $branchId = $request->input('branch_id', 'all');
        $data = $this->getSalesChartData($period, $branchId);
        return view('live.charts.sales', compact('data', 'period', 'branchId'));
    }

    /**
     * Display purchases charts.
     */
    public function chartPurchases(Request $request)
    {
        $period = $request->input('period', '7days');
        $branchId = $request->input('branch_id', 'all');
        $data = $this->getPurchasesChartData($period, $branchId);
        return view('live.charts.purchases', compact('data', 'period', 'branchId'));
    }

    /**
     * Display expenses charts.
     */
    public function chartExpenses(Request $request)
    {
        $period = $request->input('period', '7days');
        $branchId = $request->input('branch_id', 'all');
        $data = $this->getExpensesChartData($period, $branchId);
        return view('live.charts.expenses', compact('data', 'period', 'branchId'));
    }

    /**
     * Display loans charts.
     */
    public function chartLoans(Request $request)
    {
        $branchId = $request->input('branch_id', 'all');
        $data = $this->getLoansChartData($branchId);
        return view('live.charts.loans', compact('data', 'branchId'));
    }

    /**
     * Display branch-specific charts.
     */
    public function chartBranch($branch_id, Request $request)
    {
        $branch = Branch::findOrFail($branch_id);
        $period = $request->input('period', '7days');
        $data = $this->getBranchChartData($branch_id, $period);
        return view('live.charts.branch', compact('branch', 'data', 'period'));
    }

    /**
     * Display real-time charts.
     */
    public function chartRealtime(Request $request)
    {
        $branchId = $request->input('branch_id', 'all');
        $data = $this->getRealtimeData($branchId);
        return view('live.charts.realtime', compact('data', 'branchId'));
    }

    /**
     * Export chart data.
     */
    public function chartExport(Request $request)
    {
        $type = $request->input('type', 'transactions');
        $period = $request->input('period', '7days');
        $branchId = $request->input('branch_id', 'all');
        
        return response()->json(['success' => true, 'message' => 'Export started']);
    }

    // ============================================
    // API / DATA ENDPOINTS
    // ============================================

    /**
     * API: Dashboard data for AJAX polling.
     */
    public function apiDashboard(Request $request)
    {
        $period = $request->input('period', 'today');
        $branchId = $request->input('branch_id', 'all');
        
        $stats = $this->getLiveStats($period, $branchId);
        $recentTransactions = Transaction::with(['branch', 'customer', 'supplier'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function($transaction) {
                return [
                    'id' => $transaction->id,
                    'code' => $transaction->transaction_code,
                    'type' => $transaction->transaction_type,
                    'amount' => number_format($transaction->amount, 2),
                    'branch' => $transaction->branch->name ?? 'N/A',
                    'customer' => $transaction->customer->name ?? null,
                    'supplier' => $transaction->supplier->name ?? null,
                    'time' => $transaction->created_at->diffForHumans(),
                    'status' => $transaction->status,
                ];
            });

        return response()->json([
            'stats' => $stats,
            'recent_transactions' => $recentTransactions,
            'timestamp' => Carbon::now()->toIso8601String(),
        ]);
    }

    /**
     * API: Live transactions feed.
     */
    public function apiTransactions(Request $request)
    {
        $limit = $request->input('limit', 50);
        $branchId = $request->input('branch_id');
        
        $query = Transaction::with(['branch', 'customer', 'supplier'])
            ->orderBy('created_at', 'desc');
            
        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }
        
        $transactions = $query->limit($limit)->get();

        return response()->json([
            'transactions' => $transactions,
            'timestamp' => Carbon::now()->toIso8601String(),
        ]);
    }

    /**
     * API: Camera status.
     */
    public function apiCameraStatus()
    {
        $cameras = $this->getCameraData();
        return response()->json([
            'cameras' => $cameras,
            'timestamp' => Carbon::now()->toIso8601String(),
        ]);
    }

    /**
     * API: Live notifications.
     */
    public function apiNotifications()
    {
        $notifications = $this->getNotifications();
        return response()->json([
            'notifications' => $notifications,
            'timestamp' => Carbon::now()->toIso8601String(),
        ]);
    }

    /**
     * API: Branch performance data.
     */
    public function apiBranchPerformance(Request $request)
    {
        $period = $request->input('period', 'today');
        $data = $this->getBranchPerformance($period);
        return response()->json($data);
    }

    /**
     * API: Chart data.
     */
    public function apiChartData(Request $request)
    {
        $period = $request->input('period', '7days');
        $branchId = $request->input('branch_id', 'all');
        $metric = $request->input('metric', 'sales');
        $data = $this->getChartData($period, $branchId, $metric);
        return response()->json($data);
    }

    // ============================================
    // PRIVATE HELPER METHODS
    // ============================================

    /**
     * Get camera data.
     */
    private function getCameraData()
    {
        $branches = Branch::all();
        $cameras = [];
        $cameraNames = [
            'Main Entrance', 'Gold Vault', 'Office Area', 'Staff Room', 
            'Security Gate', 'Processing Area', 'Warehouse', 'Parking Lot',
            'Reception', 'Loading Bay'
        ];
        
        foreach ($branches as $index => $branch) {
            $count = rand(2, 4);
            for ($i = 0; $i < $count; $i++) {
                $status = rand(1, 10) > 2 ? 'online' : 'offline';
                $cameras[] = [
                    'id' => count($cameras) + 1,
                    'name' => $cameraNames[count($cameras) % count($cameraNames)],
                    'branch_id' => $branch->id,
                    'branch_name' => $branch->name,
                    'status' => $status,
                    'stream_url' => null,
                    'last_activity' => Carbon::now()->subMinutes(rand(1, 30)),
                ];
            }
        }
        
        return $cameras;
    }

    /**
     * Get live statistics.
     */
    private function getLiveStats($period = 'today', $branchId = 'all')
    {
        $dateRange = $this->getDateRange($period);
        $query = Transaction::whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        
        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        return [
            'total_transactions' => $query->count(),
            'total_sales' => (clone $query)->where('transaction_type', 'gold_sale')->where('status', 'completed')->sum('amount'),
            'total_purchases' => (clone $query)->where('transaction_type', 'gold_purchase')->where('status', 'completed')->sum('amount'),
            'total_expenses' => Expense::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->sum('amount'),
            'total_loans' => Loan::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->sum('amount'),
            'active_loans' => Loan::where('status', 'approved')->count(),
            'online_cameras' => rand(3, 8),
            'total_branches' => Branch::count(),
            'period_label' => $this->getPeriodLabel($period),
        ];
    }

    /**
     * Get chart data.
     */
    private function getChartData($period = '7days', $branchId = 'all', $metric = 'sales')
    {
        $dateRange = $this->getDateRange($period);
        $labels = [];
        $salesData = [];
        $purchasesData = [];
        $expensesData = [];
        
        $current = Carbon::parse($dateRange['start']);
        $end = Carbon::parse($dateRange['end']);
        
        while ($current <= $end) {
            $labels[] = $current->format('Y-m-d');
            
            $dayStart = $current->copy()->startOfDay();
            $dayEnd = $current->copy()->endOfDay();
            
            $query = Transaction::whereBetween('created_at', [$dayStart, $dayEnd]);
            if ($branchId && $branchId !== 'all') {
                $query->where('branch_id', $branchId);
            }
            
            $salesData[] = (clone $query)->where('transaction_type', 'gold_sale')->where('status', 'completed')->sum('amount');
            $purchasesData[] = (clone $query)->where('transaction_type', 'gold_purchase')->where('status', 'completed')->sum('amount');
            $expensesData[] = Expense::whereBetween('created_at', [$dayStart, $dayEnd])->sum('amount');
            
            $current->addDay();
        }
        
        return [
            'labels' => $labels,
            'sales' => $salesData,
            'purchases' => $purchasesData,
            'expenses' => $expensesData,
            'metric' => $metric,
        ];
    }

    /**
     * Get branch performance.
     */
    private function getBranchPerformance($period = 'today')
    {
        $dateRange = $this->getDateRange($period);
        $branches = Branch::all();
        
        $performance = [];
        foreach ($branches as $branch) {
            $sales = Transaction::where('branch_id', $branch->id)
                ->where('transaction_type', 'gold_sale')
                ->where('status', 'completed')
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->sum('amount');
                
            $purchases = Transaction::where('branch_id', $branch->id)
                ->where('transaction_type', 'gold_purchase')
                ->where('status', 'completed')
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->sum('amount');
                
            $expenses = Expense::where('branch_id', $branch->id)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->sum('amount');
                
            $loanAmount = Loan::where('branch_id', $branch->id)
                ->where('status', 'approved')
                ->sum('amount');
                
            $cashBalance = $branch->cash_balance;
            
            $performance[] = [
                'id' => $branch->id,
                'name' => $branch->name,
                'sales' => $sales,
                'purchases' => $purchases,
                'expenses' => $expenses,
                'profit' => $sales - $purchases - $expenses,
                'loan_amount' => $loanAmount,
                'cash_balance' => $cashBalance,
                'transaction_count' => Transaction::where('branch_id', $branch->id)
                    ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                    ->count(),
            ];
        }
        
        usort($performance, function($a, $b) {
            return $b['sales'] <=> $a['sales'];
        });
        
        return $performance;
    }

    /**
     * Get recent transactions.
     */
    private function getRecentTransactions($limit = 10)
    {
        return Transaction::with(['branch', 'customer', 'supplier'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top customers - FIXED for ONLY_FULL_GROUP_BY
     */
    private function getTopCustomers($period = 'month')
    {
        $dateRange = $this->getDateRange($period);
        
        return Customer::select(
                'customers.id',
                'customers.name',
                'customers.contact',
                'customers.email',
                'customers.branch_id',
                DB::raw('SUM(transactions.amount) as total_spent'),
                DB::raw('COUNT(transactions.id) as transaction_count')
            )
            ->join('transactions', 'customers.id', '=', 'transactions.customer_id')
            ->where('transactions.transaction_type', 'gold_sale')
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('customers.id', 'customers.name', 'customers.contact', 'customers.email', 'customers.branch_id')
            ->orderBy('total_spent', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get top suppliers - FIXED for ONLY_FULL_GROUP_BY
     */
    private function getTopSuppliers($period = 'month')
    {
        $dateRange = $this->getDateRange($period);
        
        return Supplier::select(
                'suppliers.id',
                'suppliers.name',
                'suppliers.contact',
                'suppliers.email',
                'suppliers.branch_id',
                DB::raw('SUM(transactions.amount) as total_supplied'),
                DB::raw('COUNT(transactions.id) as transaction_count')
            )
            ->join('transactions', 'suppliers.id', '=', 'transactions.supplier_id')
            ->where('transactions.transaction_type', 'gold_purchase')
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('suppliers.id', 'suppliers.name', 'suppliers.contact', 'suppliers.email', 'suppliers.branch_id')
            ->orderBy('total_supplied', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get transaction chart data.
     */
    private function getTransactionChartData($period = '7days', $branchId = 'all')
    {
        $dateRange = $this->getDateRange($period);
        $data = [];
        
        $current = Carbon::parse($dateRange['start']);
        $end = Carbon::parse($dateRange['end']);
        
        while ($current <= $end) {
            $dayStart = $current->copy()->startOfDay();
            $dayEnd = $current->copy()->endOfDay();
            
            $query = Transaction::whereBetween('created_at', [$dayStart, $dayEnd]);
            if ($branchId && $branchId !== 'all') {
                $query->where('branch_id', $branchId);
            }
            
            $data[] = [
                'date' => $current->format('Y-m-d'),
                'sales' => (clone $query)->where('transaction_type', 'gold_sale')->where('status', 'completed')->count(),
                'purchases' => (clone $query)->where('transaction_type', 'gold_purchase')->where('status', 'completed')->count(),
                'total' => (clone $query)->count(),
            ];
            
            $current->addDay();
        }
        
        return $data;
    }

    /**
     * Get sales chart data.
     */
    private function getSalesChartData($period = '7days', $branchId = 'all')
    {
        $dateRange = $this->getDateRange($period);
        $data = [];
        
        $current = Carbon::parse($dateRange['start']);
        $end = Carbon::parse($dateRange['end']);
        
        while ($current <= $end) {
            $dayStart = $current->copy()->startOfDay();
            $dayEnd = $current->copy()->endOfDay();
            
            $query = Transaction::where('transaction_type', 'gold_sale')
                ->where('status', 'completed')
                ->whereBetween('created_at', [$dayStart, $dayEnd]);
                
            if ($branchId && $branchId !== 'all') {
                $query->where('branch_id', $branchId);
            }
            
            $data[] = [
                'date' => $current->format('Y-m-d'),
                'amount' => (clone $query)->sum('amount'),
                'count' => (clone $query)->count(),
                'avg' => (clone $query)->avg('amount') ?? 0,
            ];
            
            $current->addDay();
        }
        
        return $data;
    }

    /**
     * Get purchases chart data.
     */
    private function getPurchasesChartData($period = '7days', $branchId = 'all')
    {
        $dateRange = $this->getDateRange($period);
        $data = [];
        
        $current = Carbon::parse($dateRange['start']);
        $end = Carbon::parse($dateRange['end']);
        
        while ($current <= $end) {
            $dayStart = $current->copy()->startOfDay();
            $dayEnd = $current->copy()->endOfDay();
            
            $query = Transaction::where('transaction_type', 'gold_purchase')
                ->where('status', 'completed')
                ->whereBetween('created_at', [$dayStart, $dayEnd]);
                
            if ($branchId && $branchId !== 'all') {
                $query->where('branch_id', $branchId);
            }
            
            $data[] = [
                'date' => $current->format('Y-m-d'),
                'amount' => (clone $query)->sum('amount'),
                'count' => (clone $query)->count(),
                'avg' => (clone $query)->avg('amount') ?? 0,
            ];
            
            $current->addDay();
        }
        
        return $data;
    }

    /**
     * Get expenses chart data.
     */
    private function getExpensesChartData($period = '7days', $branchId = 'all')
    {
        $dateRange = $this->getDateRange($period);
        $data = [];
        
        $current = Carbon::parse($dateRange['start']);
        $end = Carbon::parse($dateRange['end']);
        
        while ($current <= $end) {
            $dayStart = $current->copy()->startOfDay();
            $dayEnd = $current->copy()->endOfDay();
            
            $query = Expense::whereBetween('created_at', [$dayStart, $dayEnd]);
            if ($branchId && $branchId !== 'all') {
                $query->where('branch_id', $branchId);
            }
            
            $data[] = [
                'date' => $current->format('Y-m-d'),
                'amount' => (clone $query)->sum('amount'),
                'count' => (clone $query)->count(),
            ];
            
            $current->addDay();
        }
        
        return $data;
    }

    /**
     * Get loans chart data.
     */
    private function getLoansChartData($branchId = 'all')
    {
        $query = Loan::query();
        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }
        
        $statuses = ['pending', 'approved', 'completed', 'rejected'];
        $data = [
            'labels' => ['Pending', 'Approved', 'Completed', 'Rejected'],
            'values' => [],
            'amounts' => [],
        ];
        
        foreach ($statuses as $status) {
            $count = (clone $query)->where('status', $status)->count();
            $amount = (clone $query)->where('status', $status)->sum('amount');
            $data['values'][] = $count;
            $data['amounts'][] = $amount;
        }
        
        return $data;
    }

    /**
     * Get branch chart data.
     */
    private function getBranchChartData($branchId, $period = '7days')
    {
        $dateRange = $this->getDateRange($period);
        $data = [];
        
        $current = Carbon::parse($dateRange['start']);
        $end = Carbon::parse($dateRange['end']);
        
        while ($current <= $end) {
            $dayStart = $current->copy()->startOfDay();
            $dayEnd = $current->copy()->endOfDay();
            
            $data[] = [
                'date' => $current->format('Y-m-d'),
                'sales' => Transaction::where('branch_id', $branchId)
                    ->where('transaction_type', 'gold_sale')
                    ->where('status', 'completed')
                    ->whereBetween('created_at', [$dayStart, $dayEnd])
                    ->sum('amount'),
                'purchases' => Transaction::where('branch_id', $branchId)
                    ->where('transaction_type', 'gold_purchase')
                    ->where('status', 'completed')
                    ->whereBetween('created_at', [$dayStart, $dayEnd])
                    ->sum('amount'),
                'expenses' => Expense::where('branch_id', $branchId)
                    ->whereBetween('created_at', [$dayStart, $dayEnd])
                    ->sum('amount'),
            ];
            
            $current->addDay();
        }
        
        return $data;
    }

    /**
     * Get realtime data.
     */
    private function getRealtimeData($branchId = 'all')
    {
        $query = Transaction::where('created_at', '>=', Carbon::now()->subHours(1));
        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }
        
        return [
            'recent' => (clone $query)->orderBy('created_at', 'desc')->limit(20)->get(),
            'sales' => (clone $query)->where('transaction_type', 'gold_sale')->sum('amount'),
            'purchases' => (clone $query)->where('transaction_type', 'gold_purchase')->sum('amount'),
            'count' => (clone $query)->count(),
            'by_minute' => (clone $query)->select(DB::raw('DATE_FORMAT(created_at, "%H:%i") as minute'), DB::raw('COUNT(*) as count'))
                ->groupBy('minute')
                ->orderBy('minute', 'desc')
                ->limit(60)
                ->get(),
        ];
    }

    /**
     * Get notifications.
     */
    private function getNotifications()
    {
        $notifications = [];
        
        // Recent transactions
        $recentTx = Transaction::orderBy('created_at', 'desc')->limit(5)->get();
        foreach ($recentTx as $tx) {
            $notifications[] = [
                'id' => $tx->id,
                'type' => 'transaction',
                'message' => $this->getTransactionMessage($tx),
                'time' => $tx->created_at->diffForHumans(),
                'read' => false,
            ];
        }
        
        // Low balance alerts
        $branches = Branch::all();
        foreach ($branches as $branch) {
            $cashBalance = $branch->cash_balance;
            if ($cashBalance < 1000000) {
                $notifications[] = [
                    'id' => 'balance_' . $branch->id,
                    'type' => 'alert',
                    'message' => "Low cash balance at {$branch->name}: " . number_format($cashBalance, 2) . " TZS",
                    'time' => Carbon::now()->diffForHumans(),
                    'read' => false,
                ];
            }
        }
        
        // Pending loans
        $pendingLoans = Loan::where('status', 'pending')->limit(3)->get();
        foreach ($pendingLoans as $loan) {
            $notifications[] = [
                'id' => 'loan_' . $loan->id,
                'type' => 'loan',
                'message' => "Pending loan application: " . number_format($loan->amount, 2) . " TZS",
                'time' => $loan->created_at->diffForHumans(),
                'read' => false,
            ];
        }
        
        usort($notifications, function($a, $b) {
            return strtotime($a['time']) <=> strtotime($b['time']);
        });
        
        return array_slice($notifications, 0, 15);
    }

    /**
     * Get transaction message.
     */
    private function getTransactionMessage($tx)
    {
        $typeLabels = [
            'gold_purchase' => 'Gold Purchase',
            'gold_sale' => 'Gold Sale',
            'cash_deposit' => 'Cash Deposit',
            'cash_withdrawal' => 'Cash Withdrawal',
            'expense' => 'Expense',
            'loan_disbursement' => 'Loan Disbursement',
            'loan_repayment' => 'Loan Repayment',
        ];
        
        $label = $typeLabels[$tx->transaction_type] ?? $tx->transaction_type;
        $amount = number_format($tx->amount, 2);
        $branch = $tx->branch->name ?? 'Unknown';
        
        return "{$label} of {$amount} TZS at {$branch}";
    }

    /**
     * Get date range.
     */
    private function getDateRange($period)
    {
        switch ($period) {
            case 'today':
                return [
                    'start' => Carbon::today()->startOfDay(),
                    'end' => Carbon::today()->endOfDay(),
                    'label' => 'Today'
                ];
            case 'yesterday':
                return [
                    'start' => Carbon::yesterday()->startOfDay(),
                    'end' => Carbon::yesterday()->endOfDay(),
                    'label' => 'Yesterday'
                ];
            case 'week':
                return [
                    'start' => Carbon::now()->startOfWeek(),
                    'end' => Carbon::now()->endOfWeek(),
                    'label' => 'This Week'
                ];
            case 'month':
                return [
                    'start' => Carbon::now()->startOfMonth(),
                    'end' => Carbon::now()->endOfMonth(),
                    'label' => 'This Month'
                ];
            case 'quarter':
                return [
                    'start' => Carbon::now()->startOfQuarter(),
                    'end' => Carbon::now()->endOfQuarter(),
                    'label' => 'This Quarter'
                ];
            case 'year':
                return [
                    'start' => Carbon::now()->startOfYear(),
                    'end' => Carbon::now()->endOfYear(),
                    'label' => 'This Year'
                ];
            case '7days':
            default:
                return [
                    'start' => Carbon::now()->subDays(7)->startOfDay(),
                    'end' => Carbon::now()->endOfDay(),
                    'label' => 'Last 7 Days'
                ];
            case '30days':
                return [
                    'start' => Carbon::now()->subDays(30)->startOfDay(),
                    'end' => Carbon::now()->endOfDay(),
                    'label' => 'Last 30 Days'
                ];
        }
    }

    /**
     * Get period label.
     */
    private function getPeriodLabel($period)
    {
        $labels = [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'week' => 'This Week',
            'month' => 'This Month',
            'quarter' => 'This Quarter',
            'year' => 'This Year',
            '7days' => 'Last 7 Days',
            '30days' => 'Last 30 Days',
        ];
        
        return $labels[$period] ?? 'Custom Period';
    }
}