<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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

        $cloudinary = new \Cloudinary\Cloudinary([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key' => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
        ]);

        $result = $cloudinary->uploadApi()->upload(
            $request->file('image')->getRealPath(),
            ['folder' => 'profile-photos']
        );

        $user->image = $result['secure_url'];
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

        if (!$user->image) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. File tidak ditemukan atau bukan milik Anda.',
            ], 403);
        }

        return response()->redirectTo($user->image);
    }
}