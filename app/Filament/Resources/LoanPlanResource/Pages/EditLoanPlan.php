<?php

namespace App\Filament\Resources\LoanPlanResource\Pages;

use App\Filament\Resources\LoanPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLoanPlan extends EditRecord
{
    protected static string $resource = LoanPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
