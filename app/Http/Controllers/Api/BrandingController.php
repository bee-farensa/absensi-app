<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; 

class BrandingController extends Controller
{
    public function index(Request $request, $email)
    {
        // Validate email format to prevent SQL injection
        $validated = validator(['email' => $email], [
            'email' => 'required|email|max:255',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Format email tidak valid',
            ], 422);
        }

        // Only allow user to query their own email for security
        $authenticatedUser = $request->user();
        if ($authenticatedUser && $authenticatedUser->email !== $email) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Anda hanya dapat mengakses branding email Anda sendiri.',
            ], 403);
        }

        // Cari user berdasarkan Email
        $user = User::with('company')
            ->where('email', $email)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email tidak terdaftar',
            ], 404);
        }

        $company = $user->company;

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan belum terhubung ke perusahaan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'company_name' => $company->name,
                // Generate full Cloudinary URL from path stored in database
                'logo_url' => $company->logo ? Storage::disk('cloudinary')->url($company->logo) : null,
            ],
        ]);
    }
}