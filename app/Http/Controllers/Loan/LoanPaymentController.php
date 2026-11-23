<?php

namespace App\Http\Controllers\Loan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\BranchBalance;
use App\Models\Transaction;

class LoanPaymentController extends Controller
{
    /**
     * Display a listing of loan payments.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = LoanPayment::with(['loan', 'loan.branch', 'loan.supplier', 'loan.creator']);

        // Filter by branch
        if ($user->role !== 'director') {
            $query->whereHas('loan', function ($q) use ($user) {
                $q->where('branch_id', $user->assigned_branch);
            });
        }

        // Filter by loan ID
        if ($request->filled('loan_id')) {
            $query->where('loan_id', $request->loan_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('payment_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('payment_date', '<=', $request->to_date);
        }

        $payments = $query->orderBy('payment_date', 'desc')
            ->paginate(20)
            ->appends($request->all());

        // Statistics
        $totalPayments    = (clone $query)->sum('amount');
        $verifiedPayments = (clone $query)->where('status', 'verified')->sum('amount');
        $pendingPayments  = (clone $query)->where('status', 'pending')->count();

        // Get loans for dropdown
        $loans = Loan::where('status', 'approved')
            ->when($user->role !== 'director', function ($q) use ($user) {
                return $q->where('branch_id', $user->assigned_branch);
            })
            ->get();

        return view('loan.payments.index', compact(
            'payments',
            'totalPayments',
            'verifiedPayments',
            'pendingPayments',
            'loans'
        ));
    }

    /**
     * Show the form for creating a new loan payment.
     */
    public function create($loanId = null)
    {
        $user = Auth::user();

        $loans = Loan::with(['branch', 'supplier'])
            ->where('status', 'approved')
            ->when($user->role !== 'director', function ($q) use ($user) {
                return $q->where('branch_id', $user->assigned_branch);
            })
            ->get();

        $selectedLoan = null;
        if ($loanId) {
            $selectedLoan = Loan::with(['branch', 'supplier'])
                ->where('id', $loanId)
                ->where('status', 'approved')
                ->first();
        }

        return view('loan.payments.create', compact('loans', 'selectedLoan'));
    }

