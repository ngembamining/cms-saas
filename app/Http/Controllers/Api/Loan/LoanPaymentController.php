<?php

namespace App\Http\Controllers\Api\Loan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\BranchBalance;

class LoanPaymentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isGlobal = in_array($user->role, ['director', 'admin', 'accountant']);

        $query = LoanPayment::with(['loan', 'loan.supplier', 'loan.branch']);
        if (!$isGlobal) $query->whereHas('loan', fn($q) => $q->where('branch_id', $user->assigned_branch));
        if ($request->filled('loan_id')) $query->where('loan_id', $request->loan_id);

        $payments = $query->latest()->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $payments->map(fn($p) => [
                'id' => (string) $p->id,
                'loan_id' => (string) $p->loan_id,
                'supplier_name' => $p->loan->supplier->name ?? 'Unknown',
                'amount' => (float) $p->amount,
                'status' => strtoupper($p->status),
                'method' => strtoupper($p->payment_method),
                'date' => $p->created_at->format('d M Y'),
            ]),
            'meta' => ['current_page' => $payments->currentPage(), 'last_page' => $payments->lastPage(), 'total' => $payments->total()]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,mobile_money',
            'reference' => 'nullable|string|max:100',
            'comments' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();
            $user = $request->user();
            $loan = Loan::findOrFail($request->loan_id);

            if (!in_array($loan->status, ['approved', 'completed'])) throw new \Exception('This loan is not active for payments.');

            $totalPaid = LoanPayment::where('loan_id', $loan->id)->where('status', 'verified')->sum('amount');
            $totalPayable = $loan->amount + ($loan->amount * $loan->interest_rate / 100);
            $remaining = $totalPayable - $totalPaid;

            if ($request->amount > $remaining) throw new \Exception('Payment exceeds remaining balance.');

            $payment = LoanPayment::create([
                'loan_id' => $loan->id,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'payment_method' => $request->payment_method,
                'reference' => $request->reference,
                'comments' => $request->comments,
                'status' => 'pending',
            ]);

            if ($request->payment_method === 'cash') {
                $payment->status = 'verified';
                $payment->verified_by = $user->id;
                $payment->verified_at = now();
                $payment->save();

                $cashBalance = BranchBalance::firstOrCreate(['branch_id' => $loan->branch_id, 'balance_type' => 'cash'], ['currency' => 'TZS', 'amount' => 0]);
                $cashBalance->amount += $payment->amount;
                $cashBalance->save();

                $totalPaidNow = LoanPayment::where('loan_id', $loan->id)->where('status', 'verified')->sum('amount');
                if ($totalPaidNow >= $totalPayable) $loan->update(['status' => 'completed']);
            }

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Payment recorded.'], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function verify(Request $request, $id)
    {
        $payment = LoanPayment::with('loan')->findOrFail($id);
        if ($payment->status !== 'pending') return response()->json(['status' => false, 'message' => 'Already processed.'], 400);

        try {
            DB::beginTransaction();
            $payment->update(['status' => 'verified', 'verified_by' => $request->user()->id, 'verified_at' => now()]);
            
            $cashBalance = BranchBalance::firstOrCreate(['branch_id' => $payment->loan->branch_id, 'balance_type' => 'cash'], ['currency' => 'TZS', 'amount' => 0]);
            $cashBalance->amount += $payment->amount;
            $cashBalance->save();

            $totalPaid = LoanPayment::where('loan_id', $payment->loan_id)->where('status', 'verified')->sum('amount');
            $totalPayable = $payment->loan->amount + ($payment->loan->amount * $payment->loan->interest_rate / 100);
            if ($totalPaid >= $totalPayable) $payment->loan->update(['status' => 'completed']);

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Payment verified.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
    }
}