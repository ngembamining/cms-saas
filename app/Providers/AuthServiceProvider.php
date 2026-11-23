<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define gates for roles
        Gate::define('director', function (User $user) {
            return $user->role === 'director';
        });

        Gate::define('branch_manager', function (User $user) {
            return $user->role === 'branch_manager';
        });

        Gate::define('accountant', function (User $user) {
            return $user->role === 'accountant';
        });

        Gate::define('secretary', function (User $user) {
            return $user->role === 'secretary';
        });

        Gate::define('hr', function (User $user) {
            return $user->role === 'hr';
        });

        Gate::define('staff', function (User $user) {
            return $user->role === 'staff';
        });

        // Define permission gates
        Gate::define('manage_user', function (User $user) {
            return in_array($user->role, ['director', 'hr']);
        });

        Gate::define('manage_role', function (User $user) {
            return $user->role === 'director';
        });

        Gate::define('manage_permission', function (User $user) {
            return $user->role === 'director';
        });

        Gate::define('manage_branch', function (User $user) {
            return $user->role === 'director';
        });

        Gate::define('view_reports', function (User $user) {
            return in_array($user->role, ['director', 'branch_manager', 'accountant']);
        });

        Gate::define('manage_loans', function (User $user) {
            return in_array($user->role, ['director', 'branch_manager']);
        });

        Gate::define('manage_transactions', function (User $user) {
            return in_array($user->role, ['director', 'branch_manager', 'accountant', 'staff']);
        });

        Gate::define('manage_expenses', function (User $user) {
            return in_array($user->role, ['director', 'branch_manager', 'accountant']);
        });
    }
}