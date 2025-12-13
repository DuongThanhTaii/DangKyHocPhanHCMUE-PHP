<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsPdt
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is logged in
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Check role
        // Assuming Auth::user() returns TaiKhoan model which has loai_tai_khoan
        if ($request->user()->loai_tai_khoan !== 'phong_dao_tao') {
            return response()->json(['message' => 'Forbidden: Requires PDT role'], 403);
        }

        return $next($request);
    }
}
