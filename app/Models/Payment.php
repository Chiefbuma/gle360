<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payment';
    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'loan_id',
        'borrower_id',
        'payment_amount',
        'payment_date',
    ];

    protected $casts = [
        'payment_date' => 'date',
    ];

    protected static function booted()
    {
        static::creating(function ($payment) {
            $loan = $payment->loan;
            $remaining = $loan->remainingBalance();
            
            if ($payment->payment_amount > $remaining) {
                Notification::make()
                    ->title('Payment Error')
                    ->body("Payment amount exceeds remaining balance. Maximum allowed: Ksh " . number_format($remaining, 0))
                    ->danger()
                    ->send();
                return false;
            }
        });

        static::created(function ($payment) {
            $payment->loan->updateStatus();
        });
    }

    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id');
    }

    public function borrower()
    {
        return $this->belongsTo(Borrower::class, 'borrower_id');
    }
}