<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BranchBalance;
use App\Models\Branch;

class BranchBalanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isGlobal = in_array($user->role, ['director', 'admin', 'accountant']);

        $q = BranchBalance::with('branch');
        if (!$isGlobal) $q->where('branch_id', $user->assigned_branch);

        return response()->json([
            'status' => true,
            'data' => $q->get()->map(fn($b) => [
                'branch_name' => $b->branch->name ?? 'Unknown',
                'type' => $b->balance_type,
                'amount' => (float) $b->amount,
                'currency' => $b->currency,
            ]),
        ]);
    }

    public function summary(Request $request)
    {
        $user = $request->user();
        $isGlobal = in_array($user->role, ['director', 'admin', 'accountant']);

        $q = BranchBalance::query();
        if (!$isGlobal) $q->where('branch_id', $user->assigned_branch);

        return response()->json([
            'status' => true,
            'data' => [
                'total_cash' => (float) (clone $q)->where('balance_type', 'cash')->sum('amount'),
                'total_gold' => (float) (clone $q)->where('balance_type', 'gold')->sum('amount'),
            ],
        ]);
    }

    public function history(Request $request)
    {
        return response()->json(['status' => true, 'data' => []]);
    }

    public function branch(Request $request, $id)
    {
        $b = Branch::with('balances')->findOrFail($id);
        return response()->json([
            'status' => true,
            'data' => [
                'branch_name' => $b->name,
                'cash' => (float) ($b->balances->where('balance_type', 'cash')->first()->amount ?? 0),
                'gold' => (float) ($b->balances->where('balance_type', 'gold')->first()->amount ?? 0),
            ],
        ]);
    }
}