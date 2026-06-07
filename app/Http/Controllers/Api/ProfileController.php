<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil diambil',
            'data' => [
                'image_url' => $user->image ?? null,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = $request->user();

        // Hapus foto lama jika ada
        if ($user->image) {
            $publicId = pathinfo($user->image, PATHINFO_FILENAME);
            Cloudinary::destroy('profile-photos/' . $publicId);
        }
        $uploaded = Cloudinary::upload($request->file('image')->getRealPath(), [
            'folder' => 'profile-photos'
        ]);
        // Simpan foto baru
        // $path = $request->file('image')->store('profile-photos', 'public');
        $user->image = $uploaded->getSecurePath();
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil diperbarui',
            'data' => [
                'image_url' => $user->image,
            ],
        ]);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        if (!$user->image) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada foto profil untuk dihapus',
            ], 404);
        }

        $publicId = pathinfo($user->image, PATHINFO_FILENAME);
        Cloudinary::destroy('profile-photos/' . $publicId);
        // Hapus file dari storage
        // Storage::disk('public')->delete($user->image);

        // Set kolom image ke null
        $user->image = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil dihapus',
            'data' => [
                'image_url' => null,
            ],
        ]);
    }

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
