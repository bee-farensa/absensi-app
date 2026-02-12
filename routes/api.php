<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OfficeController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\LeaveRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BrandingController;

// Endpoint Login (Bisa diakses tanpa login)
Route::post('/login', [AuthController::class, 'login']);

// Group Route yang butuh Login (Proteksi Sanctum)
Route::middleware('auth:sanctum')->group(function () {

    // Test ambil data user yang sedang login
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/offices', [OfficeController::class, 'index']);
    Route::post('/attendance', [AttendanceController::class, 'store']);
    Route::post('/leave', [LeaveRequestController::class, 'store']);
    Route::get('/branding/{nik}', [BrandingController::class, 'index']);
});