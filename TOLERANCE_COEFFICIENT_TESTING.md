# Testing Tolerance Coefficient via Filament UI

## Deskripsi
Tolerance Coefficient adalah parameter yang digunakan untuk memperluas radius efektif absen berdasarkan akurasi GPS. 

Formula yang digunakan di backend:
```
Effective Radius = Office Radius + (Tolerance Coefficient × GPS Accuracy)
```

## Nilai Testing
- **0.5** : Paling Ketat (hanya GPS accuracy 50% dikali dalam extended radius)
- **1.0** : Normal/Default (GPS accuracy 100% dikali dalam extended radius)
- **1.5** : Toleransi Sedang (GPS accuracy 150% dikali dalam extended radius)
- **2.0** : Paling Lebar (GPS accuracy 200% dikali dalam extended radius)

## Cara Testing

### 1. Login ke Filament Admin
- Akses: `http://localhost/admin` (atau domain production)
- Login sebagai: `super_admin` atau `admin_pt`

### 2. Navigasi ke Manajemen Kantor
- Di menu sidebar, klik **"Manajemen Kantor"**
- Pilih kantor yang ingin ditest (misal: "Kantor Pusat")

### 3. Edit Tolerance Coefficient

#### Test Phase 1: Paling Ketat (k=0.5)
1. Klik tombol **Edit** pada kantor yang dipilih
2. Scroll ke field **"Tolerance Coefficient"**
3. Ubah nilai menjadi `0.5`
4. Klik **"Save"**
5. Karyawan melakukan absensi normal
6. Catat hasil (acceptance rate, jarak yang ditolak)

#### Test Phase 2: Normal (k=1.0) - Default
1. Klik tombol **Edit** pada kantor yang sama
2. Ubah nilai tolerance coefficient menjadi `1.0`
3. Klik **"Save"**
4. Karyawan melakukan absensi normal
5. Catat hasil untuk perbandingan

#### Test Phase 3: Toleransi Sedang (k=1.5)
1. Klik tombol **Edit** pada kantor yang sama
2. Ubah nilai tolerance coefficient menjadi `1.5`
3. Klik **"Save"**
4. Karyawan melakukan absensi normal
5. Catat hasil untuk perbandingan

#### Test Phase 4: Paling Lebar (k=2.0)
1. Klik tombol **Edit** pada kantor yang sama
2. Ubah nilai tolerance coefficient menjadi `2.0`
3. Klik **"Save"**
4. Karyawan melakukan absensi normal
5. Catat hasil akhir

### 4. Production Setting
Setelah testing selesai, set kembali ke nilai production:
```
Tolerance Coefficient = 1.0 (Default)
```

## Reference Data untuk Testing

### Asumsi Baseline
- **Office Radius**: 15 meter
- **GPS Accuracy Terbaca**: Bervariasi (5m - 20m)
- **Testing Values**: k = 0.5, 1.0, 1.5, 2.0

### Tabel Kalkulasi Effective Radius

#### Skenario 1: Tolerance Coefficient = 0.5 (Paling Ketat)
| GPS Accuracy (m) | Jarak Sistem (m) | Effective Radius (m) | Status | Tanda |
|---|---|---|---|---|
| 5 | 17 | 15 + (0.5 × 5) = 17.5 | Diterima | ✓ |
| 5 | 18 | 15 + (0.5 × 5) = 17.5 | Ditolak | ✗ |
| 10 | 20 | 15 + (0.5 × 10) = 20 | Diterima | ✓ |
| 10 | 21 | 15 + (0.5 × 10) = 20 | Ditolak | ✗ |
| 15 | 22 | 15 + (0.5 × 15) = 22.5 | Diterima | ✓ |
| 20 | 25 | 15 + (0.5 × 20) = 25 | Diterima | ✓ |

#### Skenario 2: Tolerance Coefficient = 1.0 (Normal/Default)
| GPS Accuracy (m) | Jarak Sistem (m) | Effective Radius (m) | Status | Tanda |
|---|---|---|---|---|
| 5 | 20 | 15 + (1.0 × 5) = 20 | Diterima | ✓ |
| 5 | 21 | 15 + (1.0 × 5) = 20 | Ditolak | ✗ |
| 10 | 25 | 15 + (1.0 × 10) = 25 | Diterima | ✓ |
| 10 | 26 | 15 + (1.0 × 10) = 25 | Ditolak | ✗ |
| 15 | 30 | 15 + (1.0 × 15) = 30 | Diterima | ✓ |
| 20 | 35 | 15 + (1.0 × 20) = 35 | Diterima | ✓ |

#### Skenario 3: Tolerance Coefficient = 1.5 (Toleransi Sedang)
| GPS Accuracy (m) | Jarak Sistem (m) | Effective Radius (m) | Status | Tanda |
|---|---|---|---|---|
| 5 | 22 | 15 + (1.5 × 5) = 22.5 | Diterima | ✓ |
| 5 | 23 | 15 + (1.5 × 5) = 22.5 | Ditolak | ✗ |
| 10 | 30 | 15 + (1.5 × 10) = 30 | Diterima | ✓ |
| 10 | 31 | 15 + (1.5 × 10) = 30 | Ditolak | ✗ |
| 15 | 37 | 15 + (1.5 × 15) = 37.5 | Diterima | ✓ |
| 20 | 45 | 15 + (1.5 × 20) = 45 | Diterima | ✓ |

