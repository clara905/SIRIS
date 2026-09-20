<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LoginCaptcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Login user
     */
    public function login(Request $request, LoginCaptcha $captcha)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'captcha_id' => ['required', 'uuid'],
            'captcha_answer' => ['required', 'string', 'size:6'],
        ]);

        if (! $captcha->verify($credentials['captcha_id'], $credentials['captcha_answer'])) {
            return response()->json([
                'success' => false,
                'message' => 'Kode CAPTCHA salah atau kedaluwarsa. Silakan gunakan kode baru.',
            ], 422);
        }

        $user = User::with([
            'role',
            'bidang',
            'subBidang',
            'satker',
        ])->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak aktif.',
            ], 403);
        }

        // Hapus token lama jika diperlukan
        $user->tokens()->delete();

        $token = $user->createToken('siris-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        $user = $request->user()->load([
            'role',
            'bidang',
            'subBidang',
            'satker',
        ]);

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }
}
