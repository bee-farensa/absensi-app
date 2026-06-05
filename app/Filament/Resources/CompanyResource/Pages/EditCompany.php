<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $company = $this->record;

        // Cari kantor pusat (is_branch = false) dan update nomor telp-nya
        $company->offices()
            ->where('is_branch', false)
            ->update([
                'phone_number' => $company->phone_number,
            ]);
    }
}
