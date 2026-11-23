<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrAnnouncement extends Model
{
    protected $table = 'hr_announcements';

    protected $fillable = [
        'title', 'content', 'type', 'priority',
        'start_date', 'end_date', 'is_published', 'created_by',
    ];

    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'is_published' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}