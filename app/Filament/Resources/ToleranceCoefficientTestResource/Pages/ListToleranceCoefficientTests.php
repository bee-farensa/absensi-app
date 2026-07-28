<?php

namespace App\Filament\Resources\ToleranceCoefficientTestResource\Pages;

use App\Filament\Resources\ToleranceCoefficientTestResource;
use Filament\Resources\Pages\ListRecords;

class ListToleranceCoefficientTests extends ListRecords
{
    protected static string $resource = ToleranceCoefficientTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // View only, no create action
        ];
    }
}
