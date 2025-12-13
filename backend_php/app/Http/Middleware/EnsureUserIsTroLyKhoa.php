<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class EnsureUserIsTroLyKhoa
{
    /**
     * Handle an incoming request.
     * Ensures the authenticated user has the 'tro_ly_khoa' role.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $taiKhoan = JWTAuth::parseToken()->authenticate();

            if (!$taiKhoan || $taiKhoan->loai_tai_khoan !== 'tro_ly_khoa') {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Forbidden - Requires tro_ly_khoa role',
                    'data' => null
                ], 403);
            }

            return $next($request);

        } catch (\Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Unauthorized',
                'data' => null
            ], 401);
        }
    }
}
