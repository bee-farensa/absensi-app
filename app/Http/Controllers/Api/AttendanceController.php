<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Office;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'latitude' => 'required',
            'longitude' => 'required',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048', // Foto base64 atau file
        ]);

        $user = $request->user();
        $today = Carbon::today()->toDateString();
        $time = Carbon::now()->toTimeString();

        // 1. Ambil data kantor si user
        $office = Office::where('company_id', $user->company_id)->first();

        if (!$office) {
            return response()->json(['message' => 'Kantor tidak ditemukan'], 404);
        }

        // 2. Cek apakah sudah absen masuk hari ini?
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        // 3. Hitung Jarak (Rumus Haversine)
        $distance = $this->haversine(
            $request->latitude,
            $request->longitude,
            $office->latitude,
            $office->longitude
        );

        // Cek apakah di luar radius (dalam meter)
        if ($distance > $office->radius) {
            return response()->json([
                'message' => 'Anda berada di luar radius kantor. Jarak Anda: ' . round($distance) . 'm',
            ], 422);
        }

        // 4. Proses Simpan Foto
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $user->id . '.png';

            // Gunakan storeAs dengan disk public secara eksplisit
            $imagePath = $image->storeAs('attendances', $imageName, 'public');
        } else {
            return response()->json(['message' => 'File gambar tidak terbaca oleh server'], 422);
        }

        if (!$attendance) {

            $startTime = $office->start_time; // Jam masuk kantor (misal 08:00:00)
            $attendanceTime = Carbon::now()->toTimeString(); // Jam saat ini

            // Cek apakah terlambat
            $isLate = $attendanceTime > $startTime;

            // JIKA BELUM ABSEN (Check-in)
            Attendance::create([
                'user_id' => $user->id,
                'office_id' => $office->id,
                'date' => $today,
                'time_in' => $time,
                'lat_in' => $request->latitude,
                'long_in' => $request->longitude,
                'pic_in' => 'attendances/' . $imageName, // Simpan foldernya juga di DB
                'is_late' => $isLate, 
            ]);
            $statusLabel = $isLate ? ' (Terlambat)' : ' (Tepat Waktu)';
            return response()->json(['message' => 'Berhasil absen masuk' . $statusLabel]);
        } else {
            // JIKA SUDAH ABSEN MASUK (Check-out)
            if ($attendance->time_out) {
                return response()->json(['message' => 'Anda sudah absen pulang hari ini'], 422);
            }

            $attendance->update([
                'time_out' => $time,
                'lat_out' => $request->latitude,
                'long_out' => $request->longitude,
                'pic_out' => $imageName,
            ]);
            return response()->json(['message' => 'Berhasil absen pulang (Check-out)']);
        }
    }

    // Fungsi Matematika Haversine
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
