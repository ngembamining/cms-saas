<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'asset_code',
        'name',
        'category',
        'description',
        'purchase_price',
        'current_value',
        'purchase_date',
        'supplier',
        'serial_number',
        'location',
        'branch_id',
        'assigned_to',
        'status',
        'depreciation_rate',
        'last_maintenance',
        'next_maintenance',
        'warranty_until',
        'notes',
        'image',
        'created_by'
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'current_value' => 'decimal:2',
        'purchase_date' => 'date',
        'last_maintenance' => 'date',
        'next_maintenance' => 'date',
        'depreciation_rate' => 'decimal:2',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDepreciationAmountAttribute()
    {
        if ($this->depreciation_rate > 0) {
            return ($this->purchase_price * $this->depreciation_rate) / 100;
        }
        return 0;
    }

    public function getAgeInYearsAttribute()
    {
        return $this->purchase_date->diffInYears(now());
    }

    public function getStatusBadgeAttribute()
    {
        $statuses = [
            'active' => 'bg-green-100 text-green-700',
            'maintenance' => 'bg-yellow-100 text-yellow-700',
            'disposed' => 'bg-red-100 text-red-700',
        ];
        return $statuses[$this->status] ?? 'bg-gray-100 text-gray-700';
    }
}