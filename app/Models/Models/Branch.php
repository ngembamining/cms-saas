<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $table = 'branches';

    protected $fillable = [
        'name',
        'location',
        'gold_available',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'gold_available' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the balances for this branch
     */
    public function balances()
    {
        return $this->hasMany(BranchBalance::class);
    }

    /**
     * Get the transactions for this branch
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the loans for this branch
     */
    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Get the expenses for this branch
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get the users assigned to this branch
     */
    public function users()
    {
        return $this->hasMany(User::class, 'assigned_branch');
    }

    /**
     * Get gold balance
     */
    public function getGoldBalanceAttribute()
    {
        return $this->balances()->where('balance_type', 'gold')->first()->amount ?? 0;
    }

    /**
     * Get cash balance
     */
    public function getCashBalanceAttribute()
    {
        return $this->balances()->where('balance_type', 'cash')->first()->amount ?? 0;
    }
}