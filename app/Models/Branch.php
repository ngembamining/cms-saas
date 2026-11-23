<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $table = 'branches';

    // The branches table DOES have created_at and updated_at
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'name',
        'location',
        'gold_available',
    ];

    protected $casts = [
        'gold_available' => 'decimal:2',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    // =============================
    // Relationships
    // =============================

    public function balances()
    {
        return $this->hasMany(BranchBalance::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'assigned_branch');
    }

    public function suppliers()
    {
        return $this->hasMany(Supplier::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    // =============================
    // Balance accessors
    // =============================

    /**
     * Cash balance — reads from branch_balances (source of truth for cash).
     * Uses the loaded `balances` relation if present, else queries.
     */
    public function getCashBalanceAttribute()
    {
        if ($this->relationLoaded('balances')) {
            $row = $this->balances->firstWhere('balance_type', 'cash');
            return $row->amount ?? 0;
        }

        return (float) ($this->balances()
            ->where('balance_type', 'cash')
            ->value('amount') ?? 0);
    }

    /**
     * Gold balance — reads from the `gold_available` column on the branches
     * table (source of truth, matches the PHP purchase page's write path).
     *
     * NOTE: Do NOT read from branch_balances here — that table's gold row
     * is currently out of sync with branches.gold_available.
     */
    public function getGoldBalanceAttribute()
    {
        return (float) ($this->gold_available ?? 0);
    }

    /**
     * Optional: read the (legacy) gold row from branch_balances, for
     * diagnostics / reconciliation only.
     */
    public function getLegacyGoldBalanceAttribute()
    {
        if ($this->relationLoaded('balances')) {
            $row = $this->balances->firstWhere('balance_type', 'gold');
            return (float) ($row->amount ?? 0);
        }

        return (float) ($this->balances()
            ->where('balance_type', 'gold')
            ->value('amount') ?? 0);
    }

    // =============================
    // Scopes
    // =============================

    public function scopeForUser($query, $user)
    {
        if ($user && $user->role !== 'director' && $user->assigned_branch) {
            return $query->where('id', $user->assigned_branch);
        }
        return $query;
    }
}