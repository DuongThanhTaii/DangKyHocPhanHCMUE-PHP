<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class EnsureUserIsTruongKhoa
{
    /**
     * Handle an incoming request.
     * Ensures the authenticated user has the 'truong_khoa' role.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $taiKhoan = JWTAuth::parseToken()->authenticate();

            if (!$taiKhoan || $taiKhoan->loai_tai_khoan !== 'truong_khoa') {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Forbidden - Requires truong_khoa role',
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
