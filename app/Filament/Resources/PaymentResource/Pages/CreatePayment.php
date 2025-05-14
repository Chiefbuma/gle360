<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected static bool $canCreateAnother = false;

    public function mount(): void
    {
        parent::mount();

        // Handle loan_id query parameter
        $loanId = request()->query('loan_id');
        if ($loanId) {
            $loan = \App\Models\Loan::find($loanId);
            if ($loan) {
                $this->form->fill([
                    'loan_id' => $loan->loan_id,
                    'borrower_id' => $loan->borrower_id,
                    'payment_date' => now()->toDateString(),
                ]);
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}