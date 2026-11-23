<?php

namespace App\Http\Controllers\Loan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Loan;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\User;
use App\Models\BranchBalance;
use App\Http\Controllers\Communication\SMSController;

class LoanApplicationController extends Controller
{
    /**
     * Display a listing of loan applications with filters.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Start query
        $query = Loan::with(['branch', 'supplier', 'creator']);
        
        // Branch filter
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        // Supplier filter
        if ($request->filled('supplier_id')) {
            $query->where('suppliers_id', $request->supplier_id);
        }
        
        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Amount range filter
        if ($request->filled('min_amount')) {
            $query->where('amount', '>=', $request->min_amount);
        }
        if ($request->filled('max_amount')) {
            $query->where('amount', '<=', $request->max_amount);
        }
        
        // Interest rate range filter
        if ($request->filled('min_interest')) {
            $query->where('interest_rate', '>=', $request->min_interest);
        }
        if ($request->filled('max_interest')) {
            $query->where('interest_rate', '<=', $request->max_interest);
        }
        
        // Date range filter
        if ($request->filled('from_date')) {
            $query->whereDate('application_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('application_date', '<=', $request->to_date);
        }
        
        // Search filter (supplier name or description)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('supplier', function ($sub) use ($search) {
                    $sub->where('name', 'like', '%' . $search . '%');
                })->orWhere('description', 'like', '%' . $search . '%');
            });
        }
        
        // Branch access filter (non-director users)
        if ($user->role !== 'director') {
            $query->where('branch_id', $user->assigned_branch);
        }

        // Snapshot the fully-filtered query BEFORE pagination touches it.
        // paginate() applies limit/offset to $query itself (not a clone), so any
        // non-aggregate call (like pluck) made on $query afterwards would only
        // see the current page's 20 rows instead of the full filtered set.
        $statsQuery = clone $query;
        
        // Order by latest first
        $query->orderBy('created_at', 'desc');
        
        // Paginate results (keep filters in pagination links)
        $loans = $query->paginate(20)->appends($request->all());

        // ------------------------------------------------------------------
        // Money statistics (computed over the full filtered set, not just
        // the current page)
        // ------------------------------------------------------------------

        // Total principal disbursed across the filtered loans
        $amountLoaned = (clone $statsQuery)->sum('amount');

        // Total interest due across the filtered loans (amount * rate / 100)
        $totalInterestDue = (clone $statsQuery)->sum(DB::raw('amount * interest_rate / 100'));

        // Total payable = principal + interest
        $totalPayable = $amountLoaned + $totalInterestDue;

        // Amount actually paid so far, across all payments recorded against
        // the filtered loans (verified payments only)
        $loanIds = (clone $statsQuery)->pluck('id');
        $amountPaid = \App\Models\LoanPayment::whereIn('loan_id', $loanIds)
            ->where('status', 'verified')
            ->sum('amount');

        // Loan balance = what's still owed across the filtered loans
        $loanBalance = max($totalPayable - $amountPaid, 0);

        // Interest collected: the system doesn't split individual payments
        // into principal vs. interest, so we count interest as "collected"
        // only for loans that have reached status = completed (i.e. fully
        // paid off) — this avoids guessing how partial payments were applied.
        $interestCollected = (clone $statsQuery)
            ->where('status', 'completed')
            ->sum(DB::raw('amount * interest_rate / 100'));
        
        // Get branches and suppliers for filter dropdowns
        if ($user->role === 'director') {
            $branches = Branch::all();
            $suppliers = Supplier::all();
        } else {
            $branches = Branch::where('id', $user->assigned_branch)->get();
            $suppliers = Supplier::where('branch_id', $user->assigned_branch)->get();
        }
        
        return view('loan.applications.index', compact(
            'loans',
            'amountLoaned',
            'amountPaid',
            'loanBalance',
            'interestCollected',
            'branches',
            'suppliers'
        ));
    }

    /**
     * Show the form for creating a new loan application.
     */
    public function create()
    {
        $user = Auth::user();
        
        if ($user->role === 'director') {
            $branches = Branch::all();
        } else {
            $branches = Branch::where('id', $user->assigned_branch)->get();
        }
        
        $suppliers = Supplier::when($user->role !== 'director', function ($query) use ($user) {
            return $query->where('branch_id', $user->assigned_branch);
        })->get();
        
        return view('loan.applications.create', compact('branches', 'suppliers'));
    }

