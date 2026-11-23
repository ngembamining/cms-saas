<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Expense;
use App\Models\Branch;
use App\Models\BranchBalance;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Http\Controllers\Communication\SMSController;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    /**
     * Threshold above which directors are notified by SMS.
     */
    protected $directorNotificationThreshold = 1000000; // TZS 1,000,000

    /**
     * Display a listing of expenses.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Expense::query();

        if ($user->role !== 'director' && $user->role !== 'accountant') {
            $query->where('branch_id', $user->assigned_branch);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
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
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $query->orderBy('created_at', 'desc');

        $expenses = $query->paginate(20);

        $totalExpenses  = (clone $query)->sum('amount');
        $averageExpense = $expenses->count() > 0 ? $totalExpenses / $expenses->count() : 0;
        $maxExpense     = (clone $query)->max('amount') ?? 0;

        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();

        if ($user->role === 'director' || $user->role === 'accountant') {
            $branches = Branch::all();
        } else {
            $branches = Branch::where('id', $user->assigned_branch)->get();
        }

        return view('finance.expenses.index', compact(
            'expenses',
            'totalExpenses',
            'averageExpense',
            'maxExpense',
            'categories',
            'branches'
        ));
    }

    /**
     * Show the form for creating a new expense.
     */
    public function create()
    {
        $user = Auth::user();

        if ($user->role === 'director' || $user->role === 'accountant') {
            $branches = Branch::all();
        } else {
            $branches = Branch::where('id', $user->assigned_branch)->get();
        }

        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();

        return view('finance.expenses.create', compact('branches', 'categories'));
    }

    /**
     * Store a newly created expense.
     *
     * Records the expense AND deducts the amount from the branch cash balance
     * inside a single DB transaction.
     */
    public function store(Request $request)
    {
        $request->validate([
            'branch_id'   => 'required|exists:branches,id',
            'category'    => 'required|string|max:100',
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();

        // Role-based branch scoping
        if ($user->role !== 'director' && $user->role !== 'accountant') {
            if ((int) $request->branch_id !== (int) $user->assigned_branch) {
                return redirect()->back()
                    ->with('error', 'You are not allowed to record expenses for this branch.')
                    ->withInput();
            }
        }

        try {
            DB::beginTransaction();

            // 1. Lock and fetch the branch's cash balance row
            $cashBalance = BranchBalance::firstOrCreate(
                [
                    'branch_id'    => $request->branch_id,
                    'balance_type' => 'cash',
                ],
                [
                    'currency'        => 'TZS',
                    'amount'          => 0,
                    'minimum_balance' => 0,
                ]
            );

            // 2. Check sufficient funds
            if ($cashBalance->amount < $request->amount) {
                throw new \Exception(
                    'Insufficient branch cash balance. Available: TZS '
                    . number_format($cashBalance->amount, 0)
                    . ', Requested: TZS '
                    . number_format($request->amount, 0)
                );
            }

            // 3. Create the expense
            $expense = Expense::create([
                'branch_id'   => $request->branch_id,
                'category'    => $request->category,
                'amount'      => $request->amount,
                'description' => $request->description,
                'recorded_by' => $user->id,
            ]);

            // 4. Deduct from branch cash balance
            $cashBalance->amount -= $expense->amount;
            $cashBalance->save();

            DB::commit();

            // 5. Send SMS notifications after commit
            $branch = Branch::find($request->branch_id);
            $this->sendExpenseNotifications($expense, $branch, $user);

            return redirect()->route('finance.expenses.index')
                ->with('success', 'Expense recorded successfully! Amount: TZS '
                    . number_format($expense->amount)
                    . ' deducted from branch balance.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to record expense: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified expense.
     */
    public function show($id)
    {
        $expense = Expense::findOrFail($id);

        $user = Auth::user();

        if ($user->role !== 'director' && $user->role !== 'accountant' && $expense->branch_id != $user->assigned_branch) {
            abort(403, 'You do not have access to this expense.');
        }

        $recordedBy = User::find($expense->recorded_by);
        $approvedBy = User::find($expense->approved_by);

        return view('finance.expenses.show', compact('expense', 'recordedBy', 'approvedBy'));
    }

    /**
     * Show the form for editing the specified expense.
     */
    public function edit($id)
    {
        $expense = Expense::findOrFail($id);

        $user = Auth::user();

        if ($user->role !== 'director' && $user->role !== 'accountant' && $expense->branch_id != $user->assigned_branch) {
            abort(403, 'You do not have access to this expense.');
        }

        if (in_array('status', \Schema::getColumnListing('expenses')) && $expense->status !== 'pending') {
            return redirect()->route('finance.expenses.index')
                ->with('error', 'This expense cannot be edited as it has been ' . $expense->status);
        }

        if ($user->role === 'director' || $user->role === 'accountant') {
            $branches = Branch::all();
        } else {
            $branches = Branch::where('id', $user->assigned_branch)->get();
        }

        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();

        return view('finance.expenses.edit', compact('expense', 'branches', 'categories'));
    }

    /**
     * Update the specified expense.
     *
     * Adjusts the branch cash balance by the delta between old and new amounts,
     * and handles a branch change if the user reassigned the expense.
     */
    public function update(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);

        $user = Auth::user();

        // Role-based access
        if ($user->role !== 'director' && $user->role !== 'accountant' && $expense->branch_id != $user->assigned_branch) {
            abort(403, 'You do not have access to this expense.');
        }

        if (in_array('status', \Schema::getColumnListing('expenses')) && $expense->status !== 'pending') {
            return redirect()->route('finance.expenses.index')
                ->with('error', 'This expense cannot be edited.');
        }

        // Non-privileged users can't move the expense to another branch
        if ($user->role !== 'director' && $user->role !== 'accountant') {
            if ((int) $request->branch_id !== (int) $expense->branch_id) {
                return redirect()->back()
                    ->with('error', 'You cannot move an expense to another branch.')
                    ->withInput();
            }
        }

        $request->validate([
            'branch_id'   => 'required|exists:branches,id',
            'category'    => 'required|string|max:100',
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
        ]);

        $oldBranchId = $expense->branch_id;
        $oldAmount   = $expense->amount;
        $newBranchId = (int) $request->branch_id;
        $newAmount   = $request->amount;

        try {
            DB::beginTransaction();

            if ($oldBranchId === $newBranchId) {
                // Same branch — adjust by the delta
                $delta = $newAmount - $oldAmount;

                $cashBalance = BranchBalance::firstOrCreate(
                    ['branch_id' => $newBranchId, 'balance_type' => 'cash'],
                    ['currency' => 'TZS', 'amount' => 0, 'minimum_balance' => 0]
                );

                // If the expense is increasing, we need enough cash
                if ($delta > 0 && $cashBalance->amount < $delta) {
                    throw new \Exception(
                        'Insufficient branch cash balance for the increase. Available: TZS '
                        . number_format($cashBalance->amount, 0)
                    );
                }

                $cashBalance->amount -= $delta;
                $cashBalance->save();

            } else {
                // Branch changed — refund old branch, debit new branch
                $oldBalance = BranchBalance::firstOrCreate(
                    ['branch_id' => $oldBranchId, 'balance_type' => 'cash'],
                    ['currency' => 'TZS', 'amount' => 0, 'minimum_balance' => 0]
                );
                $oldBalance->amount += $oldAmount;
                $oldBalance->save();

                $newBalance = BranchBalance::firstOrCreate(
                    ['branch_id' => $newBranchId, 'balance_type' => 'cash'],
                    ['currency' => 'TZS', 'amount' => 0, 'minimum_balance' => 0]
                );

                if ($newBalance->amount < $newAmount) {
                    throw new \Exception(
                        'Insufficient cash balance on target branch. Available: TZS '
                        . number_format($newBalance->amount, 0)
                    );
                }

                $newBalance->amount -= $newAmount;
                $newBalance->save();
            }

            // Update the expense row
            $expense->update([
                'branch_id'   => $newBranchId,
                'category'    => $request->category,
                'amount'      => $newAmount,
                'description' => $request->description,
            ]);

            DB::commit();

            return redirect()->route('finance.expenses.index')
                ->with('success', 'Expense updated successfully and branch balance adjusted.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to update expense: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified expense.
     *
     * Refunds the amount back to the branch cash balance.
     */
    public function destroy($id)
    {
        $expense = Expense::findOrFail($id);

        $user = Auth::user();

        // Role-based access
        if ($user->role !== 'director' && $user->role !== 'accountant' && $expense->branch_id != $user->assigned_branch) {
            abort(403, 'You do not have access to this expense.');
        }

        try {
            DB::beginTransaction();

            // Refund the amount to the branch
            $cashBalance = BranchBalance::firstOrCreate(
                ['branch_id' => $expense->branch_id, 'balance_type' => 'cash'],
                ['currency' => 'TZS', 'amount' => 0, 'minimum_balance' => 0]
            );
            $cashBalance->amount += $expense->amount;
            $cashBalance->save();

            $expense->delete();

            DB::commit();

            return redirect()->route('finance.expenses.index')
                ->with('success', 'Expense deleted successfully and amount refunded to branch balance.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to delete expense: ' . $e->getMessage());
        }
    }

    /**
     * Get filtered expenses query (helper method for print/export)
     */
    private function getFilteredExpenses(Request $request)
    {
        $user = Auth::user();

        $query = Expense::query();

        if ($user->role !== 'director' && $user->role !== 'accountant') {
            $query->where('branch_id', $user->assigned_branch);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
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
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $query->orderBy('created_at', 'desc');

        return $query;
    }

    /**
     * Print expenses report (PDF/Printable version)
     */
    public function print(Request $request)
    {
        $expenses = $this->getFilteredExpenses($request)->get();

        $userIds = $expenses->pluck('recorded_by')->merge($expenses->pluck('approved_by'))->filter()->unique()->toArray();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        $totalExpenses  = $expenses->sum('amount');
        $averageExpense = $expenses->count() > 0 ? $totalExpenses / $expenses->count() : 0;
        $maxExpense     = $expenses->max('amount') ?? 0;
        $minExpense     = $expenses->min('amount') ?? 0;

        $byCategory = $expenses->groupBy('category')->map(function ($items) {
            return [
                'count'   => $items->count(),
                'total'   => $items->sum('amount'),
                'average' => $items->count() > 0 ? $items->sum('amount') / $items->count() : 0,
                'max'     => $items->max('amount') ?? 0,
                'min'     => $items->min('amount') ?? 0,
            ];
        });

        $byBranch = $expenses->groupBy('branch_id')->map(function ($items) {
            $branch = Branch::find($items->first()->branch_id);
            return [
                'branch'  => $branch ? $branch->name : 'N/A',
                'count'   => $items->count(),
                'total'   => $items->sum('amount'),
                'average' => $items->count() > 0 ? $items->sum('amount') / $items->count() : 0,
            ];
        });

        $filters = [
            'category'   => $request->category,
            'branch_id'  => $request->branch_id,
            'min_amount' => $request->min_amount,
            'max_amount' => $request->max_amount,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
        ];

        return view('finance.expenses.print', compact(
            'expenses',
            'totalExpenses',
            'averageExpense',
            'maxExpense',
            'minExpense',
            'byCategory',
            'byBranch',
            'filters',
            'users'
        ));
    }

    /**
     * Export expenses to Excel/CSV
     */
    public function export(Request $request)
    {
        $expenses = $this->getFilteredExpenses($request)->get();

        if ($expenses->isEmpty()) {
            return redirect()->back()->with('error', 'No expenses found to export.');
        }

        $userIds = $expenses->pluck('recorded_by')->merge($expenses->pluck('approved_by'))->filter()->unique()->toArray();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        $filename = 'expenses_report_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($expenses, $users) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'ID', 'Category', 'Branch', 'Amount (TZS)', 'Description', 'Date',
                'Recorded By', 'Status', 'Approved By', 'Approved At',
            ]);

            foreach ($expenses as $expense) {
                $branch     = Branch::find($expense->branch_id);
                $recordedBy = $users[$expense->recorded_by] ?? null;
                $approvedBy = $users[$expense->approved_by] ?? null;

                fputcsv($file, [
                    $expense->id,
                    $expense->category ?? 'Uncategorized',
                    $branch ? $branch->name : 'N/A',
                    number_format($expense->amount, 2),
                    $expense->description ?? '',
                    $expense->created_at ? $expense->created_at->format('Y-m-d H:i:s') : '',
                    $recordedBy ? ($recordedBy->full_name ?? $recordedBy->username ?? 'N/A') : 'N/A',
                    isset($expense->status) ? ucfirst($expense->status) : 'N/A',
                    $approvedBy ? ($approvedBy->full_name ?? $approvedBy->username ?? 'N/A') : 'N/A',
                    isset($expense->approved_at) && $expense->approved_at ? $expense->approved_at->format('Y-m-d H:i:s') : '',
                ]);
            }

            fputcsv($file, []);
            fputcsv($file, ['SUMMARY']);
            fputcsv($file, ['Total Expenses', number_format($expenses->sum('amount'), 2)]);
            fputcsv($file, ['Total Records', $expenses->count()]);
            fputcsv($file, ['Average Expense', number_format($expenses->avg('amount') ?? 0, 2)]);
            fputcsv($file, ['Highest Expense', number_format($expenses->max('amount') ?? 0, 2)]);
            fputcsv($file, ['Lowest Expense', number_format($expenses->min('amount') ?? 0, 2)]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get expenses for API/AJAX requests
     */
    public function getStats(Request $request)
    {
        $user = Auth::user();

        $query = Expense::query();

        if ($user->role !== 'director' && $user->role !== 'accountant') {
            $query->where('branch_id', $user->assigned_branch);
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

        $stats = [
            'total'   => (clone $query)->sum('amount'),
            'count'   => (clone $query)->count(),
            'average' => (clone $query)->avg('amount') ?? 0,
            'max'     => (clone $query)->max('amount') ?? 0,
            'min'     => (clone $query)->min('amount') ?? 0,
        ];

        if (in_array('status', \Schema::getColumnListing('expenses'))) {
            $stats['pending']  = (clone $query)->where('status', 'pending')->count();
            $stats['approved'] = (clone $query)->where('status', 'approved')->count();
            $stats['rejected'] = (clone $query)->where('status', 'rejected')->count();
        }

        $monthlyData = (clone $query)
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();

        $stats['monthly_data'] = $monthlyData;

        $categoryData = (clone $query)
            ->select(
                'category',
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('category')
            ->orderBy('total', 'desc')
            ->get();

        $stats['category_data'] = $categoryData;

        return response()->json($stats);
    }

    /**
     * Display expense categories management.
     */
    public function categories()
    {
        $categories = ExpenseCategory::orderBy('name')->get();
        return view('finance.expenses.categories', compact('categories'));
    }

    /**
     * Store a new expense category.
     */
    public function storeCategory(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:100|unique:expense_categories,name',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            ExpenseCategory::create([
                'name'        => $request->name,
                'description' => $request->description,
                'is_active'   => true,
            ]);

            return redirect()->route('finance.expenses.categories.index')
                ->with('success', 'Category created successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create category: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update an expense category.
     */
    public function updateCategory(Request $request, $id)
    {
        $category = ExpenseCategory::findOrFail($id);

        $request->validate([
            'name'        => 'required|string|max:100|unique:expense_categories,name,' . $id,
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $category->update([
                'name'        => $request->name,
                'description' => $request->description,
            ]);

            return redirect()->route('finance.expenses.categories.index')
                ->with('success', 'Category updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update category: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete an expense category.
     */
    public function destroyCategory($id)
    {
        $category = ExpenseCategory::findOrFail($id);

        try {
            $category->delete();
            return redirect()->route('finance.expenses.categories.index')
                ->with('success', 'Category deleted successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete category: ' . $e->getMessage());
        }
    }

    /**
     * Toggle category active status.
     */
    public function toggleCategory($id)
    {
        $category = ExpenseCategory::findOrFail($id);

        try {
            $category->update(['is_active' => !$category->is_active]);
            $status = $category->is_active ? 'activated' : 'deactivated';

            return redirect()->back()
                ->with('success', "Category {$status} successfully!");

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to toggle category: ' . $e->getMessage());
        }
    }

    /**
     * Lookup categories for AJAX.
     */
    public function lookupCategories(Request $request)
    {
        $search = $request->get('q', '');

        $categories = ExpenseCategory::where('is_active', true)
            ->where('name', 'like', "%{$search}%")
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name']);

        return response()->json($categories);
    }

    /**
     * =============================================================
     * SMS NOTIFICATIONS FOR EXPENSES
     * =============================================================
     */

    private function sendExpenseNotifications($expense, $branch, $recordedBy)
    {
        try {
            \Log::info('Starting SMS notifications for expense #' . $expense->id);

            $directors = User::where('role', 'director')->get();

            $branchManager = User::where('role', 'branch_manager')
                ->where('assigned_branch', $expense->branch_id)
                ->first();

            $smsController = app(SMSController::class);

            if ($branchManager && $branchManager->phone_number) {
                $phone = $smsController->formatPhoneNumber($branchManager->phone_number);
                if ($phone) {
                    $message = $this->getManagerExpenseMessage($branchManager, $expense, $branch, $recordedBy);
                    $smsController->sendSms($phone, $message, 'NGEMBA MINE');
                    \Log::info('Expense SMS sent to manager: ' . $phone);
                }
            }

            if ($expense->amount >= $this->directorNotificationThreshold) {
                foreach ($directors as $director) {
                    if ($director->phone_number) {
                        $phone = $smsController->formatPhoneNumber($director->phone_number);
                        if ($phone) {
                            $message = $this->getDirectorExpenseMessage($director, $expense, $branch, $recordedBy);
                            $smsController->sendSms($phone, $message, 'NGEMBA MINE');
                            \Log::info('Expense SMS sent to director: ' . $phone);
                        }
                    }
                }
            } else {
                \Log::info('Expense #' . $expense->id . ' below threshold (' . number_format($this->directorNotificationThreshold) . ' TZS) — directors NOT notified.');
            }

            \Log::info('All SMS notifications sent for expense #' . $expense->id);

        } catch (\Exception $e) {
            \Log::error('Failed to send expense SMS notifications: ' . $e->getMessage());
        }
    }

    private function getManagerExpenseMessage($manager, $expense, $branch, $recordedBy)
    {
        $template = DB::table('sms_templates')->where('name', 'expense_added')->first();

        if ($template) {
            return str_replace(
                ['{category}', '{description}', '{amount}', '{branch_name}', '{requested_by}'],
                [
                    $expense->category ?? 'Uncategorized',
                    $expense->description ?? 'No description',
                    number_format($expense->amount, 0),
                    $branch->name ?? 'N/A',
                    $recordedBy->full_name ?? $recordedBy->username ?? 'Unknown',
                ],
                $template->message
            );
        }

        return "New Expense Added: " . ($expense->category ?? 'Uncategorized') . " - " . ($expense->description ?? 'No description') . ". Amount: TZS " . number_format($expense->amount, 0) . ". Branch: " . ($branch->name ?? 'N/A') . ". Requested by: " . ($recordedBy->full_name ?? $recordedBy->username ?? 'Unknown') . ". Please review and approve.";
    }

    private function getDirectorExpenseMessage($director, $expense, $branch, $recordedBy)
    {
        $template = DB::table('sms_templates')->where('name', 'expense_director')->first();
        $status   = isset($expense->status) ? ucfirst($expense->status) : 'Recorded';

        if ($template) {
            return str_replace(
                ['{category}', '{description}', '{amount}', '{branch_name}', '{date}', '{status}'],
                [
                    $expense->category ?? 'Uncategorized',
                    $expense->description ?? 'No description',
                    number_format($expense->amount, 0),
                    $branch->name ?? 'N/A',
                    $expense->created_at ? $expense->created_at->format('d/m/Y H:i') : date('d/m/Y H:i'),
                    $status,
                ],
                $template->message
            );
        }

        return "Expense Report: " . ($expense->category ?? 'Uncategorized') . " - " . ($expense->description ?? 'No description') . ". Amount: TZS " . number_format($expense->amount, 0) . ". Branch: " . ($branch->name ?? 'N/A') . ". Date: " . ($expense->created_at ? $expense->created_at->format('d/m/Y H:i') : date('d/m/Y H:i')) . ". Status: " . $status . ".";
    }
}