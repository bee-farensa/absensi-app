<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OfficeController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\LeaveRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BrandingController;
use App\Http\Controllers\Api\FaceController;
use App\Http\Controllers\Api\ProfileController;

// Endpoint Login (Bisa diakses tanpa login)
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Group Route yang butuh Login (Proteksi Sanctum)
Route::middleware('auth:sanctum')->group(function () {

    // Test ambil data user yang sedang login
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/offices', [OfficeController::class, 'index']);

    // Absensi
    Route::get('/attendance', [AttendanceController::class, 'index']);   // Riwayat absensi
    Route::post('/attendance', [AttendanceController::class, 'store'])->middleware('throttle:10,1');  // Absen masuk/keluar - rate limited

    // Izin & Cuti
    Route::get('/leave', [LeaveRequestController::class, 'index']);      // Riwayat izin
    Route::post('/leave', [LeaveRequestController::class, 'store']);     // Ajukan izin

    // Persetujuan Izin & Cuti (Hanya Atasan)
    Route::get('/leave/approvals', [LeaveRequestController::class, 'approvals']);
    Route::put('/leave/{id}/approve', [LeaveRequestController::class, 'approve']);
    Route::put('/leave/{id}/reject', [LeaveRequestController::class, 'reject']);

    // Face Recognition
    Route::post('/face-enrollment', [FaceController::class, 'enroll']);      // Daftarkan wajah
    Route::get('/face-embedding', [FaceController::class, 'getEmbedding']); // Ambil embedding wajah

    // Profile Photo Management (Photo CRUD Only)
    Route::get('/profile/photo', [ProfileController::class, 'show']);        // Ambil foto profil
    Route::put('/profile/photo', [ProfileController::class, 'update']);      // Upload/update foto profil
    Route::delete('/profile/photo', [ProfileController::class, 'destroy']);  // Hapus foto profil
    Route::get('/profile/photo/download/{filename}', [ProfileController::class, 'downloadPhoto']); // Download foto dengan auth

    // Profil karyawan lengkap
    Route::get('/profile', function (Request $request) {
        $user = $request->user()->load(['company', 'department', 'position', 'roles']);
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'nik' => $user->nik,
                'email' => $user->email,
                'image_url' => $user->image ? asset('storage/' . $user->image) : null,
                'company' => $user->company?->name,
                'department' => $user->department?->name,
                'position' => $user->position?->name,
                'role' => $user->roles->pluck('name')->first(),
            ],
        ]);
    });

    // Branding endpoint - hanya bisa diakses oleh user yang sudah login
    Route::get('/branding/{email}', [BrandingController::class, 'index']);
});