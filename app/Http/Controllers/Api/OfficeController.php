<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Office;
use Illuminate\Http\Request;

class OfficeController extends Controller
{
    public function index(Request $request)
    {
        // Ambil user yang sedang login
        $user = $request->user();

        // Ambil daftar kantor yang sesuai dengan ID Perusahaan si user
        $offices = Office::where('company_id', $user->company_id)->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar lokasi kantor berhasil diambil',
            'data' => $offices
        ]);
    }
}