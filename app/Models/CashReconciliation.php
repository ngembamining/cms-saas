<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashReconciliation extends Model
{
    protected $table = 'cash_reconciliation';

    protected $fillable = [
        'branch_id',
        'reconciliation_date',
        'opening_balance',
        'closing_balance',
        'verified_amount',
        'system_balance',
        'cash_shortage',
        'cash_overage',
        'variance',
        'notes',
        'verification_notes',
        'status',
        'reconciled_by',
        'verified_by',
        'verified_at'
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'verified_amount' => 'decimal:2',
        'system_balance' => 'decimal:2',
        'cash_shortage' => 'decimal:2',
        'cash_overage' => 'decimal:2',
        'variance' => 'decimal:2',
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

    public function getStatusBadgeAttribute()
    {
        $statuses = [
            'pending' => 'bg-yellow-100 text-yellow-700',
            'verified' => 'bg-green-100 text-green-700',
            'rejected' => 'bg-red-100 text-red-700',
        ];
        return $statuses[$this->status] ?? 'bg-gray-100 text-gray-700';
    }
}