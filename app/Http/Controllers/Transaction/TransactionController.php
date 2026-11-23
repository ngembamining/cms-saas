<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchBalance;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    protected array $inflowTypes = ['gold_sale', 'cash_deposit', 'loan_repayment', 'fund_transfer'];

    /* ------------------------------------------------------------------ */
    /*  INDEX                                                              */
    /* ------------------------------------------------------------------ */
    public function index(Request $request)
    {
        $query = Transaction::with(['branch', 'customer', 'supplier', 'loan'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->search;
                $q->where(function ($q2) use ($s) {
                    $q2->where('transaction_code', 'like', "%{$s}%")
                       ->orWhere('description', 'like', "%{$s}%")
                       ->orWhere('reference_no', 'like', "%{$s}%");
                });
            })
            ->when($request->filled('type'), fn ($q) => $q->where('transaction_type', $request->type))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->filled('start_date'), fn ($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->filled('end_date'), fn ($q) => $q->whereDate('created_at', '<=', $request->end_date));

        // Stats respecting filters
        $stats = [
            'total_amount'   => (float) (clone $query)->whereIn('transaction_type', ['gold_sale', 'gold_purchase'])->sum('amount'),
            'total_count'    => (int) (clone $query)->count(),
            'gold_sales'     => (float) (clone $query)->where('transaction_type', 'gold_sale')->sum('amount'),
            'gold_purchases' => (float) (clone $query)->where('transaction_type', 'gold_purchase')->sum('amount'),
        ];

        $transactions = $query->latest('created_at')->paginate(25)->withQueryString();
        $branches     = Branch::orderBy('name')->get();

        return view('transactions.index', compact('transactions', 'stats', 'branches'));
    }

    /* ------------------------------------------------------------------ */
    /*  SHOW                                                               */
    /* ------------------------------------------------------------------ */
    public function show(string $id)
    {
        $transaction = Transaction::with([
            'branch',
            'customer',
            'supplier',
            'loan.supplier',
            'loan.branch',
            'recordedBy',
            'verifiedBy',
        ])->findOrFail($id);

        $related = collect();

        if ($transaction->loan_id) {
            $related = Transaction::where('loan_id', $transaction->loan_id)
                ->where('id', '!=', $transaction->id)
                ->latest()
                ->limit(10)
                ->get();
        }

        if ($transaction->related_transaction) {
            if ($linked = Transaction::find($transaction->related_transaction)) {
                $related = $related->push($linked);
            }
        }

        return view('transactions.show', compact('transaction', 'related'));
    }

    /* ------------------------------------------------------------------ */
    /*  REVERSE  (replaces destroy)                                        */
    /* ------------------------------------------------------------------ */
    public function reverse(Request $request, string $id)
    {
        $user = Auth::user();

        if (! in_array($user->role, ['branch_manager', 'director'], true)) {
            abort(403, 'You are not authorized to reverse transactions.');
        }

        $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $transaction = Transaction::with('loan')->findOrFail($id);

        if ($transaction->status === 'reversed') {
            return back()->with('error', 'This transaction has already been reversed.');
        }

        try {
            DB::transaction(function () use ($transaction, $request, $user) {

                // --- 1. Reverse cash impact for non-gold transactions -------
                $isGold = in_array($transaction->transaction_type, ['gold_sale', 'gold_purchase'], true);

                if (! $isGold) {
                    $balance = BranchBalance::where('branch_id', $transaction->branch_id)
                        ->where('balance_type', 'cash')
                        ->lockForUpdate()
                        ->first();

                    if ($balance) {
                        $delta = $this->isInflow($transaction->transaction_type)
                            ? -1 * (float) $transaction->amount   // subtract what came in
                            : (float) $transaction->amount;        // add back what went out
                        $balance->amount = (float) $balance->amount + $delta;
                        $balance->save();
                    }
                }

                // --- 2. Restore loan status if applicable --------------------
                if ($transaction->loan) {
                    if ($transaction->transaction_type === 'loan_disbursement') {
                        $transaction->loan->update(['status' => 'pending']);
                    } elseif ($transaction->transaction_type === 'loan_repayment') {
                        if (method_exists($transaction->loan, 'recalculateStatus')) {
                            $transaction->loan->recalculateStatus();
                        }
                    }
                }

                // --- 3. Mark as reversed ------------------------------------
                $transaction->update([
                    'status'          => 'reversed',
                    'reversal_reason' => $request->reason,
                ]);

                // --- 4. Create a mirror reversal entry (audit trail) ---------
                // Only if you want a matching entry in the ledger. Otherwise skip.
                // Transaction::create([...]); // optional

                Log::info('Transaction reversed', [
                    'id'         => $transaction->id,
                    'code'       => $transaction->transaction_code,
                    'by'         => $user->username,
                    'reason'     => $request->reason,
                ]);
            });

            return redirect()
                ->route('transactions.show', $transaction->id)
                ->with('success', 'Transaction reversed successfully.');
        } catch (\Throwable $e) {
            Log::error('Transaction reverse failed', ['id' => $id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to reverse transaction: ' . $e->getMessage());
        }
    }

    /* ------------------------------------------------------------------ */
    /*  EXPORT                                                             */
    /* ------------------------------------------------------------------ */
    public function export(Request $request)
    {
        $query = Transaction::with(['branch', 'customer', 'supplier'])
            ->when($request->filled('type'), fn ($q) => $q->where('transaction_type', $request->type))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->filled('start_date'), fn ($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->filled('end_date'), fn ($q) => $q->whereDate('created_at', '<=', $request->end_date))
            ->latest();

        $filename = 'transactions_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Code', 'Type', 'Branch', 'Party', 'Amount',
                'Payment Method', 'Status', 'Description', 'Date',
            ]);

            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $tx) {
                    $party = $tx->customer->name ?? $tx->supplier->name ?? '';
                    fputcsv($out, [
                        $tx->transaction_code,
                        ucfirst(str_replace('_', ' ', $tx->transaction_type)),
                        $tx->branch->name ?? '',
                        $party,
                        number_format((float) $tx->amount, 2, '.', ''),
                        $tx->payment_method ?? 'cash',
                        $tx->status,
                        $tx->description,
                        $tx->created_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    /* ------------------------------------------------------------------ */
    /*  SUMMARY                                                            */
    /* ------------------------------------------------------------------ */
    public function summary(Request $request)
    {
        $start = $request->input('start_date', now()->startOfMonth()->toDateString());
        $end   = $request->input('end_date', now()->toDateString());

        $rows = Transaction::selectRaw('
                transaction_type,
                COUNT(*) as count,
                SUM(amount) as total
            ')
            ->whereBetween(DB::raw('DATE(created_at)'), [$start, $end])
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id))
            ->groupBy('transaction_type')
            ->get();

        $totals = [
            'inflow'  => $rows->whereIn('transaction_type', $this->inflowTypes)->sum('total'),
            'outflow' => $rows->whereNotIn('transaction_type', $this->inflowTypes)->sum('total'),
        ];
        $totals['net'] = $totals['inflow'] - $totals['outflow'];

        return view('transactions.summary', compact('rows', 'totals', 'start', 'end'));
    }

    /* ------------------------------------------------------------------ */
    /*  API: SEARCH                                                        */
    /* ------------------------------------------------------------------ */
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

        return response()->json($results);
    }

    /* ------------------------------------------------------------------ */
    /*  API: DAILY SUMMARY                                                 */
    /* ------------------------------------------------------------------ */
    public function dailySummary(Request $request)
    {
        $date = $request->input('date', now()->toDateString());

        $data = Transaction::selectRaw('
                transaction_type,
                COUNT(*) as count,
                SUM(amount) as total
            ')
            ->whereDate('created_at', $date)
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id))
            ->groupBy('transaction_type')
            ->get();

        return response()->json([
            'date'   => $date,
            'totals' => $data,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  API: GOLD PRICE                                                    */
    /* ------------------------------------------------------------------ */
    public function getGoldPrice()
    {
        // Hook to your gold rate source — table, API, or config
        $rate = DB::table('exchange_rates')
            ->orderByDesc('effective_date')
            ->value('gold_rate');

        return response()->json([
            'gold_rate'       => $rate,
            'effective_date'  => now()->toDateString(),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Helper                                                             */
    /* ------------------------------------------------------------------ */
    protected function isInflow(string $type): bool
    {
        return in_array($type, $this->inflowTypes, true);
    }
}