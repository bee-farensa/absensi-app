<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Office;
use App\Models\ToleranceCoefficientTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $month = (int) $request->query('month', Carbon::now()->month);
        $year = (int) $request->query('year', Carbon::now()->year);

        if ($month < 1 || $month > 12) {
            $month = Carbon::now()->month;
        }

        $currentYear = Carbon::now()->year;
        if ($year < $currentYear - 5 || $year > $currentYear + 5) {
            $year = $currentYear;
        }

        $attendances = Attendance::where('user_id', $user->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->with(['office', 'company'])
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id'            => $item->id,
                    'date'          => $item->date,
                    'office_name' => $item->office?->name ?? 'Kantor',
                    'time_in'       => $item->time_in,
                    'time_out'      => $item->time_out,
                    'is_late'       => (bool) $item->is_late,
                    'face_verified' => (bool) $item->face_verified,
                    'status'        => $item->time_out ? 'Lengkap' : 'Belum Checkout',
                    'pic_in'        => $item->pic_in ? Storage::disk('cloudinary')->url($item->pic_in) : null,
                    'pic_out'       => $item->pic_out ? Storage::disk('cloudinary')->url($item->pic_out) : null,
                    'lat_in'        => $item->lat_in,
                    'long_in'       => $item->long_in,
                    'lat_out'       => $item->lat_out,
                    'long_out'      => $item->long_out,
                ];
            });

        $today = Carbon::today()->toDateString();
        $todayRecord = Attendance::where('user_id', $user->id)->where('date', $today)->first();

        return response()->json([
            'success'      => true,
            'month'        => $month,
            'year'         => $year,
            'today_status' => $todayRecord
                ? ($todayRecord->time_out ? 'checked_out' : 'checked_in')
                : 'not_yet',
            'total' => $attendances->count(),
            'data'  => $attendances,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'latitude'      => 'required|numeric|between:-90,90',
            'longitude'     => 'required|numeric|between:-180,180',
            'accuracy'      => 'nullable|numeric|min:0',
            'image'         => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'face_verified' => 'sometimes|boolean',
        ]);

        $user  = $request->user();
        $today = Carbon::today()->toDateString();
        $time  = Carbon::now()->toTimeString();

        $uploadedImagePath = null;

        try {
            $offices = Office::where('company_id', $user->company_id)->get();

            if ($offices->isEmpty()) {
                \Log::warning('No offices found for user', ['user_id' => $user->id, 'company_id' => $user->company_id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Kantor tidak ditemukan!',
                ], 404);
            }

            $nearestOffice = null;
            $minDistance   = PHP_INT_MAX;

            foreach ($offices as $office) {
                $dist = $this->haversine(
                    $request->latitude,
                    $request->longitude,
                    $office->latitude,
                    $office->longitude
                );

                if ($dist < $minDistance) {
                    $minDistance   = $dist;
                    $nearestOffice = $office;
                }
            }

            $gpsAccuracy = $request->input('accuracy', 0);
            $effectiveRadius = $nearestOffice->radius + ($nearestOffice->tolerance_coefficient * $gpsAccuracy);

            // === MODE TESTING ===
            // Kalau kantor ini lagi mode testing, skip logic absen harian (attendances)
            // sepenuhnya. Cukup hitung jarak & catat ke tolerance_coefficient_tests,
            // lalu langsung balas ke app. Bisa dipanggil berkali-kali tanpa batas.
            if ($nearestOffice->is_testing_mode) {
                $result = $minDistance <= $effectiveRadius ? 'accepted' : 'rejected';

                $this->logToleranceCoefficientTest(
                    $user,
                    $nearestOffice,
                    'test_manual',
                    (float) $request->latitude,
                    (float) $request->longitude,
                    (float) $gpsAccuracy,
                    (float) $minDistance,
                    (float) $effectiveRadius,
                    $result
                );

                return response()->json([
                    'success'            => true,
                    'testing_mode'       => true,
                    'message'            => $result === 'accepted'
                        ? 'Testing: lokasi diterima (jarak ' . round($minDistance) . 'm)'
                        : 'Testing: lokasi ditolak (jarak ' . round($minDistance) . 'm, radius efektif ' . round($effectiveRadius) . 'm)',
                    'distance_to_office' => round($minDistance, 2),
                    'effective_radius'   => round($effectiveRadius, 2),
                    'gps_accuracy'       => $gpsAccuracy,
                ]);
            }

            // Cek dulu ini bakal jadi percobaan check-in atau check-out, buat keperluan logging
            $existingToday = Attendance::where('user_id', $user->id)->where('date', $today)->first();
            $attemptType = (!$existingToday || $existingToday->time_out) ? 'check_in' : 'check_out';

            if ($minDistance > $effectiveRadius) {
                $overflow = round($minDistance - $effectiveRadius);

                $this->logToleranceCoefficientTest(
                    $user,
                    $nearestOffice,
                    $attemptType,
                    (float) $request->latitude,
                    (float) $request->longitude,
                    (float) $gpsAccuracy,
                    (float) $minDistance,
                    (float) $effectiveRadius,
                    'rejected'
                );

                return response()->json([
                    'message' => 'Anda berada di luar radius kantor ' . $overflow . ' meter',
                ], 422);
            }

            $office = $nearestOffice;

            if ($office->company_id !== $user->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kantor tidak sesuai dengan perusahaan Anda.',
                ], 403);
            }

            return \DB::transaction(function () use ($request, $user, $today, $time, $office, &$uploadedImagePath, $minDistance, $effectiveRadius, $gpsAccuracy) {
                $attendance = Attendance::where('user_id', $user->id)
                    ->where('date', $today)
                    ->lockForUpdate()
                    ->first();

                if ($attendance && $attendance->time_out) {
                    return response()->json(['message' => 'Anda sudah absen pulang hari ini'], 422);
                }

                if (!$attendance) {
                    $uploadedFile = $request->file('image');
                    $folder = 'absensi/foto_masuk';
                    $filename = 'in-' . time() . '-' . \Illuminate\Support\Str::random(5);

                    $path = Storage::disk('cloudinary')->putFileAs($folder, $uploadedFile, $filename . '.' . $uploadedFile->getClientOriginalExtension());
                    $uploadedImagePath = $path;

                    $startTime = $office->check_in_time;
                    $isLate = $startTime
                        ? Carbon::parse($time)->gt(Carbon::parse($startTime)->addMinutes(10))
                        : false;

                    Attendance::create([
                        'user_id'       => $user->id,
                        'office_id'     => $office->id,
                        'company_id'    => $user->company_id,
                        'date'          => $today,
                        'time_in'       => $time,
                        'lat_in'        => $request->latitude,
                        'long_in'       => $request->longitude,
                        'accuracy_in'   => $request->input('accuracy'),
                        'pic_in'        => $uploadedImagePath,
                        'is_late'       => $isLate,
                        'face_verified' => $request->boolean('face_verified'),
                    ]);

                    \Log::info('Attendance check-in with accuracy', [
                        'user_id'       => $user->id,
                        'accuracy_in'   => $request->input('accuracy'),
                    ]);

                    \Log::info('Attendance check-in', [
                        'user_id'      => $user->id,
                        'office_id'    => $office->id,
                        'is_late'      => $isLate,
                        'face_verified' => $request->boolean('face_verified'),
                    ]);

                    $this->logToleranceCoefficientTest(
                        $user,
                        $office,
                        'check_in',
                        (float) $request->latitude,
                        (float) $request->longitude,
                        (float) $gpsAccuracy,
                        (float) $minDistance,
                        (float) $effectiveRadius,
                        'accepted'
                    );

                    $statusLabel = $isLate ? ' (Terlambat)' : ' (Tepat Waktu)';
                    return response()->json([
                        'success' => true,
                        'message' => 'Berhasil absen masuk. Selamat bekerja!' . $statusLabel,
                    ]);
                } else {
                    if ($attendance->time_in && !$attendance->time_out) {
                        // lanjut checkout
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Anda sudah melakukan check-in dan check-out hari ini.',
                        ], 422);
                    }

                    $endTime = $office->check_out_time;
                    $canCheckout = true;
                    $checkoutMessage = '';

                    if ($endTime) {
                        $officialCheckOutTime = Carbon::parse($endTime);
                        $currentTime = Carbon::parse($time);
                        $checkInTime = Carbon::parse($attendance->time_in);
                        $workedHours = $checkInTime->diffInHours($currentTime);

                        if ($currentTime->lt($officialCheckOutTime) && $workedHours < 8) {
                            $remaining = $currentTime->diff($officialCheckOutTime)->format('%H jam %I menit');
                            $canCheckout = false;
                            $checkoutMessage = "Belum waktunya absen pulang. Tunggu {$remaining} lagi.";
                        }
                    }

                    if (!$canCheckout) {
                        return response()->json([
                            'success' => false,
                            'message' => $checkoutMessage,
                        ], 422);
                    }

                    $uploadedFile = $request->file('image');
                    $folder = 'absensi/foto_pulang';
                    $filename = 'out-' . time() . '-' . \Illuminate\Support\Str::random(5);

                    $path = Storage::disk('cloudinary')->putFileAs($folder, $uploadedFile, $filename . '.' . $uploadedFile->getClientOriginalExtension());
                    $uploadedImagePath = $path;

                    $attendance->update([
                        'time_out'      => $time,
                        'lat_out'       => $request->latitude,
                        'long_out'      => $request->longitude,
                        'accuracy_out'  => $request->input('accuracy'),
                        'pic_out'       => $uploadedImagePath,
                        'face_verified' => $request->boolean('face_verified'),
                    ]);

                    \Log::info('Attendance check-out with accuracy', [
                        'user_id'       => $user->id,
                        'accuracy_out'  => $request->input('accuracy'),
                    ]);

                    \Log::info('Attendance check-out', [
                        'user_id'      => $user->id,
                        'office_id'    => $office->id,
                        'face_verified' => $request->boolean('face_verified'),
                    ]);

                    $this->logToleranceCoefficientTest(
                        $user,
                        $office,
                        'check_out',
                        (float) $request->latitude,
                        (float) $request->longitude,
                        (float) $gpsAccuracy,
                        (float) $minDistance,
                        (float) $effectiveRadius,
                        'accepted'
                    );

                    return response()->json([
                        'success' => true,
                        'message' => 'Berhasil absen pulang. Hati-hati dijalan!',
                    ]);
                }
            });
        } catch (\Exception $e) {
            if ($uploadedImagePath) {
                Storage::disk('cloudinary')->delete($uploadedImagePath);
            }
            \Log::error('Attendance store error', ['error' => $e->getMessage(), 'user_id' => $user->id]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan absensi. Silakan coba lagi.',
            ], 500);
        }
    }

    private function haversine($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    /**
     * Catat setiap percobaan absen (diterima atau ditolak) ke tabel tolerance_coefficient_tests,
     * dipakai buat keperluan testing/riset adaptive radius. Dibungkus try-catch biar kalau
     * logging ini gagal, proses absen utama tetap jalan normal.
     */
    private function logToleranceCoefficientTest(
        $user,
        $office,
        string $attendanceType,
        float $latitude,
        float $longitude,
        float $gpsAccuracy,
        float $distanceToOffice,
        float $effectiveRadius,
        string $result
    ): void {
        try {
            ToleranceCoefficientTest::create([
                'office_id'             => $office->id,
                'user_id'               => $user->id,
                'company_id'            => $user->company_id,
                'tolerance_coefficient' => $office->tolerance_coefficient,
                'test_date'             => Carbon::today()->toDateString(),
                'test_phase'            => 'auto_log',
                'attendance_type'       => $attendanceType,
                'attendance_time'       => Carbon::now()->toTimeString(),
                'latitude'              => $latitude,
                'longitude'             => $longitude,
                'gps_accuracy'          => $gpsAccuracy,
                'distance_to_office'    => $distanceToOffice,
                'office_radius'         => $office->radius,
                'effective_radius'      => $effectiveRadius,
                'result'                => $result,
                'distance_variance'     => $distanceToOffice - $effectiveRadius,
            ]);
        } catch (\Exception $e) {
            \Log::warning('Gagal mencatat tolerance_coefficient_test', ['error' => $e->getMessage()]);
        }
    }
}
