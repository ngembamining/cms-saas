<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchBalance extends Model
{
    use HasFactory;

    protected $table = 'branch_balances';

    // DISABLE TIMESTAMPS - table doesn't have updated_at column
    public $timestamps = false;

    protected $fillable = [
        'branch_id',
        'balance_type',
        'currency',
        'amount',
        'minimum_balance',
        'last_updated'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'minimum_balance' => 'decimal:2',
        'last_updated' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}