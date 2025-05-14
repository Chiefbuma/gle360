<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanType extends Model
{
    use HasFactory;

    protected $primaryKey = 'ltype_id';
    protected $table = 'loan_type';
    public $timestamps = false;

    protected $fillable = [
        'ltype_name',
       
    ];

    public function loan()
    {
        return $this->hasMany(Loan::class, 'ltype_id', 'ltype_id');
    }
}