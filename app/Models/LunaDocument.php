<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LunaDocument extends Model
{
    use HasFactory;

    protected $table = 'luna_documents';

    protected $fillable = [
        'title',
        'description',
        'category',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'content',
        'chunks',
        'embeddings',
        'status',
        'approved_by',
        'approved_at',
        'uploaded_by',
        'metadata',
    ];

    protected $casts = [
        'chunks' => 'array',
        'embeddings' => 'array',
        'metadata' => 'array',
        'file_size' => 'integer',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}