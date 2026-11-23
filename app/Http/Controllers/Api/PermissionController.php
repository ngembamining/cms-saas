<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function list()
    {
        $groups = [
            'Dashboard' => ['dashboard.view' => 'View Dashboard', 'dashboard.stats' => 'View Statistics'],
            'Transactions' => ['transaction.create' => 'Create', 'transaction.view' => 'View', 'transaction.edit' => 'Edit', 'transaction.delete' => 'Delete'],
            'Loans' => ['loan.create' => 'Apply', 'loan.view' => 'View', 'loan.approve' => 'Approve', 'loan.payment' => 'Payments'],
            'Management' => ['user.view' => 'View Users', 'user.create' => 'Create Users', 'role.view' => 'View Roles'],
        ];

        return response()->json(['status' => true, 'data' => $groups]);
    }

    public function store(Request $request) { return response()->json(['status' => false], 501); }
    public function show($id) { return response()->json(['status' => false], 501); }
    public function update(Request $request, $id) { return response()->json(['status' => false], 501); }
    public function delete($id) { return response()->json(['status' => false], 501); }
}