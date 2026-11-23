<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Branch;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = User::with(['branch']);
        
        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', '%' . $search . '%')
                  ->orWhere('full_name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('phone_number', 'like', '%' . $search . '%');
            });
        }
        
        // Role filter
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        
        // Branch filter
        if ($request->filled('branch_id')) {
            $query->where('assigned_branch', $request->branch_id);
        }
        
        $users = $query->orderBy('created_at', 'desc')->paginate(20)->appends($request->all());
        
        // Get roles and branches for filters
        $roles = [
            'director' => 'Director',
            'branch_manager' => 'Branch Manager',
            'accountant' => 'Accountant',
            'secretary' => 'Secretary',
            'hr' => 'HR',
            'staff' => 'Staff',
        ];
        
        $branches = Branch::all();
        
        return view('management.users.index', compact('users', 'roles', 'branches'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $branches = Branch::all();
        $roles = [
            'director' => 'Director',
            'branch_manager' => 'Branch Manager',
            'accountant' => 'Accountant',
            'secretary' => 'Secretary',
            'hr' => 'HR',
            'staff' => 'Staff',
        ];
        return view('management.users.create', compact('branches', 'roles'));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:50|unique:users',
            'full_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:100|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:director,branch_manager,accountant,secretary,hr,staff',
            'assigned_branch' => 'nullable|exists:branches,id',
            'phone_number' => 'nullable|string|max:20',
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'username' => $request->username,
                'full_name' => $request->full_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'assigned_branch' => $request->assigned_branch,
                'phone_number' => $request->phone_number,
                'password_changed' => false,
                'two_factor_enabled' => false,
                'created_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('management.users.index')
                ->with('success', '✅ User "' . $user->username . '" created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to create user: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified user.
     */
    public function show($id)
    {
        $user = User::with(['branch'])->findOrFail($id);
        return view('management.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);
        $branches = Branch::all();
        $roles = [
            'director' => 'Director',
            'branch_manager' => 'Branch Manager',
            'accountant' => 'Accountant',
            'secretary' => 'Secretary',
            'hr' => 'HR',
            'staff' => 'Staff',
        ];
        return view('management.users.edit', compact('user', 'branches', 'roles'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'username' => 'required|string|max:50|unique:users,username,' . $id,
            'full_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:100|unique:users,email,' . $id,
            'role' => 'required|in:director,branch_manager,accountant,secretary,hr,staff',
            'assigned_branch' => 'nullable|exists:branches,id',
            'phone_number' => 'nullable|string|max:20',
        ]);

        try {
            DB::beginTransaction();

            $user->update([
                'username' => $request->username,
                'full_name' => $request->full_name,
                'email' => $request->email,
                'role' => $request->role,
                'assigned_branch' => $request->assigned_branch,
                'phone_number' => $request->phone_number,
            ]);

            DB::commit();

            return redirect()->route('management.users.index')
                ->with('success', '✅ User "' . $user->username . '" updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to update user: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified user.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deleting yourself
        if ($user->id === Auth::id()) {
            return redirect()->route('management.users.index')
                ->with('warning', '⚠️ You cannot delete your own account.');
        }

        try {
            DB::beginTransaction();
            $user->delete();
            DB::commit();

            return redirect()->route('management.users.index')
                ->with('success', '✅ User "' . $user->username . '" deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to delete user: ' . $e->getMessage());
        }
    }

    /**
     * Reset user password.
     */
    public function resetPassword(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        try {
            DB::beginTransaction();

            $user->password = Hash::make($request->password);
            $user->password_changed = true;
            $user->save();

            DB::commit();

            return redirect()->route('management.users.index')
                ->with('success', '✅ Password reset for "' . $user->username . '" successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to reset password: ' . $e->getMessage());
        }
    }

    /**
     * Toggle user status (enable/disable).
     */
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        
        // Prevent disabling yourself
        if ($user->id === Auth::id()) {
            return redirect()->route('management.users.index')
                ->with('warning', '⚠️ You cannot disable your own account.');
        }

        try {
            // Simple toggle - you can add a 'is_active' field to users table
            // For now, we'll just toggle a flag or redirect with message
            return redirect()->route('management.users.index')
                ->with('info', 'ℹ️ Status toggle functionality - add is_active field to users table.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', '❌ Failed to toggle status: ' . $e->getMessage());
        }
    }

    /**
     * Get user list for API.
     */
    public function getList(Request $request)
    {
        $query = User::select('id', 'username', 'full_name', 'role');
        
        if ($request->filled('search')) {
            $query->where('username', 'like', '%' . $request->search . '%');
        }
        
        return response()->json($query->limit(20)->get());
    }

    /**
     * Get users by role for API.
     */
    public function getByRole($role)
    {
        $users = User::where('role', $role)
            ->select('id', 'username', 'full_name')
            ->get();
        return response()->json($users);
    }

    /**
     * Lookup users for API.
     */
    public function lookup(Request $request)
    {
        $query = User::select('id', 'username', 'full_name');
        
        if ($request->filled('search')) {
            $query->where('username', 'like', '%' . $request->search . '%');
        }
        
        return response()->json($query->limit(20)->get());
    }
}