<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanPlan extends Model
{
    use HasFactory;

    protected $primaryKey = 'lplan_id';
    protected $table = 'loan_plan';
    public $timestamps = false;

    protected $fillable = [
        'lplan_interest',
        'lplan_penalty',
    ];

    public function loan()
    {
        return $this->hasMany(Loan::class, 'lplan_id', 'lplan_id');
    }
}