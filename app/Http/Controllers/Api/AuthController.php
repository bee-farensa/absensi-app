<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::with(['company', 'department', 'position'])
                    ->where('email', $request->email)
                    ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
            ], 401);
        }

        // Tolak superadmin
        if ($user->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke aplikasi.',
            ], 403);
        }

        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        $userData = [
            'id'         => $user->id,
            'name'       => $user->name,
            'nik'        => $user->nik,
            'email'      => $user->email,
            'image_url'  => $user->image ? asset('storage/' . $user->image) : null,
            'company'    => $user->company?->name,
            'department' => $user->department?->name,
            'position'   => $user->position?->name,
            'role'       => $user->getRoleNames()->first(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'token'   => $token,
            'user'    => $userData,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil logout',
        ]);
    }
}
