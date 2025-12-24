<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdminSystem
{
    /**
     * Handle an incoming request.
     * Ensure user has admin_system role (Phòng CNTT)
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($request->user()->loai_tai_khoan !== 'admin_system') {
            return response()->json(['message' => 'Forbidden: Requires Admin System role'], 403);
        }

        return $next($request);
    }
}
