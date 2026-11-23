<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $table = 'payroll';

    protected $fillable = [
        'employee_id', 'period_start', 'period_end',
        'base_salary', 'allowances', 'bonus', 'overtime_pay',
        'deductions', 'tax', 'net_pay',
        'payment_date', 'status', 'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
        'payment_date' => 'date',
        'base_salary'  => 'decimal:2',
        'allowances'   => 'decimal:2',
        'bonus'        => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'deductions'   => 'decimal:2',
        'tax'          => 'decimal:2',
        'net_pay'      => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}