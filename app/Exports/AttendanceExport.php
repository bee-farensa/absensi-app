<?php

namespace App\Exports;

use App\Models\Company;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

class AttendanceExport implements FromCollection, WithHeadings, WithMapping, WithCustomStartCell, WithStyles, WithEvents, WithDrawings
{
    protected $data;
    protected $companyId;
    protected $startDate;
    protected $endDate;
    protected $officeId; 

    public function __construct($data, $companyId = null, $startDate = null, $endDate = null, $officeId = null)
    {
        $this->data      = $data;
        $this->companyId = $companyId;
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
        $this->officeId  = $officeId; 
    }

    public function drawings()
    {
        $drawings = [];

        // Jika user adalah admin_pt, gunakan company_id dari user
        $user = auth()->user();
        $effectiveCompanyId = $this->companyId;
        
        if (!$effectiveCompanyId && $user && $user->hasRole('admin_pt')) {
            $effectiveCompanyId = $user->company_id;
        }

        if ($effectiveCompanyId) {
            $company = Company::find($effectiveCompanyId);
            if ($company && $company->logo && file_exists(storage_path('app/public/' . $company->logo))) {
                $drawing = new Drawing();
                $drawing->setName('Logo');
                $drawing->setDescription('Company Logo');
                $drawing->setPath(storage_path('app/public/' . $company->logo));
                $drawing->setHeight(85);
                $drawing->setCoordinates('A1');
                $drawing->setOffsetX(100);
                $drawing->setOffsetY(17);
                $drawings[] = $drawing;
            }
        }

        return $drawings;
    }

    public function collection()
    {
        return $this->data;
    }

    // Tabel data dimulai dari baris ke-6
    public function startCell(): string
    {
        return 'A6';
    }

    public function headings(): array
    {
        return [
            'Nama PT',
            'Site / Kantor',
            'Departemen',
            'Jabatan',
            'Nama Karyawan',
            'Tanggal',
            'Jam Masuk',
            'Jam Pulang',
        ];
    }

    public function map($attendance): array
    {
        return [
            $attendance->user->company->name    ?? '-',
            $attendance->office->name           ?? '-',
            $attendance->user->department->name ?? '-',
            $attendance->user->position->name   ?? '-',
            $attendance->user->name             ?? '-',
            \Carbon\Carbon::parse($attendance->date)->format('d-m-Y'),
            $attendance->time_in                ?? '-',
            $attendance->time_out               ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style header tabel di baris ke-6
            6 => [
                'font' => [
                    'bold'  => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F81BD'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $companyName    = "SEMUA PERUSAHAAN";
                $companyAddress = "";
                $companyPhone   = "";
                
                // Jika user adalah admin_pt, gunakan company_id dari user
                $user = auth()->user();
                $effectiveCompanyId = $this->companyId;
                
                if (!$effectiveCompanyId && $user && $user->hasRole('admin_pt')) {
                    $effectiveCompanyId = $user->company_id;
                }
                
                if ($this->officeId) {
                    // Jika filter kantor aktif, ambil nama kantor & alamatnya
                    $office = \App\Models\Office::find($this->officeId);
                    if ($office) {
                        $companyName = strtoupper($office->company->name . " - " . $office->name);
                        $companyAddress = $office->address ?? "";
                        $companyPhone = $office->phone_number ?? "";
                    }
                } elseif ($effectiveCompanyId) {
                    // Jika hanya filter perusahaan yang aktif (atau admin_pt)
                    $company        = Company::find($effectiveCompanyId);
                    $companyName    = $company ? strtoupper($company->name) : $companyName;
                    
                    if ($company) {
                        // Ambil alamat dari PT atau kantor pertama (Pusat) sebagai cadangan
                        $firstOffice = $company->offices()->first();
                        $companyAddress = $company->address ?? $firstOffice?->address ?? "";
                        $companyPhone = $company->phone_number ?? $firstOffice?->phone_number ?? "";
                    }
                }

                // Tinggi baris
                $sheet->getRowDimension(1)->setRowHeight(100);
                $sheet->getRowDimension(2)->setRowHeight(6);
                $sheet->getRowDimension(3)->setRowHeight(28);
                $sheet->getRowDimension(4)->setRowHeight(18);
                $sheet->getRowDimension(5)->setRowHeight(6);

                // --- Baris 1: RichText nama PT + subtitle + alamat dalam satu cell C1 ---
                $richText = new RichText();

                // 1. Nama PT
                $boldRun = $richText->createTextRun($companyName);
                $boldRun->getFont()->setBold(true)->setSize(16)->setName('Calibri');

                $richText->createText("\n");

                // 2. Subtitle
                $subtitleRun = $richText->createTextRun('Laporan Kehadiran Karyawan Terintegrasi');
                $subtitleRun->getFont()
                    ->setBold(false)
                    ->setItalic(true)
                    ->setSize(9)
                    ->setName('Calibri')
                    ->getColor()->setRGB('595959');

                if ($companyAddress) {
                    $richText->createText("\n");
                    // 3. Alamat
                    $addressText = $companyAddress;
                    if ($companyPhone) {
                        $addressText .= " | Telp: " . $companyPhone;
                    }
                    
                    $addressRun = $richText->createTextRun($addressText);
                    $addressRun->getFont()
                        ->setBold(false)
                        ->setSize(9)
                        ->setName('Calibri')
                        ->getColor()->setRGB('7F7F7F');
                }

                $sheet->getCell('C1')->setValue($richText);
                $sheet->mergeCells('C1:H1');
                $sheet->getStyle('C1')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                // Lebar kolom A (area logo) — fixed agar logo tidak terpotong
                $sheet->getColumnDimension('A')->setWidth(14);

                // --- Baris 2: Garis pemisah bawah ---
                $sheet->getStyle('A2:H2')->applyFromArray([
                    'borders' => [
                        'bottom' => [
                            'borderStyle' => Border::BORDER_DOUBLE,
                            'color'       => ['rgb' => '4F81BD'],
                        ],
                    ],
                ]);

                // --- Baris 3: Judul laporan ---
                $sheet->setCellValue('A3', 'LAPORAN DETAIL ABSENSI');
                $sheet->mergeCells('A3:H3');
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 13,
                        'name' => 'Calibri',
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // --- Baris 4: Periode ---
                $startLabel = $this->startDate
                    ? \Carbon\Carbon::parse($this->startDate)->format('d-m-Y')
                    : 'Semua Waktu';
                $endLabel = $this->endDate
                    ? \Carbon\Carbon::parse($this->endDate)->format('d-m-Y')
                    : \Carbon\Carbon::now()->format('d-m-Y');

                $sheet->setCellValue('A4', 'Periode: ' . $startLabel . ' s/d ' . $endLabel);
                $sheet->mergeCells('A4:H4');
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => [
                        'size'  => 10,
                        'name'  => 'Calibri',
                        'color' => ['rgb' => '595959'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // --- Auto size kolom B–H (A sudah fixed) ---
                foreach (range('B', 'H') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // --- Border tipis seluruh data tabel (mulai baris 6) ---
                $highestRow = $sheet->getHighestRow();
                if ($highestRow >= 6) {
                    $sheet->getStyle('A6:H' . $highestRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                            ],
                        ],
                    ]);
                }
            },
        ];
    }
}