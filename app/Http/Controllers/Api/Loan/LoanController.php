<?php

namespace App\Http\Controllers\Api\Loan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Loan;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        return $this->getLoans($request, null);
    }

    public function active(Request $request)
    {
        return $this->getLoans($request, 'approved');
    }

    public function overdue(Request $request)
    {
        // Overdue logic: approved loans where expected end date has passed
        $query = $this->baseQuery($request)->where('status', 'approved')
            ->where('application_date', '<', now()->subMonths(1)); // Simple mock logic, adjust as needed
        return $this->formatPagination($query->paginate(20));
    }

    public function completed(Request $request)
    {
        return $this->getLoans($request, 'completed');
    }

    public function statusCounts(Request $request)
    {
        $user = $request->user();
        $isGlobal = in_array($user->role, ['director', 'admin', 'accountant']);
        
        $query = Loan::query();
        if (!$isGlobal) $query->where('branch_id', $user->assigned_branch);

        return response()->json([
            'status' => true,
            'data' => [
                'pending' => (clone $query)->where('status', 'pending')->count(),
                'approved' => (clone $query)->where('status', 'approved')->count(),
                'completed' => (clone $query)->where('status', 'completed')->count(),
                'rejected' => (clone $query)->where('status', 'rejected')->count(),
            ]
        ]);
    }

    public function show(Request $request, $id)
    {
        $loan = Loan::with(['branch', 'supplier', 'creator', 'payments'])->findOrFail($id);
        
        $totalPaid = $loan->payments->where('status', 'verified')->sum('amount');
        $totalPayable = $loan->amount + ($loan->amount * $loan->interest_rate / 100);

        return response()->json([
            'status' => true,
            'data' => $this->formatLoan($loan, $totalPaid, $totalPayable)
        ]);
    }

    public function search(Request $request)
    {
        $term = $request->input('q', '');
        return $this->getLoans($request, null, $term);
    }

    // Helpers
    private function baseQuery(Request $request)
    {
        $user = $request->user();
        $isGlobal = in_array($user->role, ['director', 'admin', 'accountant']);
        $query = Loan::with(['branch', 'supplier', 'payments'])->latest();
        if (!$isGlobal) $query->where('branch_id', $user->assigned_branch);
        return $query;
    }

    private function getLoans(Request $request, $status = null, $search = null)
    {
        $query = $this->baseQuery($request);
        if ($status) $query->where('status', $status);
        if ($search) $query->whereHas('supplier', fn($q) => $q->where('name', 'like', "%{$search}%"));
        
        return $this->formatPagination($query->paginate(20));
    }

    private function formatPagination($paginator)
    {
        return response()->json([
            'status' => true,
            'data' => $paginator->map(fn($loan) => $this->formatLoan($loan)),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ]
        ]);
    }

    private function formatLoan($loan, $totalPaid = null, $totalPayable = null)
    {
        $totalPaid = $totalPaid ?? $loan->payments->where('status', 'verified')->sum('amount');
        $totalPayable = $totalPayable ?? ($loan->amount + ($loan->amount * $loan->interest_rate / 100));

        return [
            'id' => (string) $loan->id,
            'supplier_name' => $loan->supplier->name ?? 'Unknown',
            'branch_name' => $loan->branch->name ?? 'Unknown',
            'amount' => (float) $loan->amount,
            'interest_rate' => (float) $loan->interest_rate,
            'duration_months' => (int) $loan->duration_months,
            'total_payable' => (float) $totalPayable,
            'total_paid' => (float) $totalPaid,
            'balance' => (float) max($totalPayable - $totalPaid, 0),
            'status' => strtoupper($loan->status),
            'date' => $loan->created_at->format('d M Y'),
        ];
    }
}