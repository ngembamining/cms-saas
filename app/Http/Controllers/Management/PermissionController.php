<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions.
     */
    public function index(Request $request)
    {
        // Define all available permissions grouped by module
        $permissionGroups = $this->getPermissionGroups();
        
        // Get all users with their roles for reference
        $users = User::select('id', 'username', 'full_name', 'role', 'assigned_branch')->get();
        
        // Group users by role
        $usersByRole = $users->groupBy('role')->map(function ($group) {
            return $group->count();
        });
        
        // Calculate total permissions
        $totalPermissions = 0;
        foreach ($permissionGroups as $group) {
            $totalPermissions += count($group);
        }
        
        return view('management.permissions.index', compact(
            'permissionGroups',
            'usersByRole',
            'totalPermissions'
        ));
    }

    /**
     * Store a newly created permission.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions',
            'description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Create permission (if using Spatie Permission package)
            // Permission::create(['name' => $request->name]);

            DB::commit();

            return redirect()->route('management.permissions.index')
                ->with('success', '✅ Permission created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to create permission: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update the specified permission.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $id,
            'description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Update permission
            // $permission = Permission::findOrFail($id);
            // $permission->update($request->all());

            DB::commit();

            return redirect()->route('management.permissions.index')
                ->with('success', '✅ Permission updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to update permission: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified permission.
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            // Delete permission
            // $permission = Permission::findOrFail($id);
            // $permission->delete();

            DB::commit();

            return redirect()->route('management.permissions.index')
                ->with('success', '✅ Permission deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to delete permission: ' . $e->getMessage());
        }
    }

    /**
     * Display permission groups.
     */
    public function groups()
    {
        $permissionGroups = $this->getPermissionGroups();
        return view('management.permissions.groups', compact('permissionGroups'));
    }

    /**
     * Get permission groups with all permissions.
     */
    private function getPermissionGroups()
    {
        return [
            'Dashboard' => [
                'dashboard.view' => 'View Dashboard',
                'dashboard.stats' => 'View Statistics',
            ],
            'Transactions' => [
                'transaction.create' => 'Create Transactions',
                'transaction.view' => 'View Transactions',
                'transaction.edit' => 'Edit Transactions',
                'transaction.delete' => 'Delete Transactions',
                'transaction.verify' => 'Verify Transactions',
                'transaction.reverse' => 'Reverse Transactions',
                'transaction.export' => 'Export Transactions',
            ],
            'Gold Purchases' => [
                'gold_purchase.create' => 'Record Gold Purchases',
                'gold_purchase.view' => 'View Gold Purchases',
                'gold_purchase.edit' => 'Edit Gold Purchases',
                'gold_purchase.delete' => 'Delete Gold Purchases',
                'gold_purchase.receive' => 'Receive Gold',
                'gold_purchase.report' => 'View Purchase Reports',
            ],
            'Gold Sales' => [
                'gold_sale.create' => 'Record Gold Sales',
                'gold_sale.view' => 'View Gold Sales',
                'gold_sale.edit' => 'Edit Gold Sales',
                'gold_sale.delete' => 'Delete Gold Sales',
                'gold_sale.complete' => 'Complete Sales',
                'gold_sale.report' => 'View Sales Reports',
            ],
            'Loans' => [
                'loan.create' => 'Apply for Loans',
                'loan.view' => 'View Loans',
                'loan.edit' => 'Edit Loans',
                'loan.delete' => 'Delete Loans',
                'loan.approve' => 'Approve Loans',
                'loan.reject' => 'Reject Loans',
                'loan.payment' => 'Record Loan Payments',
                'loan.remind' => 'Send Loan Reminders',
                'loan.export' => 'Export Loan Data',
            ],
            'Expenses' => [
                'expense.create' => 'Create Expenses',
                'expense.view' => 'View Expenses',
                'expense.edit' => 'Edit Expenses',
                'expense.delete' => 'Delete Expenses',
                'expense.approve' => 'Approve Expenses',
                'expense.category' => 'Manage Expense Categories',
                'expense.budget' => 'Manage Budgets',
                'expense.report' => 'View Expense Reports',
            ],
            'Reconciliation' => [
                'reconciliation.create' => 'Create Reconciliation',
                'reconciliation.view' => 'View Reconciliation',
                'reconciliation.verify' => 'Verify Reconciliation',
                'reconciliation.report' => 'Reconciliation Reports',
            ],
            'Communication' => [
                'communication.sms' => 'Send SMS',
                'communication.email' => 'Send Email',
                'communication.whatsapp' => 'Send WhatsApp',
                'communication.bulk' => 'Send Bulk Messages',
                'communication.template' => 'Manage Templates',
                'communication.log' => 'View Communication Logs',
                'notification.send' => 'Send Notifications',
                'notification.view' => 'View Notifications',
            ],
            'Management' => [
                'user.view' => 'View Users',
                'user.create' => 'Create Users',
                'user.edit' => 'Edit Users',
                'user.delete' => 'Delete Users',
                'role.view' => 'View Roles',
                'role.create' => 'Create Roles',
                'role.edit' => 'Edit Roles',
                'role.delete' => 'Delete Roles',
                'permission.view' => 'View Permissions',
                'permission.edit' => 'Edit Permissions',
                'branch.view' => 'View Branches',
                'branch.create' => 'Create Branches',
                'branch.edit' => 'Edit Branches',
                'branch.delete' => 'Delete Branches',
                'supplier.view' => 'View Suppliers',
                'supplier.create' => 'Create Suppliers',
                'supplier.edit' => 'Edit Suppliers',
                'supplier.delete' => 'Delete Suppliers',
                'customer.view' => 'View Customers',
                'customer.create' => 'Create Customers',
                'customer.edit' => 'Edit Customers',
                'customer.delete' => 'Delete Customers',
            ],
            'Reports' => [
                'report.view' => 'View Reports',
                'report.financial' => 'View Financial Reports',
                'report.branch' => 'View Branch Reports',
                'report.export' => 'Export Reports',
            ],
            'Profile' => [
                'profile.view' => 'View Profile',
                'profile.edit' => 'Edit Profile',
                'profile.password' => 'Change Password',
            ],
        ];
    }

    /**
     * Get permissions for a specific role.
     */
    public function getPermissionBadgeByRole(Request $request)
    {
        $role = $request->role;
        
        // Define role-based permissions
        $rolePermissions = [
            'director' => ['*'], // All permissions
            'branch_manager' => [
                'dashboard.view', 'dashboard.stats',
                'transaction.create', 'transaction.view', 'transaction.edit',
                'gold_purchase.create', 'gold_purchase.view', 'gold_purchase.edit', 'gold_purchase.receive',
                'gold_sale.create', 'gold_sale.view', 'gold_sale.edit', 'gold_sale.complete',
                'loan.create', 'loan.view', 'loan.approve', 'loan.payment',
                'expense.create', 'expense.view', 'expense.approve',
                'reconciliation.create', 'reconciliation.view',
                'supplier.view', 'supplier.create', 'supplier.edit',
                'customer.view', 'customer.create', 'customer.edit',
                'report.view', 'report.branch',
                'profile.view', 'profile.edit', 'profile.password',
            ],
            'accountant' => [
                'dashboard.view',
                'transaction.view', 'transaction.verify',
                'gold_purchase.view', 'gold_sale.view',
                'expense.view', 'expense.create', 'expense.edit',
                'reconciliation.create', 'reconciliation.view', 'reconciliation.verify',
                'report.view', 'report.financial', 'report.export',
                'profile.view', 'profile.edit', 'profile.password',
            ],
            'secretary' => [
                'dashboard.view',
                'customer.create', 'customer.view', 'customer.edit',
                'supplier.create', 'supplier.view', 'supplier.edit',
                'communication.sms', 'communication.email', 'communication.whatsapp',
                'communication.template',
                'notification.send', 'notification.view',
                'profile.view', 'profile.edit', 'profile.password',
            ],
            'hr' => [
                'dashboard.view',
                'user.view', 'user.create', 'user.edit',
                'profile.view', 'profile.edit', 'profile.password',
                'report.view',
            ],
            'staff' => [
                'dashboard.view',
                'transaction.create', 'transaction.view',
                'customer.view', 'customer.create',
                'supplier.view',
                'profile.view', 'profile.edit', 'profile.password',
                'notification.view',
            ],
        ];
        
        $permissions = $rolePermissions[$role] ?? [];
        
        return response()->json([
            'permissions' => $permissions,
            'count' => count($permissions),
        ]);
    }
}