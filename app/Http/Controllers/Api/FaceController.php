<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FaceController extends Controller
{
    public function enroll(Request $request)
    {
        $request->validate([
            'embedding' => 'required|array|size:192',
            'embedding.*' => 'required|numeric',
        ]);

        $user = $request->user();

        $user->update([
            'face_embedding' => $request->embedding,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data wajah berhasil disimpan.',
        ]);
    }

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
    public function destroy(Request $request)
    {
        $user = $request->user();
        $user->update(['face_embedding' => null]);
        return response()->json([
            'success' => true,
            'message' => 'Data wajah berhasil dihapus.',
        ]);
    }
}