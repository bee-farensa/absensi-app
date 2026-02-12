<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting; // Tambahkan ini
use PhpOffice\PhpSpreadsheet\Style\NumberFormat; // Tambahkan ini

class AttendanceExport implements FromQuery, WithHeadings, WithMapping, WithColumnFormatting
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return ['Nama Karyawan', 'NIK', 'Perusahaan', 'Waktu Absen', 'Status'];
    }

    // Memastikan NIK dikirim sebagai string dengan kutipan tunggal di depannya (trik Excel)
    public function map($attendance): array
    {
        return [
            $attendance->user->name,
            " " . $attendance->user->nik, // Tambahkan spasi di depan agar dianggap teks
            $attendance->user->company->name,
            $attendance->created_at->format('d-m-Y H:i'),
            $attendance->is_late ? 'Terlambat' : 'Tepat Waktu',
        ];
    }

    // Memaksa kolom B (NIK) menjadi format TEXT murni
    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT,
        ];
    }
}
