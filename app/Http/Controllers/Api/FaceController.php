<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FaceController extends Controller
{
    /**
     * POST /api/face-enrollment
     * Simpan face embedding milik user yang sedang login.
     * Flutter mengirim array embedding dari MobileFaceNet.
     */
    public function enroll(Request $request)
    {
        $request->validate([
            'embedding'   => 'required|array|size:128',
            'embedding.*' => 'required|numeric',
        ]);

        $user = $request->user();

        // Validasi bahwa embedding array tidak kosong dan semua values valid
        $embedding = $request->embedding;
        if (empty($embedding) || count(array_filter($embedding, fn($v) => is_numeric($v))) !== count($embedding)) {
            return response()->json([
                'success' => false,
                'message' => 'Format embedding tidak valid.',
            ], 422);
        }

        // Validasi range values - embedding harus normalized antara -1 dan 1
        $invalidValues = array_filter($embedding, fn($v) => $v < -1 || $v > 1);
        if (!empty($invalidValues)) {
            return response()->json([
                'success' => false,
                'message' => 'Nilai embedding harus berada dalam range -1 hingga 1.',
            ], 422);
        }

        $user->update([
            'face_embedding' => $embedding,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data wajah berhasil disimpan.',
        ]);
    }

    /**
     * GET /api/face-embedding
     * Ambil face embedding milik user yang sedang login.
     * Digunakan Flutter untuk membandingkan (compare) saat verifikasi absen.
     */
    public function getEmbedding(Request $request)
    {
        $user = $request->user();

        if (!$user->face_embedding || empty($user->face_embedding)) {
            return response()->json([
                'success' => false,
                'message' => 'Wajah belum terdaftar. Daftarkan wajah terlebih dahulu.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'embedding' => $user->face_embedding,
        ]);
    }
}
