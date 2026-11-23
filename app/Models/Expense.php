<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $table = 'expenses';

    // Disable timestamps if your table doesn't have them
    public $timestamps = false;

    protected $fillable = [
        'branch_id',
        'amount',
        'category',
        'description',
        'receipt_path',
        'recorded_by',
        'created_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category', 'name');
    }
}