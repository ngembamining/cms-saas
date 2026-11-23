<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\BranchBalance;
use App\Models\Transaction;
use App\Models\Loan;

class DashboardController extends Controller
{
    /**
     * Get dashboard data for the mobile app.
     * Handles Directors/Accountants (global view) and Branch Managers (local view).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // ── 1. Determine Role & Scope ──
        // Directors, Admins, and Accountants see ALL branches.
        // Branch Managers only see their assigned branch.
        $isGlobalView = in_array($user->role, ['director', 'admin', 'accountant']);
        $branchId = $user->assigned_branch;
        
        // If they are a Branch Manager and have no branch assigned, block them
        if (!$isGlobalView && !$branchId) {
            return response()->json([
                'status' => false,
                'message' => 'No branch assigned to this user. Please contact administration.'
            ], 403);
        }

        $branch = $branchId ? Branch::find($branchId) : null;

        // ── 2. Balances ──
        if ($isGlobalView) {
            $totalGold = (float) (Branch::sum('gold_available') ?? 0);
            $totalCash = (float) (BranchBalance::where('balance_type', 'cash')->sum('amount') ?? 0);
            $branchName = "Head Office (All Branches)";
            $branchIdString = "all";
        } else {
            $totalGold = (float) ($branch->gold_available ?? 0);
            $totalCash = (float) (BranchBalance::where('branch_id', $branchId)
                ->where('balance_type', 'cash')
                ->value('amount') ?? 0);
            $branchName = $branch->name;
            $branchIdString = (string) $branchId;
        }

        // ── 3. Loan Metrics ──
        $loanQuery = Loan::query();
        if (!$isGlobalView) {
            $loanQuery->where('branch_id', $branchId);
        }
        
        $pendingLoans = (clone $loanQuery)->where('status', 'pending')->count();
        $activeLoans = (clone $loanQuery)->whereIn('status', ['approved', 'pending'])->count();
        $activeLoansValue = (float) (clone $loanQuery)->whereIn('status', ['approved', 'pending'])->sum('amount') ?? 0;

        // ── 4. Recent Transactions ──
        $txQuery = Transaction::with('branch')->orderBy('created_at', 'desc')->limit(10);
        if (!$isGlobalView) {
            $txQuery->where('branch_id', $branchId);
        }
        
        $recentTransactions = $txQuery->get()->map(function ($tx) {
            return [
                'id'             => (string) $tx->id,
                'code'           => $tx->code ?? 'TX-' . $tx->id,
                'title'          => $tx->description ?? ucfirst(str_replace('_', ' ', $tx->transaction_type)),
                'type'           => strtoupper($tx->transaction_type),
                'amount_tzs'     => (float) $tx->amount,
                'is_credit'      => in_array($tx->transaction_type, ['gold_sale', 'cash_deposit']),
                'branch_name'    => $tx->branch->name ?? 'Unknown',
                'timestamp_label'=> $tx->created_at->diffForHumans(),
            ];
        });

        // ── 5. Build the Response ──
        return response()->json([
            'status' => true,
            'data' => [
                'branch' => [
                    'id'                   => $branchIdString,
                    'name'                 => $branchName,
                    'gold_inventory_grams' => $totalGold,
                    'cash_balance_tzs'     => $totalCash,
                    'pending_approvals'    => $pendingLoans,
                    'active_loans'         => $activeLoans,
                    'active_loans_value'   => $activeLoansValue,
                ],
                'recent_transactions' => $recentTransactions,
            ]
        ]);
    }
}