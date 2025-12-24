<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Domain\RBAC\Repositories\RBACRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dynamic RBAC Middleware
 * 
 * Checks if the authenticated user's role has permission to access the current API endpoint.
 * Uses database-driven permissions with Redis caching for performance.
 * 
 * Usage in routes:
 *   ->middleware('rbac')           // Check against user's role
 *   ->middleware('rbac:sinh_vien') // Require specific role (legacy compat)
 */
class DynamicRBACMiddleware
{
    private const CACHE_TTL = 300; // 5 minutes
    private const CACHE_PREFIX = 'rbac:';

    public function __construct(
        private RBACRepositoryInterface $rbacRepository
    ) {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $requiredRole = null): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Unauthorized - Chưa đăng nhập',
            ], 401);
        }

        $userRole = $user->loai_tai_khoan;
        $routePath = '/' . $request->path();
        $method = $request->method();

        // admin_system always has full access (safety fallback)
        if ($userRole === 'admin_system') {
            return $next($request);
        }

        // If specific role required (legacy compatibility mode)
        if ($requiredRole !== null && $userRole !== $requiredRole) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Forbidden - Không có quyền truy cập',
            ], 403);
        }

        // Check dynamic permission from database (with cache)
        if (!$this->hasPermission($userRole, $routePath, $method)) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Forbidden - Bạn không có quyền truy cập chức năng này',
            ], 403);
        }

        return $next($request);
    }

    /**
     * Check permission with caching
     */
    private function hasPermission(string $roleCode, string $routePath, string $method): bool
    {
        $cacheKey = self::CACHE_PREFIX . md5("{$roleCode}:{$method}:{$routePath}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($roleCode, $routePath, $method) {
            return $this->rbacRepository->hasPermission($roleCode, $routePath, $method);
        });
    }

    /**
     * Clear permission cache for a role (call after admin updates permissions)
     */
    public static function clearCacheForRole(string $roleCode): void
    {
        // In production, use Redis SCAN to find and delete matching keys
        // For now, just flush all RBAC cache
        Cache::flush(); // TODO: More granular cache invalidation
    }
}
