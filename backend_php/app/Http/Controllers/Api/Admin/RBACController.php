<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Application\Admin\UseCases\GetAllRolesUseCase;
use App\Application\Admin\UseCases\GetRolePermissionsUseCase;
use App\Application\Admin\UseCases\ToggleRolePermissionUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * RBAC Management Controller - Phòng CNTT
 */
class RBACController extends Controller
{
    public function __construct(
        private GetAllRolesUseCase $getAllRolesUseCase,
        private GetRolePermissionsUseCase $getRolePermissionsUseCase,
        private ToggleRolePermissionUseCase $toggleRolePermissionUseCase
    ) {}

    /**
     * GET /api/admin/roles
     * Lấy danh sách tất cả roles
     */
    public function getRoles(): JsonResponse
    {
        $result = $this->getAllRolesUseCase->execute();
        return response()->json($result);
    }

    /**
     * GET /api/admin/roles/{id}/permissions
     * Lấy permissions của 1 role
     */
    public function getRolePermissions(string $id): JsonResponse
    {
        $result = $this->getRolePermissionsUseCase->execute($id);
        return response()->json($result, $result['isSuccess'] ? 200 : 404);
    }

    /**
     * PUT /api/admin/roles/{roleId}/permissions/{permissionId}
     * Toggle permission on/off
     */
    public function togglePermission(Request $request, string $roleId, string $permissionId): JsonResponse
    {
        $isEnabled = $request->boolean('isEnabled', true);
        
        $result = $this->toggleRolePermissionUseCase->execute($roleId, $permissionId, $isEnabled);
        return response()->json($result, $result['isSuccess'] ? 200 : 400);
    }
}
