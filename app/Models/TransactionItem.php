<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionItem extends Model
{
    use HasFactory;

    protected $table = 'transaction_items';

    // DISABLE TIMESTAMPS - table doesn't have updated_at column
    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'weight',
        'purity',
        'agreed_price',
        'actual_sale_weight'
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'purity' => 'decimal:2',
        'agreed_price' => 'decimal:2',
        'actual_sale_weight' => 'decimal:2',
    ];

    /**
     * Get the transaction that owns the item.
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}