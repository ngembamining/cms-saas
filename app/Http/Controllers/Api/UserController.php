<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Branch;

class UserController extends Controller
{
    public function list(Request $request)
    {
        $query = User::with(['branch']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('username', 'like', "%{$s}%")->orWhere('full_name', 'like', "%{$s}%"));
        }
        if ($request->filled('role')) $query->where('role', $request->role);
        if ($request->filled('branch_id')) $query->where('assigned_branch', $request->branch_id);

        $users = $query->latest()->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $users->map(fn($u) => [
                'id' => (string) $u->id,
                'username' => $u->username,
                'full_name' => $u->full_name,
                'email' => $u->email,
                'role' => $u->role,
                'branch_name' => $u->branch->name ?? 'Unassigned',
                'phone_number' => $u->phone_number,
                'status' => $u->password_changed ? 'Active' : 'Pending First Login',
            ]),
            'meta' => ['current_page' => $users->currentPage(), 'last_page' => $users->lastPage(), 'total' => $users->total()]
        ]);
    }

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
        ]);

        return response()->json(['status' => true, 'message' => 'User created.', 'data' => ['id' => $user->id]], 201);
    }

    public function profile($id)
    {
        $user = User::with(['branch'])->findOrFail($id);
        return response()->json(['status' => true, 'data' => $user]);
    }

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

        $user->update($request->only(['username', 'full_name', 'email', 'role', 'assigned_branch', 'phone_number']));
        return response()->json(['status' => true, 'message' => 'User updated.']);
    }

    public function delete($id)
    {
        if ($id == request()->user()->id) return response()->json(['status' => false, 'message' => 'Cannot delete yourself.'], 403);
        User::findOrFail($id)->delete();
        return response()->json(['status' => true, 'message' => 'User deleted.']);
    }

    public function resetPassword(Request $request, $id)
    {
        $request->validate(['password' => 'required|string|min:6|confirmed']);
        $user = User::findOrFail($id);
        $user->password = Hash::make($request->password);
        $user->password_changed = true;
        $user->save();
        return response()->json(['status' => true, 'message' => 'Password reset.']);
    }

    public function toggleStatus($id)
    {
        return response()->json(['status' => true, 'message' => 'Status toggle not implemented in schema yet.']);
    }

    public function lookup(Request $request)
    {
        $query = User::select('id', 'username', 'full_name');
        if ($request->filled('search')) $query->where('username', 'like', "%{$request->search}%");
        return response()->json($query->limit(20)->get());
    }
}