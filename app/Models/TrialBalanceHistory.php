<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrialBalanceHistory extends Model
{
    protected $fillable = [
        'trial_balance_id',
        'action',
        'changes',
        'user_id',
        'created_at'
    ];

    protected $casts = [
        'changes' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}