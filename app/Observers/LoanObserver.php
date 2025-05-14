<?php

namespace App\Observers;

use App\Models\Loan;
use Filament\Notifications\Notification;

class LoanObserver
{
    public function created(Loan $loan): void
    {
        // No special handling needed
    }

    public function updated(Loan $loan): void
    {
        $loan->updateStatus();
    }

    public function deleted(Loan $loan): void
    {
        // No special handling needed
    }

    public function restored(Loan $loan): void
    {
        // No special handling needed
    }

    public function forceDeleted(Loan $loan): void
    {
        // No special handling needed
    }

    public function deleting(Loan $loan)
    {
        if ($loan->hasPayments()) {
            Notification::make()
                ->title('Cannot Delete Loan')
                ->body('This loan has payments associated with it. Please delete the payments first.')
                ->danger()
                ->send();
            
            return false;
        }
    }
}