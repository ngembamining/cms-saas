<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Branch;
use App\Models\BranchBalance;
use App\Models\Transaction;

class BranchBalanceController extends Controller
{
    /**
     * Display a listing of branch balances.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get branches based on user role
        // Directors and Accountants see ALL branches
        // Branch Managers see ONLY their branch
        if (in_array($user->role, ['director', 'accountant'])) {
            $branches = Branch::with(['balances'])->get();
        } else {
            $branches = Branch::with(['balances'])
                ->where('id', $user->assigned_branch)
                ->get();
        }

        // Sync gold in-memory so the view reads the correct value from branch->balances
        // (branch_balances gold mirrors branches.gold_available)
        $branches->each(function ($branch) {
            $goldRow = $branch->balances->where('balance_type', 'gold')->first();
            if ($goldRow) {
                $goldRow->amount = $branch->gold_available;
            } else {
                // If there's no gold row yet, synthesize one so the view doesn't fall back to 0
                $branch->setRelation('balances', $branch->balances->push(new BranchBalance([
                    'branch_id'    => $branch->id,
                    'balance_type' => 'gold',
                    'currency'     => 'g',
                    'amount'       => $branch->gold_available,
                ])));
            }
        });

        // Calculate totals (across all branches)
        $totalCash = BranchBalance::where('balance_type', 'cash')->sum('amount');
        // Gold total comes from branches.gold_available (single source of truth)
        $totalGold = Branch::sum('gold_available');
        $totalBranches = Branch::count();

        return view('finance.balances.index', compact(
            'branches',
            'totalCash',
            'totalGold',
            'totalBranches'
        ));
    }

    /**
     * Display a specific branch balance.
     */
    public function branch($id)
    {
        $branch = Branch::with(['balances'])->findOrFail($id);

        $user = Auth::user();

        // Directors and Accountants can access any branch
        // Branch Managers can only access their own branch
        if (!in_array($user->role, ['director', 'accountant']) && $branch->id != $user->assigned_branch) {
            abort(403, 'You do not have access to this branch.');
        }

        $cashBalance = $branch->balances->where('balance_type', 'cash')->first();

        // Gold is read from branches.gold_available (single source of truth).
        // We wrap it in a lightweight BranchBalance-shaped object so the view
        // keeps working without needing changes.
        $goldBalance = $branch->balances->where('balance_type', 'gold')->first();
        if ($goldBalance) {
            $goldBalance->amount = $branch->gold_available;
        } else {
            $goldBalance = new BranchBalance([
                'branch_id'    => $branch->id,
                'balance_type' => 'gold',
                'currency'     => 'g',
                'amount'       => $branch->gold_available,
            ]);
        }

        // Get recent transactions
        $recentTransactions = Transaction::with(['user'])
            ->where('branch_id', $branch->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('finance.balances.branch', compact(
            'branch',
            'cashBalance',
            'goldBalance',
            'recentTransactions'
        ));
    }

    /**
     * Display balance summary.
     */
    public function summary()
    {
        $user = Auth::user();

        // Build branch query with role-based scoping
        $branchQuery = Branch::query();
        if (!in_array($user->role, ['director', 'accountant'])) {
            $branchQuery->where('id', $user->assigned_branch);
        }
        $branches = $branchQuery->with(['balances'])->get();

        $summary = [
            'total_cash' => BranchBalance::where('balance_type', 'cash')->sum('amount'),
            // Gold total comes from branches.gold_available (single source of truth)
            'total_gold' => Branch::sum('gold_available'),
            'branches'   => $branches->map(function ($branch) {
                $cash = $branch->balances->where('balance_type', 'cash')->first();
                return [
                    'branch_name' => $branch->name ?? 'Unknown',
                    'cash'        => $cash->amount ?? 0,
                    'gold'        => $branch->gold_available ?? 0,
                ];
            })->values(),
        ];

        return response()->json($summary);
    }

    /**
     * Adjust branch balance.
     */
    public function adjust(Request $request, $branchId)
    {
        $request->validate([
            'balance_type'    => 'required|in:cash,gold',
            'amount'          => 'required|numeric',
            'adjustment_type' => 'required|in:add,subtract',
            'reason'          => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();

            // Only Directors and Accountants can adjust balances
            if (!in_array($user->role, ['director', 'accountant'])) {
                abort(403, 'Only directors and accountants can adjust branch balances.');
            }

            $branch = Branch::findOrFail($branchId);

            if ($request->balance_type === 'gold') {
                // -------------------------------------------------------------
                // GOLD — writes to BOTH branches.gold_available (source of truth)
                // and branch_balances (balance_type='gold') so they stay in sync.
                // -------------------------------------------------------------
                $currentGold = (float) ($branch->gold_available ?? 0);

                $newGold = $request->adjustment_type === 'add'
                    ? $currentGold + (float) $request->amount
                    : $currentGold - (float) $request->amount;

                if ($newGold < 0) {
                    throw new \Exception('Gold balance cannot go below zero.');
                }

                // 1) Update the source of truth
                $branch->gold_available = $newGold;
                $branch->save();

                // 2) Mirror it into branch_balances (create row if it doesn't exist)
                $goldBalance = BranchBalance::firstOrNew([
                    'branch_id'    => $branchId,
                    'balance_type' => 'gold',
                ]);
                $goldBalance->currency = 'g';
                $goldBalance->amount   = $newGold;
                $goldBalance->save();

                $newAmount = $newGold;

                // Gold adjustments are logged as a gold_purchase / gold_sale
                // so the ENUM constraint on `transactions.transaction_type` is satisfied
                // without needing a schema change.
                $txType = $request->adjustment_type === 'add'
                    ? 'gold_purchase'
                    : 'gold_sale';

            } else {
                // -------------------------------------------------------------
                // CASH — unchanged behaviour (lives only in branch_balances)
                // -------------------------------------------------------------
                $balance = BranchBalance::where('branch_id', $branchId)
                    ->where('balance_type', 'cash')
                    ->first();

                if (!$balance) {
                    throw new \Exception('Cash balance not found for this branch.');
                }

                $newAmount = $request->adjustment_type === 'add'
                    ? $balance->amount + $request->amount
                    : $balance->amount - $request->amount;

                if ($newAmount < 0) {
                    throw new \Exception('Cash balance cannot go below zero.');
                }

                $balance->amount = $newAmount;
                $balance->save();

                // Cash adjustments use the existing ENUM values
                $txType = $request->adjustment_type === 'add'
                    ? 'cash_deposit'
                    : 'cash_withdrawal';
            }

            // Record transaction for the adjustment
            Transaction::create([
                'transaction_code' => 'ADJ' . date('Ymd') . '-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT),
                'branch_id'        => $branchId,
                'transaction_type' => $txType,
                'amount'           => $request->amount,
                'description'      => $request->reason ?? 'Balance adjustment',
                'recorded_by'      => $user->id,
                'verified_by'      => $user->id,
                'status'           => 'completed',
            ]);

            DB::commit();

            return redirect()->route('finance.balances.index')
                ->with('success', sprintf(
                    '✅ %s balance adjusted successfully! New balance: %s',
                    ucfirst($request->balance_type),
                    number_format($newAmount, $request->balance_type === 'gold' ? 2 : 0)
                ));

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to adjust balance: ' . $e->getMessage());
        }
    }

    /**
     * Display balance history.
     */
    public function history()
    {
        return view('finance.balances.history');
    }

    /**
     * Get branch list for API.
     */
    public function getList()
    {
        $user = Auth::user();

        // Directors and Accountants see ALL branches
        // Branch Managers see ONLY their branch
        $branches = Branch::when(!in_array($user->role, ['director', 'accountant']), function ($q) use ($user) {
            return $q->where('id', $user->assigned_branch);
        })->select('id', 'name')->get();

        return response()->json($branches);
    }

    /**
     * Get branch balance for API.
     */
    public function getBalance($branchId)
    {
        $user = Auth::user();

        // Directors and Accountants can access any branch
        // Branch Managers can only access their own branch
        if (!in_array($user->role, ['director', 'accountant']) && $branchId != $user->assigned_branch) {
            abort(403);
        }

        $cash = BranchBalance::where('branch_id', $branchId)
            ->where('balance_type', 'cash')
            ->first();

        // Gold comes from branches.gold_available (single source of truth)
        $branch = Branch::find($branchId);
        $gold   = $branch->gold_available ?? 0;

        return response()->json([
            'cash' => $cash->amount ?? 0,
            'gold' => $gold,
        ]);
    }
}