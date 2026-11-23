<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;

class CustomerController extends Controller
{
    /**
     * GET /api/customers
     */
    public function list(Request $request)
    {
        $user = $request->user();
        $isGlobal = in_array($user->role, ['director', 'admin', 'accountant']);

        $query = Customer::with('branch');

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

        $customers = $query->orderBy('name', 'asc')->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $customers->map(fn($c) => [
                'id' => (string) $c->id,
                'name' => $c->name,
                'contact' => $c->contact,
                'email' => $c->email,
                'address' => $c->address,
                'branch_name' => $c->branch->name ?? 'Unassigned',
                'created_at' => $c->created_at?->format('d M Y'),
            ]),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    /**
     * GET /api/customers/{id}
     */
    public function show(Request $request, $id)
    {
        $customer = Customer::with('branch')->findOrFail($id);

        $totalSales = $customer->transactions()
            ->where('transaction_type', 'gold_sale')
            ->sum('amount');

        return response()->json([
            'status' => true,
            'data' => [
                'id' => (string) $customer->id,
                'name' => $customer->name,
                'contact' => $customer->contact,
                'email' => $customer->email,
                'address' => $customer->address,
                'branch_name' => $customer->branch->name ?? 'Unassigned',
                'total_sales' => (float) $totalSales,
                'created_at' => $customer->created_at?->format('d M Y'),
            ],
        ]);
    }

    /**
     * POST /api/customers
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

        $customer = Customer::create([
            'name' => $request->name,
            'contact' => $request->contact,
            'address' => $request->address,
            'branch_id' => $request->branch_id,
            'email' => $request->email,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Customer created successfully.',
            'data' => ['id' => $customer->id],
        ], 201);
    }

    /**
     * PUT /api/customers/{id}
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'contact' => 'required|string|max:20',
            'address' => 'nullable|string',
            'branch_id' => 'required|exists:branches,id',
            'email' => 'nullable|email|max:255',
        ]);

        $customer->update([
            'name' => $request->name,
            'contact' => $request->contact,
            'address' => $request->address,
            'branch_id' => $request->branch_id,
            'email' => $request->email,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Customer updated successfully.',
        ]);
    }

    /**
     * DELETE /api/customers/{id}
     */
    public function delete($id)
    {
        $customer = Customer::findOrFail($id);

        if ($customer->transactions()->count() > 0) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot delete: customer has transactions.',
            ], 400);
        }

        $customer->delete();

        return response()->json([
            'status' => true,
            'message' => 'Customer deleted successfully.',
        ]);
    }

    /**
     * GET /api/lookup/customers
     * Quick lookup for form dropdowns.
     */
    public function lookup(Request $request)
    {
        $user = $request->user();
        $query = Customer::select('id', 'name', 'contact');

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