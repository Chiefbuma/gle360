<?php

namespace App\Filament\Resources\LoanScheduleResource\Pages;

use App\Filament\Resources\LoanScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLoanSchedules extends ListRecords
{
    protected static string $resource = LoanScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
