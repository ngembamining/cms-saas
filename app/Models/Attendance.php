<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $table = 'attendance';

    protected $fillable = [
        'employee_id', 'date', 'check_in', 'check_out',
        'status', 'hours_worked', 'overtime_hours', 'notes',
    ];

    protected $casts = [
        'date'           => 'date',
        'hours_worked'   => 'decimal:2',
        'overtime_hours' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    // Accessor: get status color class
    public function getStatusBadgeAttribute()
    {
        return match ($this->status) {
            'present'  => 'bg-green-100 text-green-700',
            'late'     => 'bg-amber-100 text-amber-700',
            'absent'   => 'bg-red-100 text-red-700',
            'half_day' => 'bg-blue-100 text-blue-700',
            'holiday'  => 'bg-purple-100 text-purple-700',
            'leave'    => 'bg-gray-100 text-gray-700',
            default    => 'bg-gray-100 text-gray-600',
        };
    }
}