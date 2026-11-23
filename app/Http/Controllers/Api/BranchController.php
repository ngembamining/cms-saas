    public function lookups(Request $request)
    <?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Branch;
use App\Models\BranchBalance;

class BranchController extends Controller
{
    public function list(Request $request)
    {
        $branches = Branch::with(['balances'])->orderBy('name')->paginate(20);
        
        return response()->json([
            'status' => true,
            'data' => $branches->map(fn($b) => [
                'id' => (string) $b->id,
                'name' => $b->name,
                'location' => $b->location,
                'gold_available' => (float) $b->gold_available,
                'cash_balance' => (float) ($b->balances->where('balance_type', 'cash')->first()->amount ?? 0),
            ]),
            'meta' => ['current_page' => $branches->currentPage(), 'last_page' => $branches->lastPage(), 'total' => $branches->total()]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:branches',
            'location' => 'required|string|max:255',
            'gold_available' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        $branch = Branch::create($request->only('name', 'location') + ['gold_available' => $request->gold_available ?? 0]);
        BranchBalance::create(['branch_id' => $branch->id, 'balance_type' => 'cash', 'amount' => 0, 'currency' => 'TZS']);
        BranchBalance::create(['branch_id' => $branch->id, 'balance_type' => 'gold', 'amount' => 0, 'currency' => 'g']);
        DB::commit();

        return response()->json(['status' => true, 'message' => 'Branch created.', 'data' => ['id' => $branch->id]], 201);
    }

    public function show($id)
    {
        $branch = Branch::with(['balances', 'users'])->findOrFail($id);
        return response()->json(['status' => true, 'data' => $branch]);
    }

    public function update(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:100|unique:branches,name,' . $id,
            'location' => 'required|string|max:255',
            'gold_available' => 'nullable|numeric|min:0',
        ]);

        $branch->update($request->only('name', 'location', 'gold_available'));
        return response()->json(['status' => true, 'message' => 'Branch updated.']);
    }

    public function delete($id)
    {
        $branch = Branch::findOrFail($id);
        if ($branch->users()->count() > 0 || $branch->transactions()->count() > 0) {
            return response()->json(['status' => false, 'message' => 'Cannot delete branch with users or transactions.'], 400);
        }
        DB::beginTransaction();
        $branch->balances()->delete();
        $branch->delete();
        DB::commit();
        return response()->json(['status' => true, 'message' => 'Branch deleted.']);
    }

    public function balance($id)
    {
        $branch = Branch::with('balances')->findOrFail($id);
        $cash = $branch->balances->where('balance_type', 'cash')->first()->amount ?? 0;
        $gold = $branch->balances->where('balance_type', 'gold')->first()->amount ?? 0;

        return response()->json([
            'status' => true,
            'data' => ['cash' => (float) $cash, 'gold' => (float) $gold]
        ]);
    }

    // Lookups (used by forms)
    public function lookup(Request $request)
    {
        $user = $request->user();
        $query = Branch::select('id', 'name');
        if (!in_array($user->role, ['director', 'admin', 'accountant'])) {
            $query->where('id', $user->assigned_branch);
        }
        if ($request->filled('search')) $query->where('name', 'like', "%{$request->search}%");
        
        return response()->json(['status' => true, 'data' => $query->limit(20)->get()]);
    }
}