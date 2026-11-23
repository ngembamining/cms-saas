<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LunaRule extends Model
{
    use HasFactory;

    protected $table = 'luna_rules';

    protected $fillable = [
        'name',
        'description',
        'category',
        'condition',
        'action',
        'priority',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'condition' => 'array',
        'action' => 'array',
        'priority' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}