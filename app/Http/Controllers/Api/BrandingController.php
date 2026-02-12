<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class BrandingController extends Controller
{
    public function index($nik)
    {
        // Cari user berdasarkan NIK beserta data perusahaannya
        $user = User::with('company')->where('nik', $nik)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'NIK tidak terdaftar'
            ], 404);
        }

        $company = $user->company;

        if (!$company) {
            return response()->json([
                'status' => 'error',
                'message' => 'Karyawan belum terhubung ke perusahaan'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'company_name' => $company->name,
                'theme_color'  => $company->theme_color,
                // Kita buat URL lengkap agar Flutter tinggal pakai
                'logo_url'     => $company->logo ? asset('storage/' . $company->logo) : null,
            ]
        ]);
    }
}