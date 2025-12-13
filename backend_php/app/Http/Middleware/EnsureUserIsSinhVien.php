<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class EnsureUserIsSinhVien
{
    /**
     * Handle an incoming request.
     * Ensure the authenticated user has role 'sinh_vien'
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Token không hợp lệ'
                ], 401);
            }

            if ($user->loai_tai_khoan !== 'sinh_vien') {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Chỉ sinh viên mới có quyền truy cập'
                ], 403);
            }

            return $next($request);

        } catch (\Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Lỗi xác thực: ' . $e->getMessage()
            ], 401);
        }
    }
}
