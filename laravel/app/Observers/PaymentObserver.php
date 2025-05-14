<?php

namespace App\Observers;

use App\Models\Payment;

class PaymentObserver
{
    public function created(Payment $payment)
    {
        $loan = $payment->loan;
        $loan->updateStatus();
    }

    public function updated(Payment $payment)
    {
        $loan = $payment->loan;
        $loan->updateStatus();
    }

    public function deleted(Payment $payment)
    {
        $loan = $payment->loan;
        $loan->updateStatus();
    }
}