<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customers';

    // DISABLE TIMESTAMPS
    public $timestamps = false;

    protected $fillable = [
        'name',
        'contact',
        'address',
        'branch_id',
        'email',
        'avatar',
        'outstanding_balance',
        'created_at'
    ];

    protected $casts = [
        'outstanding_balance' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the customer's avatar with fallback
     */
    public function getAvatarAttribute($value)
    {
        if ($value) {
            return asset('storage/' . $value);
        }
        return asset('img/default-avatar.png');
    }

    /**
     * Get the customer's avatar URL
     */
    public function getAvatarUrlAttribute()
    {
        if ($this->attributes['avatar'] ?? false) {
            return asset('storage/' . $this->attributes['avatar']);
        }
        return asset('img/default-avatar.png');
    }
}