    /**
     * Store a newly created loan application.
     */
    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'amount' => 'required|numeric|min:1000',
            'interest_rate' => 'required|numeric|min:0|max:100',
            'duration_months' => 'required|integer|min:1|max:60',
            'description' => 'nullable|string|max:500',
            'application_date' => 'required|date',
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();

            // Create loan application
            $loan = Loan::create([
                'branch_id' => $request->branch_id,
                'suppliers_id' => $request->supplier_id,
                'amount' => $request->amount,
                'interest_rate' => $request->interest_rate,
                'duration_months' => $request->duration_months,
                'description' => $request->description,
                'created_by' => $user->id,
                'status' => 'pending',
                'application_date' => $request->application_date,
            ]);

            DB::commit();

            // ✅ Send SMS notifications AFTER commit (never inside the transaction)
            $supplier = Supplier::find($request->supplier_id);
            $branch = Branch::find($request->branch_id);
            $this->sendLoanApplicationNotifications($loan, $supplier, $branch);

            return redirect()->route('loans.applications.index')
                ->with('success', sprintf(
                    'Loan application #%d submitted successfully! Amount: TZS %s',
                    $loan->id,
                    number_format($loan->amount)
                ));

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to submit loan application: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified loan application.
     */
    public function show($id)
    {
        $loan = Loan::with(['branch', 'supplier', 'creator', 'approver'])
            ->findOrFail($id);
        
        $user = Auth::user();
        if ($user->role !== 'director' && $loan->branch_id != $user->assigned_branch) {
            abort(403, 'You do not have access to this loan.');
        }
        
        // Calculate total payable
        $totalPayable = $loan->amount + ($loan->amount * $loan->interest_rate / 100);
        $monthlyPayment = $loan->duration_months > 0 ? $totalPayable / $loan->duration_months : 0;
        
        return view('loan.applications.show', compact('loan', 'totalPayable', 'monthlyPayment'));
    }

    /**
     * Show the form for editing the specified loan application.
     */
    public function edit($id)
    {
        $loan = Loan::findOrFail($id);
        
        // Only allow editing if pending
        if ($loan->status !== 'pending') {
            return redirect()->route('loans.applications.index')
                ->with('error', 'This loan cannot be edited as it has been ' . $loan->status);
        }
        
        $user = Auth::user();
        if ($user->role !== 'director' && $loan->branch_id != $user->assigned_branch) {
            abort(403, 'You do not have access to this loan.');
        }
        
        $branches = Branch::all();
        $suppliers = Supplier::all();
        
        return view('loan.applications.edit', compact('loan', 'branches', 'suppliers'));
    }

