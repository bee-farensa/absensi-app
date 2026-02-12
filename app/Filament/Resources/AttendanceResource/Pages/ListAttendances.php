<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use App\Exports\AttendanceExport; // Import file export tadi
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            
            // Tambahkan tombol Export Excel
            Actions\Action::make('exportExcel')
                ->label('Export ke Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    // Mengambil query yang sedang aktif (termasuk filter di layar)
                    $query = $this->getFilteredTableQuery(); 
                    
                    return Excel::download(
                        new AttendanceExport($query), 
                        'laporan-absen-' . now()->format('Y-m-d') . '.xlsx'
                    );
                }),
        ];
    }
}

