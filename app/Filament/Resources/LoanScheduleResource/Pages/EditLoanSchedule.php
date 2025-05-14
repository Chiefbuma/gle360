<?php

namespace App\Filament\Resources\LoanScheduleResource\Pages;

use App\Filament\Resources\LoanScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLoanSchedule extends EditRecord
{
    protected static string $resource = LoanScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
