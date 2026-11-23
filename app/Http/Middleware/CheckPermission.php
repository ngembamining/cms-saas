<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        $user = Auth::user();
        
        // Director has all permissions
        if ($user->role === 'director') {
            return $next($request);
        }
        
        // Check if user has the required permission
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($user, $permission)) {
                abort(403, 'Unauthorized action.');
            }
        }
        
        return $next($request);
    }
    
    /**
     * Check if user has a specific permission based on role.
     */
    private function hasPermission($user, $permission)
    {
        // Define role-based permissions
        $permissions = [
            'director' => [
                'loan.approve',
                'loan.reject',
                'loan.view',
                'loan.create',
                'loan.edit',
                'loan.delete',
                'user.manage',
                'branch.manage',
                'all'
            ],
            'branch_manager' => [
                'loan.approve',
                'loan.reject',
                'loan.view',
                'loan.create',
                'loan.edit',
            ],
            'accountant' => [
                'loan.view',
                'loan.payment',
            ],
            'secretary' => [
                'loan.view',
            ],
            'hr' => [
                'user.view',
            ],
            'staff' => [
                'loan.view',
            ],
        ];
        
        $role = $user->role;
        
        // Check if the role exists
        if (!isset($permissions[$role])) {
            return false;
        }
        
        // Check if the permission is in the role's allowed list
        return in_array($permission, $permissions[$role]) || in_array('all', $permissions[$role]);
    }
}