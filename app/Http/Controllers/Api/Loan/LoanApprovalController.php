<?php

namespace App\Http\Controllers\Api\Loan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Loan;
use App\Models\BranchBalance;
use App\Models\Transaction;

class LoanApprovalController extends Controller
{
    public function pending(Request $request)
    {
        $user = $request->user();
        $isGlobal = in_array($user->role, ['director', 'admin', 'accountant']);

        $query = Loan::with(['branch', 'supplier'])->where('status', 'pending');
        if (!$isGlobal) $query->where('branch_id', $user->assigned_branch);

        $loans = $query->latest()->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $loans->map(fn($l) => [
                'id' => (string) $l->id,
                'supplier_name' => $l->supplier->name ?? 'Unknown',
                'branch_name' => $l->branch->name ?? 'Unknown',
                'amount' => (float) $l->amount,
                'date' => $l->created_at->format('d M Y'),
            ]),
            'meta' => ['current_page' => $loans->currentPage(), 'last_page' => $loans->lastPage(), 'total' => $loans->total()]
        ]);
    }

    public function approve(Request $request, $id)
    {
        $request->validate(['comments' => 'nullable|string|max:500']);
        $loan = Loan::findOrFail($id);

        if ($loan->status !== 'pending') {
            return response()->json(['status' => false, 'message' => 'Already processed'], 400);
        }

        try {
            DB::beginTransaction();
            $user = $request->user();

            $branchBalance = BranchBalance::where('branch_id', $loan->branch_id)->where('balance_type', 'cash')->first();
            if (!$branchBalance || $branchBalance->amount < $loan->amount) {
                throw new \Exception('Insufficient cash balance.');
            }

            $loan->update(['status' => 'approved', 'approved_by' => $user->id, 'approved_date' => now(), 'comments' => $request->comments]);

            Transaction::create([
                'transaction_code' => 'LD' . date('Ymd') . '-' . rand(1000, 9999),
                'branch_id' => $loan->branch_id,
                'transaction_type' => 'loan_disbursement',
                'amount' => $loan->amount,
                'supplier_id' => $loan->suppliers_id,
                'loan_id' => $loan->id,
                'description' => 'Loan disbursement - Application #' . $loan->id,
                'recorded_by' => $user->id,
                'verified_by' => $user->id,
                'status' => 'completed',
            ]);

            $branchBalance->amount -= $loan->amount;
            $branchBalance->save();

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Loan approved successfully.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate(['comments' => 'required|string|max:500']);
        $loan = Loan::findOrFail($id);

        if ($loan->status !== 'pending') return response()->json(['status' => false, 'message' => 'Already processed'], 400);

        $loan->update(['status' => 'rejected', 'approved_by' => $request->user()->id, 'approved_date' => now(), 'comments' => $request->comments]);
        return response()->json(['status' => true, 'message' => 'Loan rejected.']);
    }

    public function hold(Request $request, $id)
    {
        $request->validate(['comments' => 'nullable|string|max:500']);
        $loan = Loan::findOrFail($id);

        if ($loan->status !== 'pending') return response()->json(['status' => false, 'message' => 'Already processed'], 400);

        $loan->update(['status' => 'hold', 'comments' => $request->comments ?? 'Under further review']);
        return response()->json(['status' => true, 'message' => 'Loan placed on hold.']);
    }
}