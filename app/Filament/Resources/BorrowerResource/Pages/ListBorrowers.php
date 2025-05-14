<?php

namespace App\Filament\Resources\BorrowerResource\Pages;

use App\Filament\Resources\BorrowerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBorrowers extends ListRecords
{
    protected static string $resource = BorrowerResource::class;

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
