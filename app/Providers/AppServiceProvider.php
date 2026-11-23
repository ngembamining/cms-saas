<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Map polymorphic types for messages and other morph relationships
        Relation::morphMap([
            'user' => 'App\Models\User',
            'supplier' => 'App\Models\Supplier',
            'customer' => 'App\Models\Customer',
            // Add other models as you need them:
            // 'branch' => 'App\Models\Branch',
            // 'loan' => 'App\Models\Loan',
            // 'transaction' => 'App\Models\Transaction',
        ]);
    }
}