    /**
     * Update the specified loan application.
     */
    public function update(Request $request, $id)
    {
        $loan = Loan::findOrFail($id);
        
        if ($loan->status !== 'pending') {
            return redirect()->route('loans.applications.index')
                ->with('error', 'This loan cannot be edited.');
        }

        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'amount' => 'required|numeric|min:1000',
            'interest_rate' => 'required|numeric|min:0|max:100',
            'duration_months' => 'required|integer|min:1|max:60',
            'description' => 'nullable|string|max:500',
            'application_date' => 'required|date',
        ]);

        try {
            DB::beginTransaction();

            $loan->update([
                'branch_id' => $request->branch_id,
                'suppliers_id' => $request->supplier_id,
                'amount' => $request->amount,
                'interest_rate' => $request->interest_rate,
                'duration_months' => $request->duration_months,
                'description' => $request->description,
                'application_date' => $request->application_date,
            ]);

            DB::commit();

            return redirect()->route('loans.applications.index')
                ->with('success', 'Loan application updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to update loan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified loan application.
     */
    public function destroy($id)
    {
        $loan = Loan::findOrFail($id);
        
        if ($loan->status !== 'pending') {
            return redirect()->route('loans.applications.index')
                ->with('error', 'This loan cannot be deleted.');
        }

        try {
            DB::beginTransaction();
            $loan->delete();
            DB::commit();

            return redirect()->route('loans.applications.index')
                ->with('success', 'Loan application deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to delete loan: ' . $e->getMessage());
        }
    }

    /**
     * Export loan applications to Excel/CSV.
     */
    public function export(Request $request)
    {
        // This will be implemented with Laravel Excel
        return redirect()->back()->with('info', 'Export functionality coming soon!');
    }

    /**
     * Get loan statistics for dashboard widgets.
     */
    public function getStats(Request $request)
    {
        $user = Auth::user();
        
        $query = Loan::query();
        
        if ($user->role !== 'director') {
            $query->where('branch_id', $user->assigned_branch);
        }
        
        // Apply filters if provided
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        $stats = [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'total_amount' => (clone $query)->sum('amount'),
            'avg_amount' => (clone $query)->avg('amount'),
            'avg_interest' => (clone $query)->avg('interest_rate'),
        ];
        
        // Get monthly loan data for charts
        $monthlyData = (clone $query)
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();
        
        $stats['monthly_data'] = $monthlyData;
        
        // Get top suppliers by loan amount
        $topSuppliers = (clone $query)
            ->select('suppliers_id', DB::raw('SUM(amount) as total_amount'))
            ->with('supplier')
            ->groupBy('suppliers_id')
            ->orderBy('total_amount', 'desc')
            ->limit(5)
            ->get();
        
        $stats['top_suppliers'] = $topSuppliers;
        
        return response()->json($stats);
    }

    /**
     * Get active loan count for dashboard badge.
     */
    public function getActiveCount(Request $request)
    {
        $user = Auth::user();
        
        $query = Loan::where('status', 'approved');
        
        if ($user->role !== 'director') {
            $query->where('branch_id', $user->assigned_branch);
        }
        
        return response()->json([
            'active_loans' => $query->count()
        ]);
    }

    /**
     * Send reminder for loan payment.
     */
    public function sendReminder($id)
    {
        $loan = Loan::findOrFail($id);
        
        if (!in_array($loan->status, ['approved', 'pending'])) {
            return redirect()->back()
                ->with('error', 'Reminder can only be sent for pending or approved loans.');
        }
        
        // Implement SMS/Email reminder logic here
        // This will be connected to your communication module
        
        return redirect()->back()
            ->with('success', 'Reminder sent successfully!');
    }

    /**
     * Get loan details for quick view (AJAX).
     */
    public function getDetails($id)
    {
        $loan = Loan::with(['branch', 'supplier', 'creator', 'approver'])
            ->findOrFail($id);
        
        $user = Auth::user();
        if ($user->role !== 'director' && $loan->branch_id != $user->assigned_branch) {
            abort(403);
        }
        
        $totalPayable = $loan->amount + ($loan->amount * $loan->interest_rate / 100);
        $monthlyPayment = $loan->duration_months > 0 ? $totalPayable / $loan->duration_months : 0;
        
        return response()->json([
            'loan' => $loan,
            'total_payable' => $totalPayable,
            'monthly_payment' => $monthlyPayment
        ]);
    }

    /**
     * =============================================================
     * SMS NOTIFICATIONS FOR LOAN APPLICATIONS
     * =============================================================
     * Fired after a loan application is created.
     * Recipients:
     *   - Supplier       → acknowledgment (application received)
     *   - Branch Manager → action-required alert
     *   - Directors      → full report summary
     */

    /**
     * Orchestrator: dispatches all SMS notifications for a new loan application.
     */
    private function sendLoanApplicationNotifications($loan, $supplier, $branch)
    {
        try {
            \Log::info('Starting SMS notifications for loan application #' . $loan->id);

            // 1. Fetch all directors
            $directors = User::where('role', 'director')->get();

            // 2. Fetch the branch manager for this loan's branch
            $branchManager = User::where('role', 'branch_manager')
                ->where('assigned_branch', $loan->branch_id)
                ->first();

            // 3. Get the SMS controller instance via the service container
            $smsController = app(SMSController::class);

            // --- SMS to SUPPLIER (acknowledgment) ---
            if ($supplier && $supplier->contact) {
                $phone = $smsController->formatPhoneNumber($supplier->contact);
                if ($phone) {
                    $message = $this->getSupplierApplicationMessage($supplier, $loan);
                    $smsController->sendSms($phone, $message, 'NGEMBA MINE');
                    \Log::info('Loan application SMS sent to supplier: ' . $phone);
                }
            }

            // --- SMS to BRANCH MANAGER (action required) ---
            if ($branchManager && $branchManager->phone_number) {
                $phone = $smsController->formatPhoneNumber($branchManager->phone_number);
                if ($phone) {
                    $message = $this->getManagerApplicationMessage($branchManager, $supplier, $loan, $branch);
                    $smsController->sendSms($phone, $message, 'NGEMBA MINE');
                    \Log::info('Loan application SMS sent to manager: ' . $phone);
                }
            }

            // --- SMS to ALL DIRECTORS (report) ---
            foreach ($directors as $director) {
                if ($director->phone_number) {
                    $phone = $smsController->formatPhoneNumber($director->phone_number);
                    if ($phone) {
                        $message = $this->getDirectorApplicationMessage($director, $supplier, $loan, $branch);
                        $smsController->sendSms($phone, $message, 'NGEMBA MINE');
                        \Log::info('Loan application SMS sent to director: ' . $phone);
                    }
                }
            }

            \Log::info('All SMS notifications sent for loan application #' . $loan->id);

        } catch (\Exception $e) {
            \Log::error('Failed to send loan application SMS notifications: ' . $e->getMessage());
        }
    }

    /**
     * Build the supplier acknowledgment message.
     */
    private function getSupplierApplicationMessage($supplier, $loan)
    {
        $template = DB::table('sms_templates')
            ->where('name', 'loan_application_supplier')
            ->first();

        if ($template) {
            return str_replace(
                ['{supplier_name}', '{loan_id}', '{amount}', '{duration}'],
                [
                    $supplier->name,
                    $loan->id,
                    number_format($loan->amount, 0),
                    $loan->duration_months,
                ],
                $template->message
            );
        }

        // Fallback if template is missing
        return "Dear " . $supplier->name . ", we have received your loan application #" . $loan->id . " for TZS " . number_format($loan->amount, 0) . " over " . $loan->duration_months . " months. It is now under review. Thank you for trusting us. - Ngemba Mining";
    }

    /**
     * Build the branch manager alert message.
     */
    private function getManagerApplicationMessage($manager, $supplier, $loan, $branch)
    {
        $template = DB::table('sms_templates')
            ->where('name', 'loan_application_manager')
            ->first();

        if ($template) {
            return str_replace(
                ['{supplier_name}', '{amount}', '{duration}', '{interest}', '{branch_name}', '{loan_id}'],
                [
                    $supplier->name ?? 'Unknown',
                    number_format($loan->amount, 0),
                    $loan->duration_months,
                    number_format($loan->interest_rate, 2),
                    $branch->name ?? 'N/A',
                    $loan->id,
                ],
                $template->message
            );
        }

        return "New Loan Application: " . ($supplier->name ?? 'Unknown') . " applied for TZS " . number_format($loan->amount, 0) . " over " . $loan->duration_months . " months (Interest " . number_format($loan->interest_rate, 2) . "%). Branch: " . ($branch->name ?? 'N/A') . ". Application #" . $loan->id . ". Please review.";
    }

    /**
     * Build the director report message.
     */
    private function getDirectorApplicationMessage($director, $supplier, $loan, $branch)
    {
        $template = DB::table('sms_templates')
            ->where('name', 'loan_application_director')
            ->first();

        if ($template) {
            return str_replace(
                ['{branch_name}', '{supplier_name}', '{amount}', '{duration}', '{interest}', '{loan_id}', '{date}'],
                [
                    $branch->name ?? 'N/A',
                    $supplier->name ?? 'Unknown',
                    number_format($loan->amount, 0),
                    $loan->duration_months,
                    number_format($loan->interest_rate, 2),
                    $loan->id,
                    $loan->created_at ? $loan->created_at->format('d/m/Y H:i') : date('d/m/Y H:i'),
                ],
                $template->message
            );
        }

        return "Loan Application Report: " . ($branch->name ?? 'N/A') . " - " . ($supplier->name ?? 'Unknown') . " applied for TZS " . number_format($loan->amount, 0) . " over " . $loan->duration_months . " months (Interest " . number_format($loan->interest_rate, 2) . "%). Application #" . $loan->id . ". Date: " . ($loan->created_at ? $loan->created_at->format('d/m/Y H:i') : date('d/m/Y H:i')) . ".";
    }
}