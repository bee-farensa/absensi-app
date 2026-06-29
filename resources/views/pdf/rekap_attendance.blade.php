<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Rekap Absensi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }

        .kop-surat {
            width: 100%;
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .kop-surat table {
            width: 100%;
            border: none;
            border-collapse: collapse;
        }

        .kop-surat td {
            border: none;
            vertical-align: middle;
        }

        .logo {
            max-width: 100px;
            max-height: 100px;
        }

        .company-info {
            text-align: center;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }

        .company-address {
            font-size: 12px;
            margin: 5px 0 0 0;
        }

        .title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .subtitle {
            text-align: center;
            font-size: 12px;
            margin-bottom: 20px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        .data-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }

        .data-table td {
            vertical-align: middle;
        }

        .text-center {
            text-align: center;
        }

        .signature-table {
            width: 100%;
            margin-top: 50px;
            border: none;
            border-collapse: collapse;
        }

        .signature-table td {
            border: none;
            padding: 0;
        }
    </style>
</head>

<body>
    @php
    $user = auth()->user();
    $isSuperAdmin = $user->hasRole('super_admin');

    // Cek apakah ada filter PT yang dipilih
    $filteredCompanyId = $filteredCompanyId ?? null;
    $company = $user->company;

    if ($isSuperAdmin && $filteredCompanyId) {
    $company = \App\Models\Company::find($filteredCompanyId);
    $isGlobal = false; // Karena sudah difilter per PT
    } else {
    $isGlobal = $isSuperAdmin;
    }

    // Ambil data kantor pusat sebagai cadangan jika data di PT kosong
    $mainOffice = $company?->offices()->first();

    $companyName = $isGlobal ? 'LAPORAN REKAP ABSENSI GLOBAL' : ($company?->name ?? 'NAMA PERUSAHAAN');
    $companyAddress = $isGlobal ? 'Semua Data Perusahaan' : ($company?->address ?? $mainOffice?->address ?? 'Alamat
    belum diatur.');
    $companyPhone = $isGlobal ? '' : ($company?->phone_number ?? $mainOffice?->phone_number ?? '');

    $logoBase64 = null;
    if ($company && $company->logo) {
    try {
    $logoContents = \Illuminate\Support\Facades\Storage::disk('cloudinary')->get($company->logo);
    $type = pathinfo($company->logo, PATHINFO_EXTENSION) ?: 'png';
    $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($logoContents);
    } catch (\Exception $e) {
    \Log::warning('Gagal mengambil logo dari Cloudinary untuk PDF: ' . $e->getMessage());
    }
    }

    $periode = 'Semua Data';
    if (!empty($startDate) && !empty($endDate)) {
    $periode = \Carbon\Carbon::parse($startDate)->format('d/m/Y') . ' - ' .
    \Carbon\Carbon::parse($endDate)->format('d/m/Y');
    } elseif (!empty($startDate)) {
    $periode = 'Sejak ' . \Carbon\Carbon::parse($startDate)->format('d/m/Y');
    } elseif (!empty($endDate)) {
    $periode = 'Sampai ' . \Carbon\Carbon::parse($endDate)->format('d/m/Y');
    }
    @endphp

    <!-- Kop Surat -->
    <div class="kop-surat">
        <table>
            <tr>
                <td width="20%" class="text-center">
                    @if($logoBase64 && !$isGlobal)
                    <img src="{{ $logoBase64 }}" class="logo" alt="Logo">
                    @elseif(!$isGlobal)
                    <div
                        style="width: 80px; height: 80px; background: #eee; border-radius: 50%; display: inline-block; line-height: 80px; text-align: center; font-size: 10px; color: #999; border: 1px solid #ccc;">
                        No Logo</div>
                    @endif
                </td>
                <td width="60%" class="company-info">
                    <h1 class="company-name">{{ $companyName }}</h1>
                    <p class="company-address">{{ $companyAddress }}</p>
                    @if($companyPhone)
                    <p class="company-address">Telp: {{ $companyPhone }}</p>
                    @endif
                </td>
                <td width="20%"></td>
            </tr>
        </table>
    </div>

    <div class="title">REKAPITULASI DATA ABSENSI KARYAWAN</div>
    <div class="subtitle">
        Periode: {{ $periode }}<br>
        Dicetak pada: {{ now()->format('d F Y H:i') }}
    </div>

    <!-- Tabel Data Rekap -->
    <table class="data-table">
        <thead>
            <tr>
                <th width="5%" rowspan="2">No</th>
                <th width="20%" rowspan="2">Nama Karyawan</th>
                @if($isSuperAdmin)
                <th width="15%" rowspan="2">PT</th>
                @endif
                <th width="15%" rowspan="2">Departemen</th>
                <th colspan="5">Total Keterangan (Hari)</th>
            </tr>
            <tr>
                <th width="9%">Hadir</th>
                <th width="9%">Telat</th>
                <th width="9%">Sakit</th>
                <th width="9%">Izin</th>
                <th width="9%">Cuti</th>
            </tr>
        </thead>
        <tbody>
            @php $index = 1; @endphp
            @foreach($rekap as $data)
            <tr>
                <td class="text-center">{{ $index++ }}</td>
                <td>{{ $data['user']?->name ?? '-' }}</td>
                @if($isSuperAdmin)
                <td>{{ $data['user']?->company?->name ?? '-' }}</td>
                @endif
                <td>{{ $data['user']?->department?->name ?? '-' }}</td>

                <td class="text-center" style="font-weight: bold;">{{ $data['hadir'] }}</td>
                <td class="text-center" style="color: red;">{{ $data['telat'] > 0 ? $data['telat'] : '-' }}</td>
                <td class="text-center">{{ $data['sakit'] > 0 ? $data['sakit'] : '-' }}</td>
                <td class="text-center">{{ $data['izin'] > 0 ? $data['izin'] : '-' }}</td>
                <td class="text-center">{{ $data['cuti'] > 0 ? $data['cuti'] : '-' }}</td>
            </tr>
            @endforeach

            @if(empty($rekap))
            <tr>
                <td colspan="{{ $isSuperAdmin ? '9' : '8' }}" class="text-center" style="padding: 20px;">
                    Tidak ada data rekap absensi untuk periode/filter ini.
                </td>
            </tr>
            @endif
        </tbody>
    </table>

    <!-- Tanda Tangan -->
    <table class="signature-table">
        <tr>
            <td style="width: 70%;"></td>
            <td style="width: 30%; text-align: center;">
                <p>Mengetahui,</p>
                <br><br><br><br>
                <p><strong>{{ $user->name }}</strong><br>
                    <span style="font-size: 10px; color: #666;">({{ $user->hasRole('super_admin') ? 'Super Admin' :
                        'Admin PT' }})</span>
                </p>
            </td>
        </tr>
    </table>

</body>

</html>