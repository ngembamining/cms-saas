<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    protected $fillable = [
        'transaction_code',
        'branch_id',
        'transaction_type',
        'amount',
        'customer_id',
        'supplier_id',
        'loan_id',
        'description',
        'reference_no',
        'payment_method',
        'payment_reference',
        'recorded_by',
        'verified_by',
        'status',
        'reversal_reason',
        'related_transaction',
        'comments',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* ------------------------------------------------------------------ */
    /* Relationships                                                       */
    /* ------------------------------------------------------------------ */

    /**
     * Branch where the transaction occurred.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * User who recorded the transaction.
     * Alias: recordedBy() — matches the DB column name for clarity.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * User who verified or reversed the transaction.
     * Alias: verifiedBy() — matches the DB column name for clarity.
     */
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Customer (for gold sales).
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Supplier (for gold purchases).
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Associated loan (for loan disbursements or repayments).
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id');
    }

    /**
     * Line items (for gold purchases/sales with weight & purity rows).
     */
    public function items()
    {
        return $this->hasMany(TransactionItem::class, 'transaction_id');
    }

    /**
     * Linked transaction — set on reversals to point to the original row.
     */
    public function relatedTransaction()
    {
        return $this->belongsTo(Transaction::class, 'related_transaction');
    }

    /**
     * Transactions that point back to this one (typically reversal entries).
     */
    public function reversals()
    {
        return $this->hasMany(Transaction::class, 'related_transaction');
    }

    /* ------------------------------------------------------------------ */
    /* Scopes                                                              */
    /* ------------------------------------------------------------------ */

    /**
     * Money/gold coming IN (sales, deposits, loan repayments, fund transfers).
     */
    public function scopeInflow($query)
    {
        return $query->whereIn('transaction_type', [
            'gold_sale',
            'cash_deposit',
            'loan_repayment',
            'fund_transfer',
        ]);
    }

    /**
     * Money/gold going OUT (purchases, withdrawals, expenses, disbursements).
     */
    public function scopeOutflow($query)
    {
        return $query->whereNotIn('transaction_type', [
            'gold_sale',
            'cash_deposit',
            'loan_repayment',
            'fund_transfer',
        ]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeReversed($query)
    {
        return $query->where('status', 'reversed');
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    /* ------------------------------------------------------------------ */
    /* Accessors / Helpers                                                 */
    /* ------------------------------------------------------------------ */

    /**
     * Is this transaction an inflow (money in)?
     */
    public function getIsInflowAttribute(): bool
    {
        return in_array($this->transaction_type, [
            'gold_sale',
            'cash_deposit',
            'loan_repayment',
            'fund_transfer',
        ], true);
    }

    /**
     * Is this transaction reversible?
     */
    public function getIsReversibleAttribute(): bool
    {
        return $this->status !== 'reversed'
            && in_array($this->status, ['completed', 'pending'], true);
    }

    /**
     * Human-readable label for the transaction type.
     */
    public function getTypeLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->transaction_type ?? ''));
    }

    /**
     * Total weight from line items (in grams), if any.
     */
    public function getTotalWeightAttribute(): float
    {
        return (float) $this->items->sum('weight');
    }
}