<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Loan;
use App\Models\Expense;
use App\Models\BranchBalance;
use Carbon\Carbon;

class ReportController extends Controller
{
    private function isGlobal($user): bool
    {
        return in_array($user->role, ['director', 'admin', 'accountant']);
    }

    private function scopeToBranch($query, $user, $branchId = null)
    {
        if ($this->isGlobal($user)) {
            if ($branchId) $query->where('branch_id', $branchId);
        } else {
            $query->where('branch_id', $user->assigned_branch);
        }
        return $query;
    }

    public function daily(Request $request)
    {
        $user = $request->user();
        $date = $request->input('date', now()->toDateString());

        $txQ = Transaction::whereDate('created_at', $date);
        $this->scopeToBranch($txQ, $user);

        $expQ = Expense::whereDate('created_at', $date);
        $this->scopeToBranch($expQ, $user);

        return response()->json([
            'status' => true,
            'data' => [
                'date' => $date,
                'sales' => (float) (clone $txQ)->where('transaction_type', 'gold_sale')->sum('amount'),
                'purchases' => (float) (clone $txQ)->where('transaction_type', 'gold_purchase')->sum('amount'),
                'expenses' => (float) (clone $expQ)->sum('amount'),
            ],
        ]);
    }

    public function weekly(Request $request)
    {
        $user = $request->user();
        $start = $request->input('start_date', now()->startOfWeek()->toDateString());
        $end = $request->input('end_date', now()->endOfWeek()->toDateString());

        $txQ = Transaction::whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59']);
        $this->scopeToBranch($txQ, $user);

        return response()->json([
            'status' => true,
            'data' => [
                'start_date' => $start,
                'end_date' => $end,
                'sales' => (float) (clone $txQ)->where('transaction_type', 'gold_sale')->sum('amount'),
                'purchases' => (float) (clone $txQ)->where('transaction_type', 'gold_purchase')->sum('amount'),
            ],
        ]);
    }

    public function monthly(Request $request)
    {
        $user = $request->user();
        $month = $request->input('month', now()->format('Y-m'));

        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($start));

        $txQ = Transaction::whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59']);
        $this->scopeToBranch($txQ, $user);

        return response()->json([
            'status' => true,
            'data' => [
                'month' => $month,
                'sales' => (float) (clone $txQ)->where('transaction_type', 'gold_sale')->sum('amount'),
                'purchases' => (float) (clone $txQ)->where('transaction_type', 'gold_purchase')->sum('amount'),
            ],
        ]);
    }

    public function cashFlow(Request $request)
    {
        $user = $request->user();
        $from = $request->input('from_date', now()->startOfMonth()->toDateString());
        $to = $request->input('to_date', now()->toDateString());

        $txQ = Transaction::whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
        $this->scopeToBranch($txQ, $user);

        $inflows = (float) (clone $txQ)->whereIn('transaction_type', ['gold_sale', 'cash_deposit'])->sum('amount');
        $outflows = (float) (clone $txQ)->whereIn('transaction_type', ['gold_purchase', 'cash_withdrawal', 'expense', 'loan_disbursement'])->sum('amount');

        return response()->json([
            'status' => true,
            'data' => [
                'from_date' => $from,
                'to_date' => $to,
                'inflows' => $inflows,
                'outflows' => $outflows,
                'net' => $inflows - $outflows,
            ],
        ]);
    }

    public function profitLoss(Request $request)
    {
        $user = $request->user();
        $from = $request->input('from_date', now()->startOfMonth()->toDateString());
        $to = $request->input('to_date', now()->toDateString());

        $txQ = Transaction::whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
        $this->scopeToBranch($txQ, $user);

        $expQ = Expense::whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
        $this->scopeToBranch($expQ, $user);

        $revenue = (float) (clone $txQ)->where('transaction_type', 'gold_sale')->sum('amount');
        $cogs = (float) (clone $txQ)->where('transaction_type', 'gold_purchase')->sum('amount');
        $expenses = (float) $expQ->sum('amount');

        return response()->json([
            'status' => true,
            'data' => [
                'from_date' => $from,
                'to_date' => $to,
                'revenue' => $revenue,
                'cost_of_goods' => $cogs,
                'expenses' => $expenses,
                'gross_profit' => $revenue - $cogs,
                'net_profit' => $revenue - $cogs - $expenses,
            ],
        ]);
    }

    public function balanceSheet(Request $request)
    {
        $user = $request->user();
        $asAt = $request->input('as_at_date', now()->toDateString());

        $balQ = BranchBalance::query();
        if (!$this->isGlobal($user)) $balQ->where('branch_id', $user->assigned_branch);

        $cash = (float) (clone $balQ)->where('balance_type', 'cash')->sum('amount');
        $gold = (float) (clone $balQ)->where('balance_type', 'gold')->sum('amount');

        $loanQ = Loan::where('status', 'approved');
        if (!$this->isGlobal($user)) $loanQ->where('branch_id', $user->assigned_branch);
        $loans = (float) $loanQ->sum('amount');

        return response()->json([
            'status' => true,
            'data' => [
                'as_at_date' => $asAt,
                'total_assets' => $cash + $gold,
                'cash' => $cash,
                'gold' => $gold,
                'loans_outstanding' => $loans,
            ],
        ]);
    }

    public function branch(Request $request)
    {
        $user = $request->user();
        $branches = $this->isGlobal($user)
            ? \App\Models\Branch::with('balances')->get()
            : \App\Models\Branch::with('balances')->where('id', $user->assigned_branch)->get();

        $data = $branches->map(function ($b) {
            $cash = $b->balances->where('balance_type', 'cash')->first()->amount ?? 0;
            $gold = $b->balances->where('balance_type', 'gold')->first()->amount ?? 0;
            return [
                'branch_name' => $b->name,
                'cash' => (float) $cash,
                'gold' => (float) $gold,
                'transactions' => (int) Transaction::where('branch_id', $b->id)->count(),
            ];
        });

        return response()->json(['status' => true, 'data' => $data]);
    }
}