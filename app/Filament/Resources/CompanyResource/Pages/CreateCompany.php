<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCompany extends CreateRecord
{
    protected static string $resource = CompanyResource::class;

    protected function afterCreate(): void
    {
        $company = $this->record;

        $company->offices()->create([
            'name'           => 'Kantor Pusat',
            'is_branch'      => false,
            'address'        => $this->data['office_address'],
            'phone_number'   => $this->data['office_phone_number'],
            'latitude'       => $this->data['office_latitude'],
            'longitude'      => $this->data['office_longitude'],
            'radius'         => $this->data['office_radius'],
            'check_in_time'  => $this->data['office_check_in_time'],
            'check_out_time' => $this->data['office_check_out_time'],
        ]);
    }
}
