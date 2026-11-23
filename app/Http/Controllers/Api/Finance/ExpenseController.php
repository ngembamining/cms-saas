<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Expense;
use App\Models\Branch;
use App\Models\BranchBalance;
use App\Models\ExpenseCategory;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isGlobal = in_array($user->role, ['director', 'admin', 'accountant']);

        $query = Expense::with('branch');

        if (!$isGlobal) {
            $query->where('branch_id', $user->assigned_branch);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $expenses = $query->latest()->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $expenses->map(fn($e) => [
                'id' => (string) $e->id,
                'branch_name' => $e->branch->name ?? 'Unknown',
                'category' => $e->category,
                'amount_tzs' => (float) $e->amount,
                'description' => $e->description,
                'date' => $e->created_at->format('d M Y, H:i'),
            ]),
            'meta' => [
                'current_page' => $expenses->currentPage(),
                'last_page' => $expenses->lastPage(),
                'total' => $expenses->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id'   => 'required|exists:branches,id',
            'category'    => 'required|string|max:100',
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $isGlobal = in_array($user->role, ['director', 'admin', 'accountant']);

        if (!$isGlobal && (int) $request->branch_id !== (int) $user->assigned_branch) {
            return response()->json([
                'status' => false,
                'message' => 'You cannot record expenses for this branch.',
            ], 403);
        }

        try {
            DB::beginTransaction();

            $cashBalance = BranchBalance::firstOrCreate(
                ['branch_id' => $request->branch_id, 'balance_type' => 'cash'],
                ['currency' => 'TZS', 'amount' => 0, 'minimum_balance' => 0]
            );

            if ($cashBalance->amount < $request->amount) {
                throw new \RuntimeException(
                    'Insufficient branch cash. Available: TZS '
                    . number_format($cashBalance->amount, 0)
                );
            }

            $expense = Expense::create([
                'branch_id'   => $request->branch_id,
                'category'    => $request->category,
                'amount'      => $request->amount,
                'description' => $request->description,
                'recorded_by' => $user->id,
            ]);

            $cashBalance->amount -= $expense->amount;
            $cashBalance->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Expense recorded successfully.',
                'data' => ['id' => $expense->id],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function show(Request $request, $id)
    {
        $expense = Expense::with('branch')->findOrFail($id);
        $user = $request->user();

        if (!in_array($user->role, ['director', 'admin', 'accountant']) &&
            $expense->branch_id != $user->assigned_branch) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'id' => (string) $expense->id,
                'branch_name' => $expense->branch->name ?? 'Unknown',
                'category' => $expense->category,
                'amount_tzs' => (float) $expense->amount,
                'description' => $expense->description,
                'date' => $expense->created_at->format('d M Y, H:i'),
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);
        $user = $request->user();
        $isGlobal = in_array($user->role, ['director', 'admin', 'accountant']);

        if (!$isGlobal && $expense->branch_id != $user->assigned_branch) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $request->validate([
            'category'    => 'required|string|max:100',
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $delta = $request->amount - $expense->amount;
            $cashBalance = BranchBalance::firstOrCreate(
                ['branch_id' => $expense->branch_id, 'balance_type' => 'cash'],
                ['currency' => 'TZS', 'amount' => 0, 'minimum_balance' => 0]
            );

            if ($delta > 0 && $cashBalance->amount < $delta) {
                throw new \RuntimeException('Insufficient cash to cover the increase.');
            }

            $cashBalance->amount -= $delta;
            $cashBalance->save();

            $expense->update([
                'category'    => $request->category,
                'amount'      => $request->amount,
                'description' => $request->description,
            ]);

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Expense updated.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);
        $user = $request->user();
        $isGlobal = in_array($user->role, ['director', 'admin', 'accountant']);

        if (!$isGlobal && $expense->branch_id != $user->assigned_branch) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        try {
            DB::beginTransaction();

            $cashBalance = BranchBalance::firstOrCreate(
                ['branch_id' => $expense->branch_id, 'balance_type' => 'cash'],
                ['currency' => 'TZS', 'amount' => 0, 'minimum_balance' => 0]
            );
            $cashBalance->amount += $expense->amount;
            $cashBalance->save();

            $expense->delete();

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Expense deleted.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function categories(Request $request)
    {
        $categories = ExpenseCategory::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['status' => true, 'data' => $categories]);
    }

    public function lookupCategories(Request $request)
    {
        return $this->categories($request);
    }

    public function getStats(Request $request)
    {
        $user = $request->user();
        $isGlobal = in_array($user->role, ['director', 'admin', 'accountant']);

        $query = Expense::query();
        if (!$isGlobal) {
            $query->where('branch_id', $user->assigned_branch);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'total' => (float) (clone $query)->sum('amount'),
                'count' => (int) (clone $query)->count(),
                'this_month' => (float) (clone $query)->whereMonth('created_at', now()->month)->sum('amount'),
            ],
        ]);
    }

    public function pendingApprovals(Request $request)
    {
        return response()->json(['status' => true, 'count' => 0]);
    }

    public function approve(Request $request, $id) { return response()->json(['status' => false, 'message' => 'Not implemented.'], 501); }
    public function reject(Request $request, $id)  { return response()->json(['status' => false, 'message' => 'Not implemented.'], 501); }
    public function hold(Request $request, $id)    { return response()->json(['status' => false, 'message' => 'Not implemented.'], 501); }
}