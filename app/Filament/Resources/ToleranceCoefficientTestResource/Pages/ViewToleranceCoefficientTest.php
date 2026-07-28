<?php

namespace App\Filament\Resources\ToleranceCoefficientTestResource\Pages;

use App\Filament\Resources\ToleranceCoefficientTestResource;
use Filament\Resources\Pages\ViewRecord;

class ViewToleranceCoefficientTest extends ViewRecord
{
    protected static string $resource = ToleranceCoefficientTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // View only
        ];
    }
}
