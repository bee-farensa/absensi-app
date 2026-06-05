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
        $user = auth()->user();
        $today = Carbon::today()->toDateString();
        $isSuperAdmin = $user->hasRole('super_admin');

        // Buat base query untuk setiap stat — gunakan clone agar tidak saling mempengaruhi
        $baseAttendance = Attendance::where('date', $today);
        $baseLeave = LeaveRequest::where('status', 'Pending');
        $userQuery = User::query();

        if (!$isSuperAdmin) {
            $baseAttendance->whereHas('user', fn($q) => $q->where('company_id', $user->company_id));
            $baseLeave->whereHas('user', fn($q) => $q->where('company_id', $user->company_id));
            $userQuery->where('company_id', $user->company_id);
        }

        // Hitung masing-masing stat dengan clone query yang bersih
        $hadirCount     = (clone $baseAttendance)->count();
        $terlambatCount = (clone $baseAttendance)->where('is_late', true)->count();
        $izinCount      = (clone $baseLeave)->count();

        $stats = [];

        // HANYA SUPER ADMIN yang bisa lihat total perusahaan
        if ($isSuperAdmin) {
            $stats[] = Stat::make('Total Perusahaan', Company::count())
                ->description('Jumlah perusahaan terdaftar')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('info');
        } else {
            // ADMIN PT melihat total karyawannya sendiri
            $stats[] = Stat::make('Total Karyawan', $userQuery->count())
                ->description('Jumlah karyawan')
                ->descriptionIcon('heroicon-m-users')
                ->color('info');
        }

        $stats[] = Stat::make('Hadir Hari Ini', $hadirCount)
            ->description('Total karyawan yang sudah absen')
            ->descriptionIcon('heroicon-m-user-group')
            ->color('success');

        $stats[] = Stat::make('Terlambat', $terlambatCount)
            ->description('Karyawan yang terlambat')
            ->descriptionIcon('heroicon-m-clock')
            ->color('danger');

        $stats[] = Stat::make('Izin Pending', $izinCount)
            ->description('Menunggu persetujuan atasan')
            ->descriptionIcon('heroicon-m-envelope')
            ->color('warning');

        return $stats;
    }
}