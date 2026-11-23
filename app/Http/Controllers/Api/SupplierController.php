<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Supplier;

class SupplierController extends Controller
{
    /**
     * GET /api/suppliers
     * List suppliers (used by app for the "Suppliers" tab).
     */
    public function list(Request $request)
    {
        $user = $request->user();
        $isGlobal = in_array($user->role, ['director', 'admin', 'accountant']);

        $query = Supplier::with('branch');

        if (!$isGlobal) {
            $query->where('branch_id', $user->assigned_branch);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('contact', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        $suppliers = $query->orderBy('name', 'asc')->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $suppliers->map(fn($s) => [
                'id' => (string) $s->id,
                'name' => $s->name,
                'contact' => $s->contact,
                'email' => $s->email,
                'address' => $s->address,
                'branch_name' => $s->branch->name ?? 'Unassigned',
                'created_at' => $s->created_at?->format('d M Y'),
            ]),
            'meta' => [
                'current_page' => $suppliers->currentPage(),
                'last_page' => $suppliers->lastPage(),
                'total' => $suppliers->total(),
            ],
        ]);
    }

    /**
     * GET /api/suppliers/{id}
     * Show a single supplier's details.
     */
    public function show(Request $request, $id)
    {
        $supplier = Supplier::with('branch')->findOrFail($id);

        $totalPurchases = $supplier->transactions()
            ->where('transaction_type', 'gold_purchase')
            ->sum('amount');

        $totalLoans = $supplier->loans()
            ->where('status', 'approved')
            ->sum('amount');

        return response()->json([
            'status' => true,
            'data' => [
                'id' => (string) $supplier->id,
                'name' => $supplier->name,
                'contact' => $supplier->contact,
                'email' => $supplier->email,
                'address' => $supplier->address,
                'branch_name' => $supplier->branch->name ?? 'Unassigned',
                'total_purchases' => (float) $totalPurchases,
                'total_loans' => (float) $totalLoans,
                'created_at' => $supplier->created_at?->format('d M Y'),
            ],
        ]);
    }

    /**
     * POST /api/suppliers
     * Create a new supplier.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'contact' => 'required|string|max:20',
            'address' => 'nullable|string',
            'branch_id' => 'required|exists:branches,id',
            'email' => 'nullable|email|max:255',
        ]);

        $supplier = Supplier::create([
            'name' => $request->name,
            'contact' => $request->contact,
            'address' => $request->address,
            'branch_id' => $request->branch_id,
            'email' => $request->email,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Supplier created successfully.',
            'data' => ['id' => $supplier->id],
        ], 201);
    }

    /**
     * PUT /api/suppliers/{id}
     * Update an existing supplier.
     */
    public function update(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'contact' => 'required|string|max:20',
            'address' => 'nullable|string',
            'branch_id' => 'required|exists:branches,id',
            'email' => 'nullable|email|max:255',
        ]);

        $supplier->update([
            'name' => $request->name,
            'contact' => $request->contact,
            'address' => $request->address,
            'branch_id' => $request->branch_id,
            'email' => $request->email,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Supplier updated successfully.',
        ]);
    }

    /**
     * DELETE /api/suppliers/{id}
     * Delete a supplier (fails if they have transactions/loans).
     */
    public function delete($id)
    {
        $supplier = Supplier::findOrFail($id);

        if ($supplier->transactions()->count() > 0) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot delete: supplier has transactions.',
            ], 400);
        }

        if ($supplier->loans()->count() > 0) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot delete: supplier has loans.',
            ], 400);
        }

        $supplier->delete();

        return response()->json([
            'status' => true,
            'message' => 'Supplier deleted successfully.',
        ]);
    }

    /**
     * GET /api/lookup/suppliers
     * Quick lookup for form dropdowns.
     */
    public function lookup(Request $request)
    {
        $user = $request->user();
        $query = Supplier::select('id', 'name', 'contact');

        if (!in_array($user->role, ['director', 'admin', 'accountant'])) {
            $query->where('branch_id', $user->assigned_branch);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        return response()->json([
            'status' => true,
            'data' => $query->limit(20)->get(),
        ]);
    }
}