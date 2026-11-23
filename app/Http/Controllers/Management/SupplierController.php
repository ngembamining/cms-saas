<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Supplier;
use App\Models\Branch;

class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = Supplier::with(['branch']);
        
        // Branch filter for non-director users
        if ($user->role !== 'director') {
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
        
        $suppliers = $query->orderBy('name', 'asc')->paginate(20)->appends($request->all());
        
        // Get branches for filter
        $branches = Branch::when($user->role !== 'director', function ($q) use ($user) {
            return $q->where('id', $user->assigned_branch);
        })->get();
        
        return view('management.suppliers.index', compact('suppliers', 'branches'));
    }

    /**
     * Show the form for creating a new supplier.
     */
    public function create()
    {
        $user = Auth::user();
        
        $branches = Branch::when($user->role !== 'director', function ($q) use ($user) {
            return $q->where('id', $user->assigned_branch);
        })->get();
        
        return view('management.suppliers.create', compact('branches'));
    }

    /**
     * Store a newly created supplier.
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

            $supplier = Supplier::create([
                'name' => $request->name,
                'contact' => $request->contact,
                'address' => $request->address,
                'branch_id' => $request->branch_id,
                'email' => $request->email,
                'created_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('suppliers.index')
                ->with('success', '✅ Supplier "' . $supplier->name . '" created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to create supplier: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified supplier.
     */
    public function show($id)
    {
        $supplier = Supplier::with(['branch', 'transactions', 'loans'])
            ->findOrFail($id);
        
        $user = Auth::user();
        if ($user->role !== 'director' && $supplier->branch_id != $user->assigned_branch) {
            abort(403, 'You do not have access to this supplier.');
        }
        
        // Get transaction summary
        $totalPurchases = $supplier->transactions()
            ->where('transaction_type', 'gold_purchase')
            ->sum('amount');
            
        $totalLoans = $supplier->loans()
            ->where('status', 'approved')
            ->sum('amount');
        
        $recentTransactions = $supplier->transactions()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        return view('management.suppliers.show', compact(
            'supplier',
            'totalPurchases',
            'totalLoans',
            'recentTransactions'
        ));
    }

    /**
     * Show the form for editing the specified supplier.
     */
    public function edit($id)
    {
        $supplier = Supplier::findOrFail($id);
        
        $user = Auth::user();
        if ($user->role !== 'director' && $supplier->branch_id != $user->assigned_branch) {
            abort(403, 'You do not have access to this supplier.');
        }
        
        $branches = Branch::all();
        
        return view('management.suppliers.edit', compact('supplier', 'branches'));
    }

    /**
     * Update the specified supplier.
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

        try {
            DB::beginTransaction();

            $supplier->update([
                'name' => $request->name,
                'contact' => $request->contact,
                'address' => $request->address,
                'branch_id' => $request->branch_id,
                'email' => $request->email,
            ]);

            DB::commit();

            return redirect()->route('suppliers.index')
                ->with('success', '✅ Supplier "' . $supplier->name . '" updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to update supplier: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified supplier.
     */
    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);
        
        // Check if supplier has transactions
        if ($supplier->transactions()->count() > 0) {
            return redirect()->route('suppliers.index')
                ->with('warning', '⚠️ This supplier has transactions and cannot be deleted.');
        }
        
        // Check if supplier has loans
        if ($supplier->loans()->count() > 0) {
            return redirect()->route('suppliers.index')
                ->with('warning', '⚠️ This supplier has loans and cannot be deleted.');
        }

        try {
            DB::beginTransaction();
            $supplier->delete();
            DB::commit();

            return redirect()->route('suppliers.index')
                ->with('success', '✅ Supplier "' . $supplier->name . '" deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to delete supplier: ' . $e->getMessage());
        }
    }

    /**
     * Get supplier transactions.
     */
    public function transactions($id)
    {
        $supplier = Supplier::findOrFail($id);
        
        $user = Auth::user();
        if ($user->role !== 'director' && $supplier->branch_id != $user->assigned_branch) {
            abort(403, 'You do not have access to this supplier.');
        }
        
        $transactions = $supplier->transactions()
            ->with(['branch', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('management.suppliers.transactions', compact('supplier', 'transactions'));
    }

    /**
     * Get supplier loans.
     */
    public function loans($id)
    {
        $supplier = Supplier::findOrFail($id);
        
        $user = Auth::user();
        if ($user->role !== 'director' && $supplier->branch_id != $user->assigned_branch) {
            abort(403, 'You do not have access to this supplier.');
        }
        
        $loans = $supplier->loans()
            ->with(['branch', 'creator'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('management.suppliers.loans', compact('supplier', 'loans'));
    }

    /**
     * Merge two suppliers.
     */
    public function merge(Request $request, $id)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id|different:id',
        ]);

        try {
            DB::beginTransaction();

            $primarySupplier = Supplier::findOrFail($id);
            $secondarySupplier = Supplier::findOrFail($request->supplier_id);
            
            // Update transactions
            DB::table('transactions')
                ->where('supplier_id', $secondarySupplier->id)
                ->update(['supplier_id' => $primarySupplier->id]);
            
            // Update loans
            DB::table('loans')
                ->where('suppliers_id', $secondarySupplier->id)
                ->update(['suppliers_id' => $primarySupplier->id]);
            
            // Delete secondary supplier
            $secondarySupplier->delete();

            DB::commit();

            return redirect()->route('suppliers.show', $primarySupplier->id)
                ->with('success', '✅ Suppliers merged successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to merge suppliers: ' . $e->getMessage());
        }
    }

    /**
     * Export suppliers to CSV.
     */
    public function export()
    {
        $user = Auth::user();
        
        $query = Supplier::query();
        
        if ($user->role !== 'director') {
            $query->where('branch_id', $user->assigned_branch);
        }
        
        $suppliers = $query->orderBy('name', 'asc')->get();
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="suppliers_' . date('Y-m-d') . '.csv"',
        ];
        
        $callback = function() use ($suppliers) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            
            fputcsv($file, ['ID', 'Name', 'Contact', 'Email', 'Address', 'Branch', 'Created At']);
            
            foreach ($suppliers as $supplier) {
                fputcsv($file, [
                    $supplier->id,
                    $supplier->name,
                    $supplier->contact,
                    $supplier->email ?? '',
                    $supplier->address ?? '',
                    $supplier->branch->name ?? 'N/A',
                    $supplier->created_at ? $supplier->created_at->format('Y-m-d H:i') : '',
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get supplier list for API.
     */
    public function lookup(Request $request)
    {
        $user = Auth::user();
        
        $query = Supplier::select('id', 'name', 'contact');
        
        if ($user->role !== 'director') {
            $query->where('branch_id', $user->assigned_branch);
        }
        
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        return response()->json($query->limit(20)->get());
    }

    // ============================================
    // IMPORT FUNCTIONALITY
    // ============================================

    /**
     * Download import template CSV
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="suppliers_template.csv"',
        ];
        
        $columns = ['name', 'contact', 'email', 'address', 'branch_id'];
        
        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, $columns);
            fputcsv($file, ['Example Supplier', '255712345678', 'supplier@example.com', 'P.O. Box 123, City', '1']);
            fputcsv($file, ['Another Supplier', '255798765432', 'another@example.com', 'P.O. Box 456, Town', '2']);
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import suppliers from CSV file
     */
    public function import(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'file' => 'required|file|max:5120',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed: ' . $validator->errors()->first(),
                    'errors' => $validator->errors()->all()
                ], 422);
            }

            $file = $request->file('file');
            
            $mimeType = $file->getMimeType();
            $extension = strtolower($file->getClientOriginalExtension());
            
            $allowedMimes = [
                'text/csv', 'text/plain', 'text/x-csv',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/csv', 'application/x-csv', 'application/octet-stream',
            ];
            
            $allowedExtensions = ['csv', 'xlsx', 'xls', 'txt'];
            
            $isValid = in_array($mimeType, $allowedMimes) || in_array($extension, $allowedExtensions);
            
            if (!$isValid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file type. Please upload a CSV, XLSX, or XLS file.',
                    'errors' => []
                ], 422);
            }

            $duplicateAction = $request->input('duplicate_action', 'skip');

            $handle = fopen($file->getRealPath(), 'r');
            if (!$handle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not read the uploaded file.',
                    'errors' => []
                ], 400);
            }

            $headers = fgetcsv($handle);
            if (!$headers) {
                fclose($handle);
                return response()->json([
                    'success' => false,
                    'message' => 'The file is empty or invalid.',
                    'errors' => []
                ], 400);
            }

            // Remove BOM from first header
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);

            // Clean headers
            $headers = array_map(function($header) {
                $header = trim($header);
                $header = strtolower($header);
                return $header;
            }, $headers);

            // Header mapping with alternatives
            $headerMap = [
                'name' => ['name', 'supplier_name', 'full_name', 'supplier'],
                'contact' => ['contact', 'phone', 'phone_number', 'mobile', 'telephone'],
                'email' => ['email', 'e_mail', 'mail', 'email_address'],
                'address' => ['address', 'location', 'physical_address', 'street'],
                'branch_id' => ['branch_id', 'branch', 'branchid', 'branch_code'],
            ];

            // Map actual headers to expected headers
            $mappedHeaders = [];
            foreach ($headers as $header) {
                $found = false;
                foreach ($headerMap as $expected => $alternatives) {
                    if (in_array($header, $alternatives)) {
                        $mappedHeaders[] = $expected;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $mappedHeaders[] = $header;
                }
            }

            // Check required columns
            $missingHeaders = array_diff(['name', 'contact'], $mappedHeaders);
            if (!empty($missingHeaders)) {
                fclose($handle);
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required columns: ' . implode(', ', $missingHeaders) . 
                               '. Found: ' . implode(', ', $headers),
                    'errors' => []
                ], 422);
            }

            $imported = 0;
            $skipped = 0;
            $failed = 0;
            $errors = [];
            $rowNumber = 1;

            DB::beginTransaction();

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                
                if (empty(array_filter($row))) {
                    continue;
                }

                $data = [];
                foreach ($mappedHeaders as $index => $header) {
                    $data[$header] = $row[$index] ?? null;
                }

                $data = array_map('trim', $data);

                if (empty($data['name']) || empty($data['contact'])) {
                    $failed++;
                    $errors[] = "Row {$rowNumber}: Name and Contact are required";
                    continue;
                }

                $branchId = null;
                if (!empty($data['branch_id']) && is_numeric($data['branch_id'])) {
                    $branch = Branch::find($data['branch_id']);
                    if (!$branch) {
                        $failed++;
                        $errors[] = "Row {$rowNumber}: Branch ID '{$data['branch_id']}' not found";
                        continue;
                    }
                    $branchId = $branch->id;
                }

                $existing = Supplier::where('contact', $data['contact'])->first();

                if ($existing) {
                    if ($duplicateAction === 'skip') {
                        $skipped++;
                        continue;
                    } elseif ($duplicateAction === 'update') {
                        $existing->update([
                            'name' => $data['name'],
                            'email' => $data['email'] ?? $existing->email,
                            'address' => $data['address'] ?? $existing->address,
                            'branch_id' => $branchId ?? $existing->branch_id,
                        ]);
                        $imported++;
                        continue;
                    }
                }

                Supplier::create([
                    'name' => $data['name'],
                    'contact' => $data['contact'],
                    'email' => $data['email'] ?? null,
                    'address' => $data['address'] ?? null,
                    'branch_id' => $branchId,
                    'created_at' => now(),
                ]);
                $imported++;
            }

            fclose($handle);
            DB::commit();

            return response()->json([
                'success' => true,
                'imported' => $imported,
                'skipped' => $skipped,
                'failed' => $failed,
                'total' => $imported + $skipped + $failed,
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
                'errors' => []
            ], 500);
        }
    }

    /**
     * Download sample import file (alias for downloadTemplate)
     */
    public function sample()
    {
        return $this->downloadTemplate();
    }
}