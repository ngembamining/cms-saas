<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Branch;
use App\Models\BranchBalance;
use App\Models\User;

class BranchController extends Controller
{
    /**
     * Display a listing of branches.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = Branch::with(['balances', 'users']);
        
        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('location', 'like', '%' . $search . '%');
            });
        }
        
        $branches = $query->orderBy('name', 'asc')->paginate(20)->appends($request->all());
        
        return view('management.branches.index', compact('branches'));
    }

    /**
     * Show the form for creating a new branch.
     */
    public function create()
    {
        return view('management.branches.create');
    }

    /**
     * Store a newly created branch.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:branches',
            'location' => 'required|string|max:255',
            'gold_available' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Create branch
            $branch = Branch::create([
                'name' => $request->name,
                'location' => $request->location,
                'gold_available' => $request->gold_available ?? 0,
                'created_at' => now(),
            ]);

            // Create default balances for the branch
            BranchBalance::create([
                'branch_id' => $branch->id,
                'balance_type' => 'cash',
                'amount' => 0,
                'currency' => 'TZS',
                'minimum_balance' => 0,
                'last_updated' => now(),
            ]);

            BranchBalance::create([
                'branch_id' => $branch->id,
                'balance_type' => 'gold',
                'amount' => 0,
                'currency' => 'g',
                'minimum_balance' => 0,
                'last_updated' => now(),
            ]);

            DB::commit();

            return redirect()->route('management.branches.index')
                ->with('success', '✅ Branch "' . $branch->name . '" created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to create branch: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified branch.
     */
    public function show($id)
    {
        $branch = Branch::with(['balances', 'users', 'transactions', 'loans', 'expenses'])
            ->findOrFail($id);
        
        // Get statistics
        $totalStaff = $branch->users()->count();
        $totalTransactions = $branch->transactions()->count();
        $totalLoans = $branch->loans()->count();
        $totalExpenses = $branch->expenses()->sum('amount');
        
        $cashBalance = $branch->balances()->where('balance_type', 'cash')->first();
        $goldBalance = $branch->balances()->where('balance_type', 'gold')->first();
        
        $recentTransactions = $branch->transactions()
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return view('management.branches.show', compact(
            'branch',
            'totalStaff',
            'totalTransactions',
            'totalLoans',
            'totalExpenses',
            'cashBalance',
            'goldBalance',
            'recentTransactions'
        ));
    }

    /**
     * Show the form for editing the specified branch.
     */
    public function edit($id)
    {
        $branch = Branch::findOrFail($id);
        return view('management.branches.edit', compact('branch'));
    }

    /**
     * Update the specified branch.
     */
    public function update(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:100|unique:branches,name,' . $id,
            'location' => 'required|string|max:255',
            'gold_available' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $branch->update([
                'name' => $request->name,
                'location' => $request->location,
                'gold_available' => $request->gold_available ?? 0,
                'updated_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('management.branches.index')
                ->with('success', '✅ Branch "' . $branch->name . '" updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to update branch: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified branch.
     */
    public function destroy($id)
    {
        $branch = Branch::findOrFail($id);
        
        // Check if branch has users
        if ($branch->users()->count() > 0) {
            return redirect()->route('management.branches.index')
                ->with('warning', '⚠️ This branch has staff assigned and cannot be deleted.');
        }
        
        // Check if branch has transactions
        if ($branch->transactions()->count() > 0) {
            return redirect()->route('management.branches.index')
                ->with('warning', '⚠️ This branch has transactions and cannot be deleted.');
        }

        try {
            DB::beginTransaction();
            
            // Delete branch balances first
            $branch->balances()->delete();
            
            // Delete branch
            $branch->delete();
            
            DB::commit();

            return redirect()->route('management.branches.index')
                ->with('success', '✅ Branch "' . $branch->name . '" deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to delete branch: ' . $e->getMessage());
        }
    }

    /**
     * Display branch balances.
     */
    public function balances($id)
    {
        $branch = Branch::with(['balances'])->findOrFail($id);
        
        $cashBalance = $branch->balances()->where('balance_type', 'cash')->first();
        $goldBalance = $branch->balances()->where('balance_type', 'gold')->first();
        
        return view('management.branches.balances', compact(
            'branch',
            'cashBalance',
            'goldBalance'
        ));
    }

    /**
     * Get branch list for API.
     */
    public function getList(Request $request)
    {
        $user = Auth::user();
        
        $branches = Branch::when($user->role !== 'director', function ($q) use ($user) {
            return $q->where('id', $user->assigned_branch);
        })->select('id', 'name', 'location');
        
        if ($request->filled('search')) {
            $branches->where('name', 'like', '%' . $request->search . '%');
        }
        
        return response()->json($branches->get());
    }

    /**
     * Get branch balance for API.
     */
    public function getBalance($id)
    {
        $branch = Branch::with(['balances'])->findOrFail($id);
        
        $cash = $branch->balances()->where('balance_type', 'cash')->first();
        $gold = $branch->balances()->where('balance_type', 'gold')->first();
        
        return response()->json([
            'cash' => $cash->amount ?? 0,
            'gold' => $gold->amount ?? 0,
        ]);
    }

    /**
     * Lookup branches for API.
     */
    public function lookup(Request $request)
    {
        $user = Auth::user();
        
        $branches = Branch::select('id', 'name')
            ->when($user->role !== 'director', function ($q) use ($user) {
                return $q->where('id', $user->assigned_branch);
            });
        
        if ($request->filled('search')) {
            $branches->where('name', 'like', '%' . $request->search . '%');
        }
        
        return response()->json($branches->limit(20)->get());
    }
}