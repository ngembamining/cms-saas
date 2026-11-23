<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Asset;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssetController extends Controller
{
    /**
     * Check if user can access all branches (Accountant or Director)
     */
    private function canAccessAllBranches()
    {
        return in_array(Auth::user()->role, ['accountant', 'director']);
    }

    /**
     * Display a listing of assets.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = Asset::with(['branch', 'assignedTo', 'creator']);
        
        // Apply branch filter for non-director/accountant users
        if (!$this->canAccessAllBranches()) {
            $query->where('branch_id', $user->assigned_branch);
        }
        
        // Apply filters
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('asset_code', 'LIKE', "%{$search}%")
                  ->orWhere('serial_number', 'LIKE', "%{$search}%");
            });
        }
        
        $assets = $query->orderBy('created_at', 'desc')->paginate(20)->appends($request->all());
        
        // Statistics
        $totalAssets = (clone $query)->count();
        $totalValue = (clone $query)->sum('current_value');
        $totalPurchaseValue = (clone $query)->sum('purchase_price');
        
        // Categories for filter
        $categories = Asset::distinct()->pluck('category');
        $branches = Branch::when(!$this->canAccessAllBranches(), function ($q) use ($user) {
            return $q->where('id', $user->assigned_branch);
        })->get();
        $statuses = ['active', 'maintenance', 'disposed'];
        
        return view('finance.assets.index', compact(
            'assets',
            'totalAssets',
            'totalValue',
            'totalPurchaseValue',
            'categories',
            'branches',
            'statuses'
        ));
    }

    /**
     * Show the form for creating a new asset.
     */
    public function create()
    {
        $user = Auth::user();
        
        $branches = Branch::when(!$this->canAccessAllBranches(), function ($q) use ($user) {
            return $q->where('id', $user->assigned_branch);
        })->get();
        
        $users = User::all();
        $categories = ['Office Equipment', 'Computer & Electronics', 'Motor Vehicle', 'Furniture', 'Machinery', 'Building', 'Other'];
        $statuses = ['active', 'maintenance', 'disposed'];
        
        return view('finance.assets.create', compact('branches', 'users', 'categories', 'statuses'));
    }

    /**
     * Store a newly created asset.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'purchase_price' => 'required|numeric|min:0',
            'current_value' => 'required|numeric|min:0',
            'purchase_date' => 'required|date',
            'branch_id' => 'required|exists:branches,id',
            'supplier' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'required|in:active,maintenance,disposed',
            'depreciation_rate' => 'nullable|numeric|min:0|max:100',
            'warranty_until' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Generate asset code
            $assetCode = 'AST-' . date('Y') . '-' . strtoupper(Str::random(6));
            
            $asset = Asset::create([
                'asset_code' => $assetCode,
                'name' => $request->name,
                'category' => $request->category,
                'description' => $request->description,
                'purchase_price' => $request->purchase_price,
                'current_value' => $request->current_value,
                'purchase_date' => $request->purchase_date,
                'supplier' => $request->supplier,
                'serial_number' => $request->serial_number,
                'location' => $request->location,
                'branch_id' => $request->branch_id,
                'assigned_to' => $request->assigned_to,
                'status' => $request->status,
                'depreciation_rate' => $request->depreciation_rate ?? 0,
                'warranty_until' => $request->warranty_until,
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('finance.assets.index')
                ->with('success', '✅ Asset created successfully! Asset Code: ' . $assetCode);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to create asset: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified asset.
     */
    public function show($id)
    {
        $asset = Asset::with(['branch', 'assignedTo', 'creator'])->findOrFail($id);
        
        // Check permission
        if (!$this->canAccessAllBranches() && $asset->branch_id != Auth::user()->assigned_branch) {
            abort(403, 'You do not have access to this asset.');
        }
        
        return view('finance.assets.show', compact('asset'));
    }

    /**
     * Show the form for editing the specified asset.
     */
    public function edit($id)
    {
        $asset = Asset::findOrFail($id);
        
        // Check permission
        if (!$this->canAccessAllBranches() && $asset->branch_id != Auth::user()->assigned_branch) {
            abort(403, 'You do not have access to this asset.');
        }
        
        $user = Auth::user();
        $branches = Branch::when(!$this->canAccessAllBranches(), function ($q) use ($user) {
            return $q->where('id', $user->assigned_branch);
        })->get();
        $users = User::all();
        $categories = ['Office Equipment', 'Computer & Electronics', 'Motor Vehicle', 'Furniture', 'Machinery', 'Building', 'Other'];
        $statuses = ['active', 'maintenance', 'disposed'];
        
        return view('finance.assets.edit', compact('asset', 'branches', 'users', 'categories', 'statuses'));
    }

    /**
     * Update the specified asset.
     */
    public function update(Request $request, $id)
    {
        $asset = Asset::findOrFail($id);
        
        // Check permission
        if (!$this->canAccessAllBranches() && $asset->branch_id != Auth::user()->assigned_branch) {
            abort(403, 'You do not have access to this asset.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'purchase_price' => 'required|numeric|min:0',
            'current_value' => 'required|numeric|min:0',
            'purchase_date' => 'required|date',
            'branch_id' => 'required|exists:branches,id',
            'supplier' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'required|in:active,maintenance,disposed',
            'depreciation_rate' => 'nullable|numeric|min:0|max:100',
            'warranty_until' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        try {
            $asset->update($request->all());

            return redirect()->route('finance.assets.index')
                ->with('success', '✅ Asset updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', '❌ Failed to update asset: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified asset.
     */
    public function destroy($id)
    {
        $asset = Asset::findOrFail($id);
        
        // Check permission
        if (!$this->canAccessAllBranches() && $asset->branch_id != Auth::user()->assigned_branch) {
            abort(403, 'You do not have access to this asset.');
        }
        
        try {
            $asset->delete();

            return redirect()->route('finance.assets.index')
                ->with('success', '✅ Asset deleted successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', '❌ Failed to delete asset: ' . $e->getMessage());
        }
    }

    /**
     * Print asset report
     */
    public function print(Request $request)
    {
        $user = Auth::user();
        
        $query = Asset::with(['branch', 'assignedTo']);
        
        if (!$this->canAccessAllBranches()) {
            $query->where('branch_id', $user->assigned_branch);
        }
        
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        $assets = $query->orderBy('category')->get();
        
        $totalValue = $assets->sum('current_value');
        $totalPurchaseValue = $assets->sum('purchase_price');
        $totalDepreciation = $totalPurchaseValue - $totalValue;
        
        $byCategory = $assets->groupBy('category')->map(function($group) {
            return [
                'count' => $group->count(),
                'value' => $group->sum('current_value'),
                'purchase_value' => $group->sum('purchase_price')
            ];
        });
        
        return view('finance.assets.print', compact(
            'assets',
            'totalValue',
            'totalPurchaseValue',
            'totalDepreciation',
            'byCategory'
        ));
    }

    /**
     * Export assets to CSV
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        
        $query = Asset::with(['branch', 'assignedTo']);
        
        if (!$this->canAccessAllBranches()) {
            $query->where('branch_id', $user->assigned_branch);
        }
        
        $assets = $query->get();
        
        $filename = 'assets_report_' . date('Y-m-d') . '.csv';
        $handle = fopen('php://temp', 'r+');
        
        // Headers
        fputcsv($handle, [
            'Asset Code',
            'Name',
            'Category',
            'Branch',
            'Purchase Price',
            'Current Value',
            'Depreciation',
            'Purchase Date',
            'Supplier',
            'Serial Number',
            'Location',
            'Assigned To',
            'Status',
            'Notes'
        ]);
        
        foreach ($assets as $asset) {
            fputcsv($handle, [
                $asset->asset_code,
                $asset->name,
                $asset->category,
                $asset->branch->name ?? 'N/A',
                $asset->purchase_price,
                $asset->current_value,
                $asset->purchase_price - $asset->current_value,
                $asset->purchase_date->format('d/m/Y'),
                $asset->supplier ?? 'N/A',
                $asset->serial_number ?? 'N/A',
                $asset->location ?? 'N/A',
                $asset->assignedTo->name ?? 'Unassigned',
                $asset->status,
                $asset->notes ?? ''
            ]);
        }
        
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        
        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}