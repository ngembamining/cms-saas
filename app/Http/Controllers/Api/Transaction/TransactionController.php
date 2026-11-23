<?php

namespace App\Http\Controllers\Api\Transaction; // <--- MUST HAVE \Transaction

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\BranchBalance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    protected array $inflowTypes = ['gold_sale', 'cash_deposit', 'loan_repayment', 'fund_transfer'];

    /**
     * GET /api/transactions
     * List transactions for the mobile app.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $isGlobalView = in_array($user->role, ['director', 'admin', 'accountant']);

        $query = Transaction::with(['branch', 'customer', 'supplier', 'recordedBy'])
            ->latest('created_at');

        // Apply branch filter for managers
        if (!$isGlobalView) {
            $query->where('branch_id', $user->assigned_branch);
        }

        // Apply Search
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q2) use ($s) {
                $q2->where('transaction_code', 'like', "%{$s}%")
                   ->orWhere('description', 'like', "%{$s}%")
                   ->orWhere('reference_no', 'like', "%{$s}%");
            });
        }

        // Apply Filter
        if ($request->filled('filter') && $request->filter !== 'ALL') {
            $filter = strtolower($request->filter);
            if ($filter === 'gold_purchases') $query->where('transaction_type', 'gold_purchase');
            if ($filter === 'gold_sales') $query->where('transaction_type', 'gold_sale');
            if ($filter === 'cash') $query->whereIn('transaction_type', ['cash_deposit', 'cash_withdrawal']);
        }

        $transactions = $query->limit(100)->get()->map(function ($tx) {
            return $this->formatTransaction($tx);
        });

        return response()->json([
            'status' => true,
            'data' => $transactions
        ]);
    }

    /**
     * GET /api/transactions/{id}
     * Show single transaction details.
     */
    public function show(Request $request, $id)
    {
        $tx = Transaction::with(['branch', 'customer', 'supplier', 'recordedBy', 'loan'])
            ->findOrFail($id);
        
        return response()->json([
            'status' => true,
            'data' => $this->formatTransaction($tx)
        ]);
    }

    /**
     * POST /api/transactions/{id}/reverse
     * Reverse a transaction.
     */
    public function reverse(Request $request, $id)
    {
        $user = $request->user();

        if (! in_array($user->role, ['branch_manager', 'director'], true)) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $transaction = Transaction::with('loan')->findOrFail($id);

        if ($transaction->status === 'reversed') {
            return response()->json(['status' => false, 'message' => 'Already reversed.'], 400);
        }

        try {
            DB::transaction(function () use ($transaction, $request, $user) {
                $isGold = in_array($transaction->transaction_type, ['gold_sale', 'gold_purchase'], true);

                if (! $isGold) {
                    $balance = BranchBalance::where('branch_id', $transaction->branch_id)
                        ->where('balance_type', 'cash')
                        ->lockForUpdate()
                        ->first();

                    if ($balance) {
                        $delta = $this->isInflow($transaction->transaction_type)
                            ? -1 * (float) $transaction->amount
                            : (float) $transaction->amount;
                        $balance->amount = (float) $balance->amount + $delta;
                        $balance->save();
                    }
                }

                if ($transaction->loan) {
                    if ($transaction->transaction_type === 'loan_disbursement') {
                        $transaction->loan->update(['status' => 'pending']);
                    } elseif ($transaction->transaction_type === 'loan_repayment') {
                        if (method_exists($transaction->loan, 'recalculateStatus')) {
                            $transaction->loan->recalculateStatus();
                        }
                    }
                }

                $transaction->update([
                    'status'          => 'reversed',
                    'reversal_reason' => $request->reason,
                ]);

                Log::info('Transaction reversed', [
                    'id'         => $transaction->id,
                    'code'       => $transaction->transaction_code,
                    'by'         => $user->username,
                    'reason'     => $request->reason,
                ]);
            });

            return response()->json(['status' => true, 'message' => 'Transaction reversed successfully.']);
        } catch (\Throwable $e) {
            Log::error('Transaction reverse failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/transactions/{id}
     * Destroy a transaction (soft delete if applicable).
     */
    public function destroy(Request $request, $id)
    {
        $tx = Transaction::findOrFail($id);
        
        // Optional security check: only directors can delete
        if ($request->user()->role !== 'director') {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $tx->delete();
        return response()->json(['status' => true, 'message' => 'Transaction deleted']);
    }

    /**
     * GET /api/transactions/summary
     */
    public function summary(Request $request)
    {
        $start = $request->input('start_date', now()->startOfMonth()->toDateString());
        $end   = $request->input('end_date', now()->toDateString());

        $rows = Transaction::selectRaw('transaction_type, COUNT(*) as count, SUM(amount) as total')
            ->whereBetween(DB::raw('DATE(created_at)'), [$start, $end])
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id))
            ->groupBy('transaction_type')
            ->get();

        $totals = [
            'inflow'  => $rows->whereIn('transaction_type', $this->inflowTypes)->sum('total'),
            'outflow' => $rows->whereNotIn('transaction_type', $this->inflowTypes)->sum('total'),
        ];
        $totals['net'] = $totals['inflow'] - $totals['outflow'];

        return response()->json(['status' => true, 'data' => $totals]);
    }

    /**
     * GET /api/transactions/search
     */
    public function search(Request $request)
    {
        $term = $request->input('q', '');

        $results = Transaction::with('branch:id,name')
            ->when($term, function ($q) use ($term) {
                $q->where('transaction_code', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");
            })
            ->latest()
            ->limit(20)
            ->get(['id', 'transaction_code', 'transaction_type', 'amount', 'branch_id', 'created_at']);

        return response()->json(['status' => true, 'data' => $results]);
    }

    /**
     * GET /api/transactions/daily-summary
     */
    public function dailySummary(Request $request)
    {
        $date = $request->input('date', now()->toDateString());

        $data = Transaction::selectRaw('transaction_type, COUNT(*) as count, SUM(amount) as total')
            ->whereDate('created_at', $date)
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id))
            ->groupBy('transaction_type')
            ->get();

        return response()->json([
            'status' => true,
            'data' => ['date' => $date, 'totals' => $data]
        ]);
    }

    /**
     * GET /api/transactions/gold-price
     */
    public function getGoldPrice()
    {
        $rate = DB::table('exchange_rates')->orderByDesc('effective_date')->value('gold_rate');
        return response()->json([
            'status' => true,
            'data' => ['gold_rate' => $rate, 'effective_date' => now()->toDateString()]
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Helpers                                                            */
    /* ------------------------------------------------------------------ */

    protected function isInflow(string $type): bool
    {
        return in_array($type, $this->inflowTypes, true);
    }

    /**
     * Map Laravel model to the exact shape the Kotlin app expects.
     */
    private function formatTransaction($tx)
    {
        $partyName = 'N/A';
        if ($tx->transaction_type === 'gold_purchase' && $tx->supplier) {
            $partyName = $tx->supplier->name;
        } elseif ($tx->transaction_type === 'gold_sale' && $tx->customer) {
            $partyName = $tx->customer->name;
        } elseif ($tx->customer) {
            $partyName = $tx->customer->name;
        } elseif ($tx->supplier) {
            $partyName = $tx->supplier->name;
        }

        $paymentMethod = strtoupper($tx->payment_method ?? 'CASH');
        if ($paymentMethod === 'BANK') $paymentMethod = 'BANK_TRANSFER';

        $goldItems = [];
        if (in_array($tx->transaction_type, ['gold_purchase', 'gold_sale']) && $tx->weight > 0) {
            $goldItems[] = [
                'weight_grams' => (float) $tx->weight,
                'purity_percentage' => (float) ($tx->purity ?? 99.9),
                'price_per_gram_tzs' => (float) ($tx->amount / $tx->weight),
            ];
        }

        $status = strtoupper($tx->status ?? 'COMPLETED');
        if ($status === 'REVERSED') $status = 'REJECTED';

        return [
            'id' => (string) $tx->id,
            'code' => $tx->transaction_code ?? 'TX-' . $tx->id,
            'title' => $tx->description ?? ucfirst(str_replace('_', ' ', $tx->transaction_type)),
            'type' => strtoupper($tx->transaction_type),
            'amount_tzs' => (float) $tx->amount,
            'is_credit' => in_array($tx->transaction_type, ['gold_sale', 'cash_deposit']),
            'branch_name' => $tx->branch->name ?? 'Unknown',
            'timestamp_label' => $tx->created_at->format('h:i A'),
            'date_group' => $tx->created_at->isToday() ? 'Today' : ($tx->created_at->isYesterday() ? 'Yesterday' : $tx->created_at->format('d M Y')),
            'formatted_date' => $tx->created_at->format('d M Y, h:i A'),
            'status' => $status,
            'party_name' => $partyName,
            'payment_method' => $paymentMethod,
            'recorded_by' => $tx->recordedBy->name ?? 'System',
            'gold_items' => $goldItems,
        ];
    }
}