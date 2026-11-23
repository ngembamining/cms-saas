<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class RolesController extends Controller
{
    public function list()
    {
        $userCounts = User::select('role', \DB::raw('count(*) as count'))->groupBy('role')->pluck('count', 'role');

        $roles = [
            ['id' => 'director', 'name' => 'Director', 'description' => 'Full system access', 'users' => $userCounts['director'] ?? 0],
            ['id' => 'branch_manager', 'name' => 'Branch Manager', 'description' => 'Manage branch operations', 'users' => $userCounts['branch_manager'] ?? 0],
            ['id' => 'accountant', 'name' => 'Accountant', 'description' => 'Financial management', 'users' => $userCounts['accountant'] ?? 0],
            ['id' => 'secretary', 'name' => 'Secretary', 'description' => 'Communication & admin', 'users' => $userCounts['secretary'] ?? 0],
            ['id' => 'hr', 'name' => 'HR', 'description' => 'Staff administration', 'users' => $userCounts['hr'] ?? 0],
            ['id' => 'staff', 'name' => 'Staff', 'description' => 'Basic operational access', 'users' => $userCounts['staff'] ?? 0],
        ];

        return response()->json(['status' => true, 'data' => $roles]);
    }

    public function store(Request $request) { return response()->json(['status' => false, 'message' => 'Not implemented.'], 501); }
    public function show($id) { return $this->list(); }
    public function update(Request $request, $id) { return response()->json(['status' => false, 'message' => 'Not implemented.'], 501); }
    public function delete($id) { return response()->json(['status' => false, 'message' => 'Not implemented.'], 501); }
    public function changePermissions(Request $request, $id) { return response()->json(['status' => false, 'message' => 'Not implemented.'], 501); }
}