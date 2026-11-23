<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $table = 'exchange_rates';

    protected $fillable = [
        'gold_rate',
        'effective_date',
        'created_at'
    ];

    protected $casts = [
        'gold_rate' => 'decimal:2',
        'effective_date' => 'date',
    ];

    public $timestamps = false;
}