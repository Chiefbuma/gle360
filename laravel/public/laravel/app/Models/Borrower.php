<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Borrower extends Model
{
    use HasFactory;

    protected $primaryKey = 'borrower_id';
    protected $table = 'borrower';
    public $timestamps = true;

    protected $guarded = [];

    public function loan()
    {
        return $this->hasMany(Loan::class, 'borrower_id');
    }

    public function activeLoans()
    {
        return $this->loan()->whereIn('status', [1, 2]); // Confirmed or Released
    }

    public function payments()
    {
        return $this->hasManyThrough(
            LoanPayment::class,
            Loan::class,
            'borrower_id',
            'loan_id',
            'borrower_id',
            'loan_id'
        );
    }

    protected $fillable = [
        'name',
        'contact_no',
        'national_id',
    ];
}