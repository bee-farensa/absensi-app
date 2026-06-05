<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Tampilkan foto profil user yang sedang login.
     * GET /api/profile/photo
     */
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success'   => true,
            'message'   => 'Foto profil berhasil diambil',
            'data'      => [
                'image_url' => $user->image
                    ? asset('storage/' . $user->image)
                    : null,
            ],
        ]);
    }

    /**
     * Upload atau ganti foto profil.
     * PUT /api/profile/photo
     */
    public function update(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = $request->user();

        // Hapus foto lama jika ada
        if ($user->image) {
            Storage::disk('public')->delete($user->image);
        }

        // Simpan foto baru
        $path = $request->file('image')->store('profile-photos', 'public');
        $user->image = $path;
        $user->save();

        return response()->json([
            'success'   => true,
            'message'   => 'Foto profil berhasil diperbarui',
            'data'      => [
                'image_url' => asset('storage/' . $user->image),
            ],
        ]);
    }

    /**
     * Hapus foto profil (set ke null).
     * DELETE /api/profile/photo
     */
    public function destroy(Request $request)
    {
        $user = $request->user();

        if (!$user->image) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada foto profil untuk dihapus',
            ], 404);
        }

        // Hapus file dari storage
        Storage::disk('public')->delete($user->image);

        // Set kolom image ke null
        $user->image = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil dihapus',
            'data'    => [
                'image_url' => null,
            ],
        ]);
    }

    /**
     * Download foto profil dengan authorization check.
     * GET /api/profile/photo/download/{filename}
     */
    public function downloadPhoto(Request $request, $filename)
    {
        $user = $request->user();

        // Validasi bahwa file milik user yang sedang login
        if (!$user->image || basename($user->image) !== $filename) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. File tidak ditemukan atau bukan milik Anda.',
            ], 403);
        }

        $path = Storage::disk('public')->path($user->image);

        if (!file_exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak ditemukan.',
            ], 404);
        }

        return response()->download($path);
    }
}
