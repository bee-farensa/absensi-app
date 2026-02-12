<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class AttendanceStats extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today()->toDateString();

        return [
            Stat::make('Total Perusahaan', Company::count())
                ->description('Jumlah perusahaan terdaftar')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('info'),

            Stat::make('Hadir Hari Ini', Attendance::where('date', $today)->count())
                ->description('Total karyawan yang sudah absen')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Terlambat', Attendance::where('date', $today)->where('is_late', true)->count())
                ->description('Karyawan yang masuk lewat jam kantor')
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger'),

            Stat::make('Izin Pending', LeaveRequest::where('status', 'Pending')->count())
                ->description('Perlu persetujuan admin')
                ->descriptionIcon('heroicon-m-envelope')
                ->color('warning'),
        ];
    }
}