    /**
     * Store a newly created loan payment.
     *
     * - Cash payments are auto-verified immediately AND the branch balance
     *   is credited in the same transaction.
     * - Non-cash payments stay pending until verify() is called.
     */
    public function store(Request $request)
    {
        $request->validate([
            'loan_id'        => 'required|exists:loans,id',
            'amount'         => 'required|numeric|min:0.01',
            'payment_date'   => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,mobile_money',
            'reference'      => 'nullable|string|max:100',
            'comments'       => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $loan = Loan::findOrFail($request->loan_id);

            // Only approved or completed loans accept payments
            if (!in_array($loan->status, ['approved', 'completed'])) {
                throw new \Exception('This loan is not active for payments.');
            }

            // Remaining balance check
            $totalPaid      = LoanPayment::where('loan_id', $loan->id)
                ->where('status', 'verified')
                ->sum('amount');
            $totalPayable   = $loan->amount + ($loan->amount * $loan->interest_rate / 100);
            $remaining      = $totalPayable - $totalPaid;

            if ($request->amount > $remaining) {
                throw new \Exception(
                    'Payment amount exceeds remaining balance. Remaining: '
                    . number_format($remaining, 0) . ' TZS'
                );
            }

            // Create payment record (pending by default)
            $payment = LoanPayment::create([
                'loan_id'        => $loan->id,
                'amount'         => $request->amount,
                'payment_date'   => $request->payment_date,
                'payment_method' => $request->payment_method,
                'reference'      => $request->reference,
                'comments'       => $request->comments,
                'status'         => 'pending',
            ]);

            // Auto-verify cash payments immediately (includes balance update)
            if ($request->payment_method === 'cash') {
                $this->approvePayment($payment, $user->id);
            }

            DB::commit();

            $message = $request->payment_method === 'cash'
                ? sprintf(
                    '✅ Cash payment of TZS %s recorded & verified. Branch balance updated.',
                    number_format($request->amount)
                )
                : sprintf(
                    '✅ Payment of TZS %s recorded. Awaiting approval.',
                    number_format($request->amount)
                );

            return redirect()->route('loans.payments.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to record payment: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified loan payment.
     */
    public function show($id)
    {
        $payment = LoanPayment::with(['loan', 'loan.branch', 'loan.supplier', 'loan.creator'])
            ->findOrFail($id);

        $user = Auth::user();
        if ($user->role !== 'director' && $payment->loan->branch_id != $user->assigned_branch) {
            abort(403, 'You do not have access to this payment.');
        }

        return view('loan.payments.show', compact('payment'));
    }

    /**
     * Verify a loan payment (approval step).
     */
    public function verify($id)
    {
        $payment = LoanPayment::with(['loan'])->findOrFail($id);

        if ($payment->status !== 'pending') {
            return redirect()->route('loans.payments.index')
                ->with('warning', '⚠️ This payment has already been ' . $payment->status);
        }

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $this->approvePayment($payment, $user->id);

            DB::commit();

            return redirect()->route('loans.payments.index')
                ->with('success', '✅ Payment of TZS '
                    . number_format($payment->amount)
                    . ' verified successfully. Branch balance updated.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to verify payment: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified loan payment.
     * If the payment was already verified, reverse the branch balance credit.
     */
    public function destroy($id)
    {
        $payment = LoanPayment::with(['loan'])->findOrFail($id);

        try {
            DB::beginTransaction();

            if ($payment->status === 'verified') {
                // Reverse the branch cash balance credit
                $cashBalance = BranchBalance::where('branch_id', $payment->loan->branch_id)
                    ->where('balance_type', 'cash')
                    ->first();

                if ($cashBalance) {
                    $cashBalance->amount -= $payment->amount;
                    $cashBalance->save();
                }

                // Re-open the loan if it was marked completed
                if ($payment->loan->status === 'completed') {
                    $payment->loan->status = 'approved';
                    $payment->loan->save();
                }
            }

            $payment->delete();
            DB::commit();

            return redirect()->route('loans.payments.index')
                ->with('success', '✅ Payment deleted successfully. Branch balance adjusted.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to delete payment: ' . $e->getMessage());
        }
    }

    /**
     * Get loan details for payment form (AJAX).
     */
    public function getLoanDetails($id)
    {
        $loan = Loan::with(['branch', 'supplier'])
            ->where('status', 'approved')
            ->findOrFail($id);

        $totalPaid    = LoanPayment::where('loan_id', $loan->id)
            ->where('status', 'verified')
            ->sum('amount');

        $totalPayable = $loan->amount + ($loan->amount * $loan->interest_rate / 100);
        $remaining    = $totalPayable - $totalPaid;

        return response()->json([
            'loan'              => $loan,
            'total_paid'        => $totalPaid,
            'total_payable'     => $totalPayable,
            'remaining_balance' => $remaining,
            'supplier'          => $loan->supplier->name ?? 'N/A',
            'branch'            => $loan->branch->name ?? 'N/A',
        ]);
    }

    /**
     * -----------------------------------------------------------------
     * Shared approval logic.
     *
     * Marks the payment as verified, credits the branch cash balance,
     * and flips the loan to 'completed' if fully repaid.
     *
     * MUST be called inside an active DB transaction.
     * -----------------------------------------------------------------
     */
    private function approvePayment(LoanPayment $payment, int $userId): void
    {
        // 1. Mark payment verified
        $payment->status      = 'verified';
        $payment->verified_by = $userId;
        $payment->verified_at = now();
        $payment->save();

        // 2. Credit branch cash balance (create the row if it doesn't exist)
        $cashBalance = BranchBalance::firstOrCreate(
            [
                'branch_id'    => $payment->loan->branch_id,
                'balance_type' => 'cash',
            ],
            [
                'currency'        => 'TZS',
                'amount'          => 0,
                'minimum_balance' => 0,
            ]
        );

        $cashBalance->amount += $payment->amount;
        $cashBalance->save();

        // 3. Check if loan is fully paid
        $totalPaid    = LoanPayment::where('loan_id', $payment->loan_id)
            ->where('status', 'verified')
            ->sum('amount');

        $loan         = $payment->loan;
        $totalPayable = $loan->amount + ($loan->amount * $loan->interest_rate / 100);

        if ($totalPaid >= $totalPayable) {
            $loan->status = 'completed';
            $loan->save();
        }
    }
}