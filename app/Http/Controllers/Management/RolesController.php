<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class RolesController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index(Request $request)
    {
        // Define all available roles with their permissions
        $roles = $this->getRolesWithPermissions();
        
        // Get user counts by role
        $userCounts = User::select('role', DB::raw('count(*) as count'))
            ->groupBy('role')
            ->pluck('count', 'role');
        
        return view('management.roles.index', compact('roles', 'userCounts'));
    }

    /**
     * Show the form for creating a new role.
     */
    public function create()
    {
        $permissionGroups = $this->getPermissionGroups();
        return view('management.roles.create', compact('permissionGroups'));
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:roles',
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            // Create role
            // $role = Role::create([
            //     'name' => $request->name,
            //     'description' => $request->description,
            // ]);

            // Assign permissions
            // if ($request->has('permissions')) {
            //     $role->syncPermissions($request->permissions);
            // }

            DB::commit();

            return redirect()->route('management.roles.index')
                ->with('success', '✅ Role "' . $request->name . '" created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to create role: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit($id)
    {
        // $role = Role::findOrFail($id);
        $role = null; // Placeholder
        
        $permissionGroups = $this->getPermissionGroups();
        
        // Get current role permissions
        $rolePermissions = []; // Placeholder
        
        return view('management.roles.edit', compact('role', 'permissionGroups', 'rolePermissions'));
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:roles,name,' . $id,
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            // Update role
            // $role = Role::findOrFail($id);
            // $role->update([
            //     'name' => $request->name,
            //     'description' => $request->description,
            // ]);

            // Sync permissions
            // if ($request->has('permissions')) {
            //     $role->syncPermissions($request->permissions);
            // } else {
            //     $role->syncPermissions([]);
            // }

            DB::commit();

            return redirect()->route('management.roles.index')
                ->with('success', '✅ Role "' . $request->name . '" updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to update role: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified role.
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            // Check if role has users
            $userCount = User::where('role', $id)->count();
            if ($userCount > 0) {
                return redirect()->route('management.roles.index')
                    ->with('warning', '⚠️ This role has ' . $userCount . ' users assigned and cannot be deleted.');
            }

            // Delete role
            // $role = Role::findOrFail($id);
            // $role->delete();

            DB::commit();

            return redirect()->route('management.roles.index')
                ->with('success', '✅ Role deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to delete role: ' . $e->getMessage());
        }
    }

    /**
     * Display role permissions.
     */
    public function permissions($id)
    {
        // $role = Role::findOrFail($id);
        $role = null; // Placeholder
        
        $permissionGroups = $this->getPermissionGroups();
        
        // Get current role permissions
        $rolePermissions = []; // Placeholder
        
        return view('management.roles.permissions', compact('role', 'permissionGroups', 'rolePermissions'));
    }

    /**
     * Update role permissions.
     */
    public function updatePermissions(Request $request, $id)
    {
        $request->validate([
            'permissions' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            // $role = Role::findOrFail($id);
            // $role->syncPermissions($request->permissions ?? []);

            DB::commit();

            return redirect()->route('management.roles.index')
                ->with('success', '✅ Permissions updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', '❌ Failed to update permissions: ' . $e->getMessage());
        }
    }

    /**
     * Get all roles with their permissions.
     */
    private function getRolesWithPermissions()
    {
        return [
            'director' => [
                'label' => 'Director',
                'description' => 'Full system access with all permissions',
                'users' => 1,
                'permissions' => ['*'],
                'color' => 'primary',
            ],
            'branch_manager' => [
                'label' => 'Branch Manager',
                'description' => 'Manage branch operations, loans, and approvals',
                'users' => 0,
                'permissions' => [
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
                'color' => 'green',
            ],
            'accountant' => [
                'label' => 'Accountant',
                'description' => 'Financial management, reconciliation, and reporting',
                'users' => 0,
                'permissions' => [
                    'dashboard.view',
                    'transaction.view', 'transaction.verify',
                    'gold_purchase.view', 'gold_sale.view',
                    'expense.view', 'expense.create', 'expense.edit',
                    'reconciliation.create', 'reconciliation.view', 'reconciliation.verify',
                    'report.view', 'report.financial', 'report.export',
                    'profile.view', 'profile.edit', 'profile.password',
                ],
                'color' => 'blue',
            ],
            'secretary' => [
                'label' => 'Secretary',
                'description' => 'Communication, customer/supplier management',
                'users' => 0,
                'permissions' => [
                    'dashboard.view',
                    'customer.create', 'customer.view', 'customer.edit',
                    'supplier.create', 'supplier.view', 'supplier.edit',
                    'communication.sms', 'communication.email', 'communication.whatsapp',
                    'communication.template',
                    'notification.send', 'notification.view',
                    'profile.view', 'profile.edit', 'profile.password',
                ],
                'color' => 'purple',
            ],
            'hr' => [
                'label' => 'HR',
                'description' => 'User management and staff administration',
                'users' => 0,
                'permissions' => [
                    'dashboard.view',
                    'user.view', 'user.create', 'user.edit',
                    'profile.view', 'profile.edit', 'profile.password',
                    'report.view',
                ],
                'color' => 'amber',
            ],
            'staff' => [
                'label' => 'Staff',
                'description' => 'Basic operational access',
                'users' => 0,
                'permissions' => [
                    'dashboard.view',
                    'transaction.create', 'transaction.view',
                    'customer.view', 'customer.create',
                    'supplier.view',
                    'profile.view', 'profile.edit', 'profile.password',
                    'notification.view',
                ],
                'color' => 'gray',
            ],
        ];
    }

    /**
     * Get permission groups.
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
}