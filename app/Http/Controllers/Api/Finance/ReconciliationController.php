<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CashReconciliation;
use App\Models\GoldReconciliation;

class ReconciliationController extends Controller
{
    private function isGlobal($user): bool
    {
        return in_array($user->role, ['director', 'admin', 'accountant']);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $cashQ = CashReconciliation::with('branch')->latest();
        if (!$this->isGlobal($user)) $cashQ->where('branch_id', $user->assigned_branch);
        $cash = $cashQ->limit(50)->get();

        $goldQ = GoldReconciliation::with('branch')->latest();
        if (!$this->isGlobal($user)) $goldQ->where('branch_id', $user->assigned_branch);
        $gold = $goldQ->limit(50)->get();

        return response()->json([
            'status' => true,
            'data' => [
                'cash' => $cash->map(fn($c) => [
                    'id' => (string) $c->id,
                    'branch_name' => $c->branch->name ?? 'Unknown',
                    'date' => $c->reconciliation_date?->format('d M Y'),
                    'opening' => (float) $c->opening_balance,
                    'closing' => (float) $c->closing_balance,
                    'variance' => (float) ($c->variance ?? 0),
                    'status' => strtoupper($c->status),
                ]),
                'gold' => $gold->map(fn($g) => [
                    'id' => (string) $g->id,
                    'branch_name' => $g->branch->name ?? 'Unknown',
                    'date' => $g->reconciliation_date?->format('d M Y'),
                    'opening' => (float) $g->opening_weight,
                    'closing' => (float) $g->closing_weight,
                ]),
            ],
        ]);
    }

    public function storeCash(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'reconciliation_date' => 'required|date',
            'opening_balance' => 'required|numeric|min:0',
            'closing_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            $system = $this->calculateSystemBalance($request->branch_id, $request->reconciliation_date);
            $variance = $request->closing_balance - ($request->opening_balance + $system);

            $rec = CashReconciliation::create([
                'branch_id' => $request->branch_id,
                'reconciliation_date' => $request->reconciliation_date,
                'opening_balance' => $request->opening_balance,
                'closing_balance' => $request->closing_balance,
                'system_balance' => $system,
                'variance' => $variance,
                'notes' => $request->notes,
                'reconciled_by' => $request->user()->id,
                'status' => 'pending',
            ]);

            return response()->json(['status' => true, 'data' => ['id' => $rec->id]], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function storeGold(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'reconciliation_date' => 'required|date',
            'opening_weight' => 'required|numeric|min:0',
            'closing_weight' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            $rec = GoldReconciliation::create([
                'branch_id' => $request->branch_id,
                'reconciliation_date' => $request->reconciliation_date,
                'opening_weight' => $request->opening_weight,
                'closing_weight' => $request->closing_weight,
                'notes' => $request->notes,
                'reconciled_by' => $request->user()->id,
            ]);

            return response()->json(['status' => true, 'data' => ['id' => $rec->id]], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function verifyCash(Request $request, $id)
    {
        if (!$this->isGlobal($request->user())) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $rec = CashReconciliation::findOrFail($id);
        $rec->update([
            'status' => 'verified',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return response()->json(['status' => true, 'message' => 'Verified.']);
    }

    public function rejectCash(Request $request, $id)
    {
        if (!$this->isGlobal($request->user())) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $rec = CashReconciliation::findOrFail($id);
        $rec->update([
            'status' => 'rejected',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return response()->json(['status' => true, 'message' => 'Rejected.']);
    }

    private function calculateSystemBalance($branchId, $date): float
    {
        $row = DB::table('transactions')
            ->where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereDate('created_at', '<=', $date)
            ->selectRaw('
                SUM(CASE WHEN transaction_type IN ("cash_deposit","gold_sale") THEN amount ELSE 0 END) as inflows,
                SUM(CASE WHEN transaction_type IN ("cash_withdrawal","expense","loan_disbursement") THEN amount ELSE 0 END) as outflows
            ')
            ->first();

        return (float) (($row->inflows ?? 0) - ($row->outflows ?? 0));
    }
}