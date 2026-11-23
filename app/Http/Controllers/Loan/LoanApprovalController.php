<?php

namespace App\Http\Controllers\Loan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Loan;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\BranchBalance;
use App\Models\User;
use App\Models\Transaction;

class LoanApprovalController extends Controller
{
    /**
     * Display a listing of loans for approval.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Build query - show all loans that are pending or need review
        $query = Loan::with(['branch', 'supplier', 'creator'])
            ->whereIn('status', ['pending', 'approved', 'rejected']);
        
        // Branch filter for non-director users
        if ($user->role !== 'director') {
            $query->where('branch_id', $user->assigned_branch);
        }
        
        // Additional filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        if ($request->filled('min_amount')) {
            $query->where('amount', '>=', $request->min_amount);
        }
        
        if ($request->filled('max_amount')) {
            $query->where('amount', '<=', $request->max_amount);
        }
        
        // Search by supplier name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('supplier', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
        }
        
        $query->orderBy('created_at', 'desc');
        $loans = $query->paginate(20)->appends($request->all());
        
        // Statistics
        $totalPending = Loan::where('status', 'pending')
            ->when($user->role !== 'director', function ($q) use ($user) {
                return $q->where('branch_id', $user->assigned_branch);
            })
            ->count();
            
        $totalApproved = Loan::where('status', 'approved')
            ->when($user->role !== 'director', function ($q) use ($user) {
                return $q->where('branch_id', $user->assigned_branch);
            })
            ->count();
            
        $totalRejected = Loan::where('status', 'rejected')
            ->when($user->role !== 'director', function ($q) use ($user) {
                return $q->where('branch_id', $user->assigned_branch);
            })
            ->count();
        
        $totalAmount = Loan::where('status', 'pending')
            ->when($user->role !== 'director', function ($q) use ($user) {
                return $q->where('branch_id', $user->assigned_branch);
            })
            ->sum('amount');
        
        // Get branches for filter
        $branches = Branch::when($user->role !== 'director', function ($q) use ($user) {
            return $q->where('id', $user->assigned_branch);
        })->get();
        
        // Get pending count for badge
        $pendingCount = $totalPending;
        
        return view('loan.approvals.index', compact(
            'loans',
            'totalPending',
            'totalApproved',
            'totalRejected',
            'totalAmount',
            'branches',
            'pendingCount'
        ));
    }

    /**
     * Display pending loans for approval.
     */
    public function pending(Request $request)
    {
        $user = Auth::user();
        
        $query = Loan::with(['branch', 'supplier', 'creator'])
            ->where('status', 'pending');
        
        if ($user->role !== 'director') {
            $query->where('branch_id', $user->assigned_branch);
        }
        
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        if ($request->filled('min_amount')) {
            $query->where('amount', '>=', $request->min_amount);
        }
        
        if ($request->filled('max_amount')) {
            $query->where('amount', '<=', $request->max_amount);
        }
        
        $loans = $query->orderBy('created_at', 'asc')->paginate(20)->appends($request->all());
        
        return view('loan.approvals.pending', compact('loans'));
    }

    /**
     * Show the form for reviewing a loan application.
     */
    public function review($id)
    {
        $loan = Loan::with(['branch', 'supplier', 'creator'])
            ->where('status', 'pending')
            ->findOrFail($id);
        
        $user = Auth::user();
        if ($user->role !== 'director' && $loan->branch_id != $user->assigned_branch) {
            abort(403, 'You do not have access to this loan.');
        }
        
        // Get branch cash balance
        $branchBalance = BranchBalance::where('branch_id', $loan->branch_id)
            ->where('balance_type', 'cash')
            ->first();
        
        $availableCash = $branchBalance->amount ?? 0;
        
        // Calculate total payable
        $totalPayable = $loan->amount + ($loan->amount * $loan->interest_rate / 100);
        $monthlyPayment = $loan->duration_months > 0 ? $totalPayable / $loan->duration_months : 0;
        
        return view('loan.approvals.review', compact(
            'loan',
            'availableCash',
            'totalPayable',
            'monthlyPayment'
        ));
    }

    /**
     * Approve a loan application.
     */
    public function approve(Request $request, $id)
    {
        $loan = Loan::findOrFail($id);
        
        if ($loan->status !== 'pending') {
            return redirect()->route('loans.approvals.index')
                ->with('warning', 'This loan has already been processed.');
        }

        $request->validate([
            'comments' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            
            // Check if branch has enough cash
            $branchBalance = BranchBalance::where('branch_id', $loan->branch_id)
                ->where('balance_type', 'cash')
                ->first();
            
            if (!$branchBalance || $branchBalance->amount < $loan->amount) {
                throw new \Exception('Insufficient cash balance. Available: ' . number_format($branchBalance->amount ?? 0) . ' TZS');
            }

            // Update loan status
            $loan->status = 'approved';
            $loan->approved_by = $user->id;
            $loan->approved_date = now();
            $loan->comments = $request->comments;
            $loan->save();

            // Record the transaction (loan disbursement)
            $transaction = Transaction::create([
                'transaction_code' => 'LD' . date('Ymd') . '-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT),
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

            // Deduct from branch cash using direct update to avoid timestamp issues
            DB::table('branch_balances')
                ->where('id', $branchBalance->id)
                ->update(['amount' => $branchBalance->amount - $loan->amount]);

            DB::commit();

            return redirect()->route('loans.approvals.index')
                ->with('success', '✅ Loan #' . $loan->id . ' approved successfully! Amount: TZS ' . number_format($loan->amount));

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to approve loan: ' . $e->getMessage());
        }
    }

    /**
     * Reject a loan application.
     */
    public function reject(Request $request, $id)
    {
        $loan = Loan::findOrFail($id);
        
        if ($loan->status !== 'pending') {
            return redirect()->route('loans.approvals.index')
                ->with('warning', 'This loan has already been processed.');
        }

        $request->validate([
            'comments' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();

            $loan->status = 'rejected';
            $loan->approved_by = $user->id;
            $loan->approved_date = now();
            $loan->comments = $request->comments;
            $loan->save();

            DB::commit();

            return redirect()->route('loans.approvals.index')
                ->with('info', 'ℹ️ Loan #' . $loan->id . ' rejected. Reason: ' . $request->comments);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to reject loan: ' . $e->getMessage());
        }
    }

    /**
     * Hold a loan application (for further review).
     */
    public function hold(Request $request, $id)
    {
        $loan = Loan::findOrFail($id);
        
        if ($loan->status !== 'pending') {
            return redirect()->route('loans.approvals.index')
                ->with('warning', 'This loan has already been processed.');
        }

        $request->validate([
            'comments' => 'nullable|string|max:500',
        ]);

        try {
            $loan->status = 'hold';
            $loan->comments = $request->comments ?? 'Under further review';
            $loan->save();

            return redirect()->route('loans.approvals.index')
                ->with('info', 'ℹ️ Loan #' . $loan->id . ' placed on hold for further review.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', '❌ Failed to hold loan: ' . $e->getMessage());
        }
    }

    /**
     * Get loan statistics for dashboard.
     */
    public function getStats()
    {
        $user = Auth::user();
        
        $query = Loan::query();
        
        if ($user->role !== 'director') {
            $query->where('branch_id', $user->assigned_branch);
        }
        
        $stats = [
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count(),
            'total_amount' => (clone $query)->where('status', 'pending')->sum('amount'),
        ];
        
        return response()->json($stats);
    }
}