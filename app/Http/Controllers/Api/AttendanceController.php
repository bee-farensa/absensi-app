<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Office;
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
        
        // Validasi month dan year
        $month = (int) $request->query('month', Carbon::now()->month);
        $year = (int) $request->query('year', Carbon::now()->year);
        
        // Ensure valid month range
        if ($month < 1 || $month > 12) {
            $month = Carbon::now()->month;
        }
        
        // Ensure valid year range (prevent too old or too far future)
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
                    'time_in'       => $item->time_in,
                    'time_out'      => $item->time_out,
                    'is_late'       => (bool) $item->is_late,
                    'face_verified' => (bool) $item->face_verified,
                    'status'        => $item->time_out ? 'Lengkap' : 'Belum Checkout',
                    // generate full URL Cloudinary untuk mobile jika path disimpan secara relatif
                    'pic_in'        => $item->pic_in ? Storage::disk('cloudinary')->url($item->pic_in) : null,
                    'pic_out'       => $item->pic_out ? Storage::disk('cloudinary')->url($item->pic_out) : null,
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
            'image'         => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'face_verified' => 'sometimes|boolean',
        ]);

        $user  = $request->user();
        $today = Carbon::today()->toDateString();
        $time  = Carbon::now()->toTimeString();
        
        $uploadedImagePath = null;

        try {
            // 1. Ambil semua kantor milik perusahaan si user
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

            // Cari kantor terdekat
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

            // Cek apakah di luar radius kantor terdekat
            if ($minDistance > $nearestOffice->radius) {
                $overflow = round($minDistance - $nearestOffice->radius);

                return response()->json([
                    'message' => 'Anda berada di luar radius kantor ' . $overflow . ' meter',
                ], 422);
            }

            $office = $nearestOffice;

            // Validasi bahwa office milik company user
            if ($office->company_id !== $user->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kantor tidak sesuai dengan perusahaan Anda.',
                ], 403);
            }

            // Use database transaction to prevent race conditions
            return \DB::transaction(function () use ($request, $user, $today, $time, $office, &$uploadedImagePath) {
                // Lock the row to prevent concurrent updates
                $attendance = Attendance::where('user_id', $user->id)
                    ->where('date', $today)
                    ->lockForUpdate()
                    ->first();

                // 3. Cek apakah sudah absen pulang
                if ($attendance && $attendance->time_out) {
                    return response()->json(['message' => 'Anda sudah absen pulang hari ini'], 422);
                }

                if (!$attendance) {
                    // ── CHECK-IN ──────────────────────────────────────────────────
                    
                    // Upload foto ke Cloudinary disk dengan folder penamaan sesuai Filament resource kemarin
                    $uploadedFile = $request->file('image');
                    $folder = 'absensi/foto_masuk';
                    $filename = 'in-' . time() . '-' . \Illuminate\Support\Str::random(5);
                    
                    $path = Storage::disk('cloudinary')->putFileAs($folder, $uploadedFile, $filename . '.' . $uploadedFile->getClientOriginalExtension());
                    $uploadedImagePath = $path;

                    $startTime = $office->check_in_time;

                    // Toleransi keterlambatan 10 menit
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
                        'pic_in'        => $uploadedImagePath,
                        'is_late'       => $isLate,
                        'face_verified' => $request->boolean('face_verified'),
                    ]);

                    \Log::info('Attendance check-in', [
                        'user_id' => $user->id,
                        'office_id' => $office->id,
                        'is_late' => $isLate,
                        'face_verified' => $request->boolean('face_verified'),
                    ]);

                    $statusLabel = $isLate ? ' (Terlambat)' : ' (Tepat Waktu)';
                    return response()->json([
                        'success' => true,
                        'message' => 'Berhasil absen masuk. Selamat bekerja!' . $statusLabel,
                    ]);

                } else {
                    // ── CHECK-OUT ─────────────────────────────────────────────────
                    if ($attendance->time_in && !$attendance->time_out) {
                        // Ini adalah check-out, lanjutkan
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
                            $checkoutMessage = "Belum waktunya absen pulang. Tunggu {$remaining} lagi .";
                        }
                    }

                    if (!$canCheckout) {
                        return response()->json([
                            'success' => false,
                            'message' => $checkoutMessage,
                        ], 422);
                    }

                    // Upload foto pulang ke Cloudinary disk
                    $uploadedFile = $request->file('image');
                    $folder = 'absensi/foto_pulang';
                    $filename = 'out-' . time() . '-' . \Illuminate\Support\Str::random(5);
                    
                    $path = Storage::disk('cloudinary')->putFileAs($folder, $uploadedFile, $filename . '.' . $uploadedFile->getClientOriginalExtension());
                    $uploadedImagePath = $path;

                    $attendance->update([
                        'time_out'      => $time,
                        'lat_out'       => $request->latitude,
                        'long_out'      => $request->longitude,
                        'pic_out'       => $uploadedImagePath,
                        'face_verified' => $request->boolean('face_verified'),
                    ]);

                    \Log::info('Attendance check-out', [
                        'user_id' => $user->id,
                        'office_id' => $office->id,
                        'face_verified' => $request->boolean('face_verified'),
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Berhasil absen pulang. Hati-hati dijalan!',
                    ]);
                }
            });
        } catch (\Exception $e) {
            // Bersihkan file di Cloudinary jika transaction gagal di tengah jalan
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
        $earthRadius = 6371000; // dalam meter
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}