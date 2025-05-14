<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanSchedule extends Model
{
    use HasFactory;

    protected $primaryKey = 'loan_sched_id';
    protected $table = 'loan_schedule';
    public $timestamps = false;

    protected $fillable = [
        'loan_id',
        'due_date',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id', 'loan_id');
    }
}