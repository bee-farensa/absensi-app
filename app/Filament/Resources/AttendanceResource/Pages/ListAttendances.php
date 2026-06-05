<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use App\Exports\AttendanceExport; 
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat; 

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Group Export Actions dengan styling yang lebih baik
            // Actions\Action::make('exportPdf')
            //     ->label('Export PDF (Detail)')
            //     ->icon('heroicon-o-document-text')
            //     ->color('danger')
            //     ->tooltip('Download laporan detail absensi dalam format PDF')
            //     ->action(function () {
            //         $query = $this->getFilteredTableQuery();
            //         $attendances = $query->get();

            //         $companyFilter = $this->getTableFilterState('company_id') ?? [];
            //         $filteredCompanyId = $companyFilter['value'] ?? null;

            //         $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.attendance', [
            //             'attendances' => $attendances,
            //             'filteredCompanyId' => $filteredCompanyId,
            //         ]);
                    
            //         return response()->streamDownload(function () use ($pdf) {
            //             echo $pdf->stream();
            //         }, 'laporan-absen-detail-' . now()->format('Y-m-d') . '.pdf');
            //     }),
                
            Actions\Action::make('exportRekapPdf')
                ->label('Export PDF (Rekap)')
                ->icon('heroicon-o-document-chart-bar')
                ->color('warning')
                ->tooltip('Download laporan rekap absensi dalam format PDF')
                ->action(function () {
                    $query = $this->getFilteredTableQuery();
                    $attendances = $query->get();
                    
                    // Ambil state filter tanggal jika ada
                    $filterState = $this->getTableFilterState('created_at') ?? [];
                    $startDate = $filterState['dari_tanggal'] ?? null;
                    $endDate = $filterState['sampai_tanggal'] ?? null;

                    $rekap = [];
                    $userIds = [];
                    
                    foreach ($attendances as $absen) {
                        $userId = $absen->user_id;
                        $userIds[$userId] = $userId;
                        
                        if (!isset($rekap[$userId])) {
                            $rekap[$userId] = [
                                'user' => $absen->user,
                                'hadir' => 0,
                                'telat' => 0,
                                'sakit' => 0,
                                'izin' => 0,
                                'cuti' => 0,
                            ];
                        }
                        
                        $rekap[$userId]['hadir']++;
                        if ($absen->is_late) {
                            $rekap[$userId]['telat']++;
                        }
                    }
                    
                    // Ambil data cuti/izin/sakit untuk user yang bersangkutan dalam rentang tanggal
                    if (!empty($userIds)) {
                        $leaveQuery = \App\Models\LeaveRequest::whereIn('user_id', $userIds)
                            ->where('status', 'Approved');
                            
                        if ($startDate) {
                            $leaveQuery->where('start_date', '>=', $startDate);
                        }
                        if ($endDate) {
                            $leaveQuery->where('start_date', '<=', $endDate);
                        }
                        
                        $leaves = $leaveQuery->get();
                        
                        foreach ($leaves as $leave) {
                            $userId = $leave->user_id;
                            if (isset($rekap[$userId])) {
                                // Hitung durasi hari
                                $start = \Carbon\Carbon::parse($leave->start_date);
                                $end = \Carbon\Carbon::parse($leave->end_date);
                                $days = $start->diffInDays($end) + 1;
                                
                                $type = strtolower($leave->type);
                                if ($type == 'sakit') {
                                    $rekap[$userId]['sakit'] += $days;
                                } elseif ($type == 'izin') {
                                    $rekap[$userId]['izin'] += $days;
                                } elseif ($type == 'cuti') {
                                    $rekap[$userId]['cuti'] += $days;
                                }
                            }
                        }
                    }

                    $companyFilter = $this->getTableFilterState('company_id') ?? [];
                    $filteredCompanyId = $companyFilter['value'] ?? null;

                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.rekap_attendance', [
                        'rekap' => $rekap, 
                        'startDate' => $startDate, 
                        'endDate' => $endDate,
                        'filteredCompanyId' => $filteredCompanyId,
                    ]);
                    
                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->stream();
                    }, 'laporan-absen-rekap-' . now()->format('Y-m-d') . '.pdf');
                }),

            // Tambahkan tombol Export Excel
            Actions\Action::make('exportExcel')
                ->label('Export Excel (Detail)')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->tooltip('Download laporan detail absensi dalam format Excel')
                ->action(function () {
                    // Mengambil data yang sedang aktif (sudah difilter)
                    $attendances = $this->getFilteredTableQuery()->get();

                    // Ambil state filter untuk Kop Surat
                    $companyFilter = $this->getTableFilterState('company_id') ?? [];
                    $filteredCompanyId = $companyFilter['value'] ?? null;

                    $officeFilter = $this->getTableFilterState('office_id') ?? [];
                    $filteredOfficeId = $officeFilter['value'] ?? null;

                    $filterState = $this->getTableFilterState('created_at') ?? [];
                    $startDate = $filterState['dari_tanggal'] ?? null;
                    $endDate = $filterState['sampai_tanggal'] ?? null;

                    return Excel::download(
                        new AttendanceExport($attendances, $filteredCompanyId, $startDate, $endDate, $filteredOfficeId),
                        'laporan-absen-detail-' . now()->format('Y-m-d') . '.xlsx'
                    );
                }),
        ];
    }
}

