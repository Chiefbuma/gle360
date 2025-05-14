<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

      // Breadcrumbs can remain protected
      public function getBreadcrumbs(): array
      {
          return []; // Return empty array to remove breadcrumbs
      }
    
    // Must be public to match parent class
    public function getTitle(): string
    {
        return ''; // Return empty string to remove title
    }
}
