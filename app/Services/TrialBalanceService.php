<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchBalance;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Loan;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\TrialBalance;
use App\Models\TrialBalanceHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TrialBalanceService
{
    /**
     * Generate trial balance for a specific date range
     * Opening stock is the gold stock at the start of the period
     */
    public function generateTrialBalance($startDate = null, $endDate = null, $branchId = null)
    {
        // Set default dates if not provided
        if (!$startDate) {
            $startDate = now()->startOfMonth()->format('Y-m-d');
        }
        if (!$endDate) {
            $endDate = now()->format('Y-m-d');
        }

        // Get branches
        $branches = Branch::when($branchId, function ($query) use ($branchId) {
            return $query->where('id', $branchId);
        })->get();

        $trialBalanceData = [];

        foreach ($branches as $branch) {
            // OPENING STOCK: Gold stock at the start of the period
            $openingGoldStock = $this->getGoldStockAtDate($branch->id, $startDate);
            
            // Gold transactions DURING the period
            $goldPurchases = $this->getGoldPurchases($branch->id, $startDate, $endDate);
            $goldSales = $this->getGoldSales($branch->id, $startDate, $endDate);

            // Get gold rates for valuation
            $openingRate = $this->getGoldRate($startDate);

            // Opening stock value (this is what we balance)
            $openingStockValue = $openingGoldStock * $openingRate;
            
            // Cash balances
            $openingCashBalance = $this->getCashBalanceAtDate($branch->id, $startDate);
            $closingCashBalance = BranchBalance::where('branch_id', $branch->id)
                ->where('balance_type', 'cash')
                ->value('amount') ?? 0;

            // Customer and supplier balances
            $customerBalance = $this->getCustomerBalance($branch->id);
            $supplierBalance = $this->getSupplierBalance($branch->id);

            // Loan data
            $loanData = $this->getLoanData($branch->id, $startDate, $endDate);

            // Transaction summaries
            $transactionData = $this->getTransactionData($branch->id, $startDate, $endDate);

            // Expense data
            $expenseData = $this->getExpenseData($branch->id, $startDate, $endDate);

            $trialBalanceData[$branch->id] = [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                // OPENING STOCK - This is the key balance figure (ONLY THIS)
                'gold_stock' => [
                    'opening_stock' => $openingGoldStock,
                    'opening_value' => $openingStockValue,
                    'rate' => $openingRate,
                    'note' => 'Gold stock at start of period (opening balance)'
                ],
                // Transactions during period (for reference only)
                'transactions' => [
                    'purchases' => $goldPurchases,
                    'sales' => $goldSales,
                    'net_change' => $goldPurchases - $goldSales,
                    'purchases_value' => $goldPurchases * $openingRate,
                    'sales_value' => $goldSales * $openingRate,
                ],
                // Cash
                'cash' => [
                    'opening' => $openingCashBalance,
                    'closing' => $closingCashBalance,
                    'net_change' => $closingCashBalance - $openingCashBalance
                ],
                // Customers
                'customers' => [
                    'total_outstanding' => $customerBalance,
                    'count' => Customer::where('branch_id', $branch->id)->count()
                ],
                // Suppliers
                'suppliers' => [
                    'total_outstanding' => $supplierBalance,
                    'count' => Supplier::where('branch_id', $branch->id)->count()
                ],
                // Loans
                'loans' => [
                    'total_issued' => $loanData['total_issued'],
                    'total_approved' => $loanData['total_approved'],
                    'total_completed' => $loanData['total_completed'],
                    'total_pending' => $loanData['total_pending'],
                    'total_rejected' => $loanData['total_rejected'],
                    'total_outstanding' => $loanData['total_outstanding']
                ],
                // Expenses
                'expenses' => [
                    'total_expenses' => $expenseData['total'],
                    'by_category' => $expenseData['by_category']
                ],
                // Summary - Balance is based on OPENING STOCK only
                'summary' => [
                    'opening_stock_balance' => $openingStockValue, // This is the balance
                    'total_cash' => $closingCashBalance,
                    'total_loans_outstanding' => $loanData['total_outstanding'],
                    'total_customer_outstanding' => $customerBalance,
                    'total_supplier_outstanding' => $supplierBalance,
                    'total_assets' => $openingStockValue + $closingCashBalance + $loanData['total_outstanding'],
                    'total_liabilities' => $supplierBalance,
                    'total_equity' => $openingStockValue + $closingCashBalance + $loanData['total_outstanding'] - $supplierBalance
                ]
            ];
        }

        // Calculate totals across all branches
        $totals = $this->calculateTotals($trialBalanceData);

        // Save trial balance history
        $this->saveTrialBalanceHistory($trialBalanceData, $startDate, $endDate, $branchId);

        return [
            'data' => $trialBalanceData,
            'totals' => $totals,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'generated_at' => now(),
            'generated_by' => Auth::user()->name ?? 'System'
        ];
    }

    /**
     * Get gold stock at a specific date (for opening stock calculation)
     * This sums all gold transactions up to the date
     */
    private function getGoldStockAtDate($branchId, $date)
    {
        // Get all gold purchases up to the date (excluding the date)
        $purchases = Transaction::where('branch_id', $branchId)
            ->where('transaction_type', 'gold_purchase')
            ->where('status', 'completed')
            ->whereDate('created_at', '<', $date)
            ->sum('amount');

        // Get all gold sales up to the date (excluding the date)
        $sales = Transaction::where('branch_id', $branchId)
            ->where('transaction_type', 'gold_sale')
            ->where('status', 'completed')
            ->whereDate('created_at', '<', $date)
            ->sum('amount');

        // If no transactions, use the current balance as opening
        if ($purchases == 0 && $sales == 0) {
            $currentGold = BranchBalance::where('branch_id', $branchId)
                ->where('balance_type', 'gold')
                ->value('amount') ?? 0;
            return $currentGold;
        }

        return $purchases - $sales;
    }

    /**
     * Get gold purchases during the period
     */
    private function getGoldPurchases($branchId, $startDate, $endDate)
    {
        return Transaction::where('branch_id', $branchId)
            ->where('transaction_type', 'gold_purchase')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');
    }

    /**
     * Get gold sales during the period
     */
    private function getGoldSales($branchId, $startDate, $endDate)
    {
        return Transaction::where('branch_id', $branchId)
            ->where('transaction_type', 'gold_sale')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');
    }

    /**
     * Get cash balance at a specific date
     */
    private function getCashBalanceAtDate($branchId, $date)
    {
        return BranchBalance::where('branch_id', $branchId)
            ->where('balance_type', 'cash')
            ->value('amount') ?? 0;
    }

    /**
     * Get gold rate for a specific date
     */
    private function getGoldRate($date)
    {
        $rate = DB::table('exchange_rates')
            ->whereDate('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->first();

        return $rate ? $rate->gold_rate : 0;
    }

    /**
     * Get customer outstanding balance
     */
    private function getCustomerBalance($branchId)
    {
        try {
            $balance = Customer::where('branch_id', $branchId)
                ->where('outstanding_balance', '>', 0)
                ->sum('outstanding_balance');
            return $balance ?: 0;
        } catch (\Exception $e) {
            return $this->calculateCustomerBalance($branchId);
        }
    }

    /**
     * Calculate customer balance from transactions
     */
    private function calculateCustomerBalance($branchId)
    {
        $sales = Transaction::where('branch_id', $branchId)
            ->where('transaction_type', 'gold_sale')
            ->where('status', 'completed')
            ->whereNotNull('customer_id')
            ->sum('amount');

        $payments = Transaction::where('branch_id', $branchId)
            ->where('transaction_type', 'loan_repayment')
            ->where('status', 'completed')
            ->whereNotNull('customer_id')
            ->sum('amount');

        return $sales - $payments;
    }

    /**
     * Get supplier outstanding balance
     */
    private function getSupplierBalance($branchId)
    {
        try {
            $balance = Supplier::where('branch_id', $branchId)
                ->where('outstanding_balance', '>', 0)
                ->sum('outstanding_balance');
            return $balance ?: 0;
        } catch (\Exception $e) {
            return $this->calculateSupplierBalance($branchId);
        }
    }

    /**
     * Calculate supplier balance from transactions
     */
    private function calculateSupplierBalance($branchId)
    {
        $purchases = Transaction::where('branch_id', $branchId)
            ->where('transaction_type', 'gold_purchase')
            ->where('status', 'completed')
            ->whereNotNull('supplier_id')
            ->sum('amount');

        $payments = Transaction::where('branch_id', $branchId)
            ->where('transaction_type', 'cash_withdrawal')
            ->where('status', 'completed')
            ->whereNotNull('supplier_id')
            ->sum('amount');

        return $purchases - $payments;
    }

    /**
     * Get loan data
     */
    private function getLoanData($branchId, $startDate, $endDate)
    {
        $loans = Loan::where('branch_id', $branchId);

        $totalIssued = (clone $loans)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        $totalApproved = (clone $loans)
            ->where('status', 'approved')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        $totalCompleted = (clone $loans)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        $totalPending = (clone $loans)
            ->where('status', 'pending')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        $totalRejected = (clone $loans)
            ->where('status', 'rejected')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        $totalOutstanding = (clone $loans)
            ->where('status', 'approved')
            ->sum('amount');

        return [
            'total_issued' => $totalIssued,
            'total_approved' => $totalApproved,
            'total_completed' => $totalCompleted,
            'total_pending' => $totalPending,
            'total_rejected' => $totalRejected,
            'total_outstanding' => $totalOutstanding
        ];
    }

    /**
     * Get transaction data
     */
    private function getTransactionData($branchId, $startDate, $endDate)
    {
        $goldPurchases = Transaction::where('branch_id', $branchId)
            ->where('transaction_type', 'gold_purchase')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        $goldSales = Transaction::where('branch_id', $branchId)
            ->where('transaction_type', 'gold_sale')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        return [
            'gold_purchases' => $goldPurchases,
            'gold_sales' => $goldSales
        ];
    }

    /**
     * Get expense data
     */
    private function getExpenseData($branchId, $startDate, $endDate)
    {
        $expenses = Expense::where('branch_id', $branchId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $total = $expenses->sum('amount');
        
        $byCategory = $expenses->groupBy('category')
            ->map(function ($group) {
                return [
                    'total' => $group->sum('amount'),
                    'count' => $group->count()
                ];
            })->toArray();

        return [
            'total' => $total,
            'by_category' => $byCategory
        ];
    }

    /**
     * Calculate totals across all branches
     */
    private function calculateTotals($data)
    {
        $totals = [
            'gold_stock' => [
                'opening_stock' => 0,
                'opening_value' => 0
            ],
            'transactions' => [
                'purchases' => 0,
                'sales' => 0,
                'net_change' => 0,
                'purchases_value' => 0,
                'sales_value' => 0,
            ],
            'cash' => [
                'opening' => 0,
                'closing' => 0,
                'net_change' => 0
            ],
            'customers' => [
                'total_outstanding' => 0,
                'count' => 0
            ],
            'suppliers' => [
                'total_outstanding' => 0,
                'count' => 0
            ],
            'loans' => [
                'total_issued' => 0,
                'total_approved' => 0,
                'total_completed' => 0,
                'total_pending' => 0,
                'total_rejected' => 0,
                'total_outstanding' => 0
            ],
            'expenses' => [
                'total_expenses' => 0
            ],
            'summary' => [
                'opening_stock_balance' => 0,
                'total_cash' => 0,
                'total_loans_outstanding' => 0,
                'total_customer_outstanding' => 0,
                'total_supplier_outstanding' => 0,
                'total_assets' => 0,
                'total_liabilities' => 0,
                'total_equity' => 0
            ]
        ];

        foreach ($data as $branch) {
            // Gold stock - ONLY opening stock
            $totals['gold_stock']['opening_stock'] += $branch['gold_stock']['opening_stock'];
            $totals['gold_stock']['opening_value'] += $branch['gold_stock']['opening_value'];
            
            // Transactions
            $totals['transactions']['purchases'] += $branch['transactions']['purchases'];
            $totals['transactions']['sales'] += $branch['transactions']['sales'];
            $totals['transactions']['net_change'] += $branch['transactions']['net_change'];
            $totals['transactions']['purchases_value'] += $branch['transactions']['purchases_value'];
            $totals['transactions']['sales_value'] += $branch['transactions']['sales_value'];
            
            // Cash
            $totals['cash']['opening'] += $branch['cash']['opening'];
            $totals['cash']['closing'] += $branch['cash']['closing'];
            $totals['cash']['net_change'] += $branch['cash']['net_change'];
            
            // Customers
            $totals['customers']['total_outstanding'] += $branch['customers']['total_outstanding'];
            $totals['customers']['count'] += $branch['customers']['count'];
            
            // Suppliers
            $totals['suppliers']['total_outstanding'] += $branch['suppliers']['total_outstanding'];
            $totals['suppliers']['count'] += $branch['suppliers']['count'];
            
            // Loans
            $totals['loans']['total_issued'] += $branch['loans']['total_issued'];
            $totals['loans']['total_approved'] += $branch['loans']['total_approved'];
            $totals['loans']['total_completed'] += $branch['loans']['total_completed'];
            $totals['loans']['total_pending'] += $branch['loans']['total_pending'];
            $totals['loans']['total_rejected'] += $branch['loans']['total_rejected'];
            $totals['loans']['total_outstanding'] += $branch['loans']['total_outstanding'];
            
            // Expenses
            $totals['expenses']['total_expenses'] += $branch['expenses']['total_expenses'];
            
            // Summary
            $totals['summary']['opening_stock_balance'] += $branch['summary']['opening_stock_balance'];
            $totals['summary']['total_cash'] += $branch['summary']['total_cash'];
            $totals['summary']['total_loans_outstanding'] += $branch['summary']['total_loans_outstanding'];
            $totals['summary']['total_customer_outstanding'] += $branch['summary']['total_customer_outstanding'];
            $totals['summary']['total_supplier_outstanding'] += $branch['summary']['total_supplier_outstanding'];
            $totals['summary']['total_assets'] += $branch['summary']['total_assets'];
            $totals['summary']['total_liabilities'] += $branch['summary']['total_liabilities'];
            $totals['summary']['total_equity'] += $branch['summary']['total_equity'];
        }

        return $totals;
    }

    /**
     * Save trial balance history
     */
    private function saveTrialBalanceHistory($data, $startDate, $endDate, $branchId)
    {
        try {
            if (class_exists('App\Models\TrialBalanceHistory')) {
                TrialBalanceHistory::create([
                    'data' => json_encode($data),
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'branch_id' => $branchId,
                    'user_id' => Auth::id(),
                    'generated_at' => now()
                ]);
            }
        } catch (\Exception $e) {
            \Log::warning('Could not save trial balance history: ' . $e->getMessage());
        }
    }

    /**
     * Get trial balance for a specific branch
     */
    public function getBranchTrialBalance($branchId, $startDate = null, $endDate = null)
    {
        return $this->generateTrialBalance($startDate, $endDate, $branchId);
    }

    /**
     * Get comparative trial balance for two periods
     */
    public function getComparativeTrialBalance($branchId, $period1Start, $period1End, $period2Start, $period2End)
    {
        $period1 = $this->generateTrialBalance($period1Start, $period1End, $branchId);
        $period2 = $this->generateTrialBalance($period2Start, $period2End, $branchId);

        return [
            'period1' => $period1,
            'period2' => $period2,
            'comparison' => $this->compareTrialBalances($period1, $period2)
        ];
    }

    /**
     * Compare two trial balances
     */
    private function compareTrialBalances($period1, $period2)
    {
        $comparison = [];

        // Compare opening stock
        $comparison['opening_stock'] = [
            'value' => $period2['totals']['gold_stock']['opening_value'] - $period1['totals']['gold_stock']['opening_value'],
            'gold' => $period2['totals']['gold_stock']['opening_stock'] - $period1['totals']['gold_stock']['opening_stock']
        ];

        // Compare cash
        $comparison['cash'] = [
            'closing' => $period2['totals']['cash']['closing'] - $period1['totals']['cash']['closing']
        ];

        // Compare loans
        $comparison['loans'] = [
            'total_outstanding' => $period2['totals']['loans']['total_outstanding'] - $period1['totals']['loans']['total_outstanding']
        ];

        // Compare expenses
        $comparison['expenses'] = [
            'total' => $period2['totals']['expenses']['total_expenses'] - $period1['totals']['expenses']['total_expenses']
        ];

        // Compare summary
        $comparison['summary'] = [
            'total_assets' => $period2['totals']['summary']['total_assets'] - $period1['totals']['summary']['total_assets'],
            'total_liabilities' => $period2['totals']['summary']['total_liabilities'] - $period1['totals']['summary']['total_liabilities'],
            'total_equity' => $period2['totals']['summary']['total_equity'] - $period1['totals']['summary']['total_equity']
        ];

        return $comparison;
    }

    /**
     * Export trial balance to CSV
     */
    public function exportToCsv($data)
    {
        $filename = 'trial_balance_' . date('Y-m-d_H-i-s') . '.csv';
        $handle = fopen('php://temp', 'r+');

        // Add headers
        fputcsv($handle, [
            'Branch',
            'Opening Stock (g)',
            'Opening Stock Value (TZS)',
            'Gold Purchases',
            'Gold Sales',
            'Net Change',
            'Opening Cash',
            'Closing Cash',
            'Customer Outstanding',
            'Supplier Outstanding',
            'Total Loans Issued',
            'Total Expenses',
            'Total Assets',
            'Total Liabilities',
            'Total Equity'
        ]);

        // Add data
        foreach ($data['data'] as $branch) {
            fputcsv($handle, [
                $branch['branch_name'],
                $branch['gold_stock']['opening_stock'],
                $branch['gold_stock']['opening_value'],
                $branch['transactions']['purchases'],
                $branch['transactions']['sales'],
                $branch['transactions']['net_change'],
                $branch['cash']['opening'],
                $branch['cash']['closing'],
                $branch['customers']['total_outstanding'],
                $branch['suppliers']['total_outstanding'],
                $branch['loans']['total_issued'],
                $branch['expenses']['total_expenses'],
                $branch['summary']['total_assets'],
                $branch['summary']['total_liabilities'],
                $branch['summary']['total_equity']
            ]);
        }

        // Add totals row
        fputcsv($handle, [
            'TOTALS',
            $data['totals']['gold_stock']['opening_stock'],
            $data['totals']['gold_stock']['opening_value'],
            $data['totals']['transactions']['purchases'],
            $data['totals']['transactions']['sales'],
            $data['totals']['transactions']['net_change'],
            $data['totals']['cash']['opening'],
            $data['totals']['cash']['closing'],
            $data['totals']['customers']['total_outstanding'],
            $data['totals']['suppliers']['total_outstanding'],
            $data['totals']['loans']['total_issued'],
            $data['totals']['expenses']['total_expenses'],
            $data['totals']['summary']['total_assets'],
            $data['totals']['summary']['total_liabilities'],
            $data['totals']['summary']['total_equity']
        ]);

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return [
            'filename' => $filename,
            'content' => $csv
        ];
    }

    /**
     * Get trial balance summary for dashboard
     */
    public function getDashboardSummary($branchId = null)
    {
        $data = $this->generateTrialBalance(
            now()->startOfMonth()->format('Y-m-d'),
            now()->format('Y-m-d'),
            $branchId
        );

        return [
            'opening_gold' => $data['totals']['gold_stock']['opening_stock'],
            'opening_gold_value' => $data['totals']['gold_stock']['opening_value'],
            'total_cash' => $data['totals']['cash']['closing'],
            'total_loans_outstanding' => $data['totals']['loans']['total_outstanding'],
            'total_expenses' => $data['totals']['expenses']['total_expenses'],
            'total_assets' => $data['totals']['summary']['total_assets'],
            'total_liabilities' => $data['totals']['summary']['total_liabilities'],
            'total_equity' => $data['totals']['summary']['total_equity'],
            'customer_count' => $data['totals']['customers']['count'],
            'supplier_count' => $data['totals']['suppliers']['count']
        ];
    }
}