<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanPayment extends Model
{
    use HasFactory;

    protected $table = 'loan_payments';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'loan_id',
        'payment_id',
        'payment_amount',
        'date_paid',
        'balance',
    ];

    protected $casts = [
        'date_paid' => 'datetime',
        'payment_amount' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id', 'loan_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'payment_id');
    }
}