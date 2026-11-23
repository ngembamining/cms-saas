<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\Branch;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = Customer::with(['branch']);
        
        // Branch filter for non-director users
        if ($user->role !== 'director' && $user->role !== 'accountant') {
            $query->where('branch_id', $user->assigned_branch);
        }
        
        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('contact', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }
        
        // Branch filter
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        $customers = $query->orderBy('name', 'asc')->paginate(20)->appends($request->all());
        
        // Get branches for filter
        $branches = Branch::when(!in_array($user->role, ['director', 'accountant']), function ($q) use ($user) {
            return $q->where('id', $user->assigned_branch);
        })->get();
        
        return view('management.customers.index', compact('customers', 'branches'));
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create()
    {
        $user = Auth::user();
        
        $branches = Branch::when(!in_array($user->role, ['director', 'accountant']), function ($q) use ($user) {
            return $q->where('id', $user->assigned_branch);
        })->get();
        
        return view('management.customers.create', compact('branches'));
    }

    /**
     * Store a newly created customer.
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

        try {
            DB::beginTransaction();

            $customer = Customer::create([
                'name' => $request->name,
                'contact' => $request->contact,
                'address' => $request->address,
                'branch_id' => $request->branch_id,
                'email' => $request->email,
                'created_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('customers.index')
                ->with('success', '✅ Customer "' . $customer->name . '" created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to create customer: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified customer.
     */
    public function show($id)
    {
        $customer = Customer::with(['branch', 'transactions'])
            ->findOrFail($id);
        
        $user = Auth::user();
        if (!in_array($user->role, ['director', 'accountant']) && $customer->branch_id != $user->assigned_branch) {
            abort(403, 'You do not have access to this customer.');
        }
        
        // Get transaction summary
        $totalSales = $customer->transactions()
            ->where('transaction_type', 'gold_sale')
            ->sum('amount');
        
        $recentTransactions = $customer->transactions()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return view('management.customers.show', compact(
            'customer',
            'totalSales',
            'recentTransactions'
        ));
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        
        $user = Auth::user();
        if (!in_array($user->role, ['director', 'accountant']) && $customer->branch_id != $user->assigned_branch) {
            abort(403, 'You do not have access to this customer.');
        }
        
        $branches = Branch::all();
        
        return view('management.customers.edit', compact('customer', 'branches'));
    }

    /**
     * Update the specified customer.
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

        try {
            DB::beginTransaction();

            $customer->update([
                'name' => $request->name,
                'contact' => $request->contact,
                'address' => $request->address,
                'branch_id' => $request->branch_id,
                'email' => $request->email,
            ]);

            DB::commit();

            return redirect()->route('customers.index')
                ->with('success', '✅ Customer "' . $customer->name . '" updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to update customer: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified customer.
     */
    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        
        // Check if customer has transactions
        if ($customer->transactions()->count() > 0) {
            return redirect()->route('customers.index')
                ->with('warning', '⚠️ This customer has transactions and cannot be deleted.');
        }

        try {
            DB::beginTransaction();
            $customer->delete();
            DB::commit();

            return redirect()->route('customers.index')
                ->with('success', '✅ Customer "' . $customer->name . '" deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to delete customer: ' . $e->getMessage());
        }
    }

    /**
     * Get customer transactions.
     */
    public function transactions($id)
    {
        $customer = Customer::findOrFail($id);
        
        $user = Auth::user();
        if (!in_array($user->role, ['director', 'accountant']) && $customer->branch_id != $user->assigned_branch) {
            abort(403, 'You do not have access to this customer.');
        }
        
        $transactions = $customer->transactions()
            ->with(['branch', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('management.customers.transactions', compact('customer', 'transactions'));
    }

    /**
     * Export customers to CSV/Excel.
     */
    public function export()
    {
        return redirect()->back()
            ->with('info', 'ℹ️ Export functionality coming soon!');
    }

    /**
     * Get customer list for API.
     */
    public function lookup(Request $request)
    {
        $user = Auth::user();
        
        $query = Customer::select('id', 'name', 'contact');
        
        if (!in_array($user->role, ['director', 'accountant'])) {
            $query->where('branch_id', $user->assigned_branch);
        }
        
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        return response()->json($query->limit(20)->get());
    }
}