<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoldReconciliation extends Model
{
    // Use the correct table name from your database
    protected $table = 'branch_gold_reconciliation';

    protected $fillable = [
        'branch_id',
        'reconciliation_date',
        'opening_weight',
        'closing_weight',
        'notes',
        'reconciled_by',
        'verified_by',
        'verified_at'
    ];

    protected $casts = [
        'opening_weight' => 'decimal:2',
        'closing_weight' => 'decimal:2',
        'reconciliation_date' => 'date',
        'verified_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function reconciledBy()
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}