#### Skenario 4: Tolerance Coefficient = 2.0 (Paling Lebar)
| GPS Accuracy (m) | Jarak Sistem (m) | Effective Radius (m) | Status | Tanda |
|---|---|---|---|---|
| 5 | 25 | 15 + (2.0 × 5) = 25 | Diterima | ✓ |
| 5 | 26 | 15 + (2.0 × 5) = 25 | Ditolak | ✗ |
| 10 | 35 | 15 + (2.0 × 10) = 35 | Diterima | ✓ |
| 10 | 36 | 15 + (2.0 × 10) = 35 | Ditolak | ✗ |
| 15 | 45 | 15 + (2.0 × 15) = 45 | Diterima | ✓ |
| 20 | 55 | 15 + (2.0 × 20) = 55 | Diterima | ✓ |

### Analisis Perbandingan

| Tolerance Coeff | GPS Acc 5m | GPS Acc 10m | GPS Acc 15m | GPS Acc 20m | Karakteristik |
|---|---|---|---|---|---|
| **0.5** | 17.5m | 20m | 22.5m | 25m | Sangat ketat, high rejection |
| **1.0** | 20m | 25m | 30m | 35m | Balanced, standard production |
| **1.5** | 22.5m | 30m | 37.5m | 45m | Lebar, medium tolerance |
| **2.0** | 25m | 35m | 45m | 55m | Paling lebar, low rejection |

### Interpretasi Hasil
- **Coefficient 0.5**: Hanya 50% dari GPS accuracy yang ditambahkan ke effective radius
  - Cocok untuk area dengan GPS signal stabil dan user harus dekat kantor
  - Risiko: Banyak rejection pada kondisi GPS accuracy buruk
  
- **Coefficient 1.0**: 100% dari GPS accuracy ditambahkan ke effective radius (DEFAULT)
  - Balanced approach, cocok untuk production
  - Recommended untuk mayoritas use case
  
- **Coefficient 1.5**: 150% dari GPS accuracy ditambahkan ke effective radius
  - Cocok untuk area dengan GPS signal buruk atau outdoor yang luas
  - Lebih lenient, acceptance rate lebih tinggi
  
- **Coefficient 2.0**: 200% dari GPS accuracy ditambahkan ke effective radius
  - Paling lenient, cocok untuk testing GPS signal ekstrem
  - Risiko: User bisa absensi dari jarak jauh

## Catatan Teknis

### Bagaimana Backend Menggunakan Nilai Ini
- Lokasi: `app/Http/Controllers/Api/AttendanceController.php` (Line 117)
- Saat karyawan melakukan absensi:
  1. Backend menghitung jarak dari lokasi karyawan ke kantor terdekat
  2. Backend membaca nilai `tolerance_coefficient` dari database
  3. Extended radius = radius + (tolerance_coefficient × GPS accuracy)
  4. Jika jarak ≤ extended radius → Absensi diterima ✓
  5. Jika jarak > extended radius → Absensi ditolak ✗

### Field yang Ditampilkan di Filament
- **Input Form**: Numeric input dengan default 1.0, min value 0.1, step 0.1
- **Table View**: Badge column dengan icon slider, warna info
- **Helper Text**: Menjelaskan pengaruh masing-masing nilai

## Troubleshooting

### Perubahan tidak berpengaruh?
- Pastikan database sudah di-sync (run migration jika belum)
- Clear cache Laravel: `php artisan cache:clear`
- Reload Filament page

### GPS Accuracy di database tidak muncul?
- Verifikasi di table `attendances`, kolom `accuracy_in` dan `accuracy_out`
- Pastikan Flutter app mengirimkan field `accuracy` saat absensi

## Workflow Rekomendasi
1. **Pre-Testing**: Pilih 1-2 karyawan untuk test
2. **Phase 1**: Run dengan k=0.5 → catat hasil (rejection rate, jarak limit)
3. **Phase 2**: Run dengan k=1.0 → catat hasil untuk perbandingan
4. **Phase 3**: Run dengan k=1.5 → catat hasil untuk perbandingan
5. **Phase 4**: Run dengan k=2.0 → catat hasil akhir
6. **Analisis**: Bandingkan semua hasil, tentukan k optimal untuk lokasi Anda
7. **Production**: Set ke nilai optimal yang dipilih

## View Data Testing
Data testing dapat dilihat di:
- **pgAdmin**: Database → `absensi` → Tables → `tolerance_coefficient_tests`
- **Query untuk analisis**:
  ```sql
  SELECT 
    tolerance_coefficient,
    result,
    COUNT(*) as total,
    ROUND(COUNT(CASE WHEN result = 'accepted' THEN 1 END) * 100.0 / COUNT(*), 2) as acceptance_rate
  FROM tolerance_coefficient_tests
  WHERE test_date >= CURRENT_DATE - INTERVAL '7 days'
  GROUP BY tolerance_coefficient, result
  ORDER BY tolerance_coefficient;
  ```
