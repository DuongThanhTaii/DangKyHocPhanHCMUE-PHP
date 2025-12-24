<?php

namespace App\Application\Admin\UseCases;

use App\Domain\Admin\Repositories\AdminRepositoryInterface;
use App\Http\Middleware\DynamicRBACMiddleware;

/**
 * UseCase: Bật/Tắt permission cho Role
 */
class ToggleRolePermissionUseCase
{
    public function __construct(
        private AdminRepositoryInterface $repository
    ) {}

    public function execute(string $roleId, string $permissionId, bool $isEnabled): array
    {
        $role = $this->repository->getRoleById($roleId);

        if (!$role) {
            return [
                'isSuccess' => false,
                'data' => null,
                'message' => 'Không tìm thấy role'
            ];
        }

        // Prevent disabling for admin_system
        if ($role->code === 'admin_system') {
            return [
                'isSuccess' => false,
                'data' => null,
                'message' => 'Không thể thay đổi quyền của Admin Hệ Thống'
            ];
        }

        $success = $this->repository->toggleRolePermission($roleId, $permissionId, $isEnabled);

        if ($success) {
            // Clear RBAC cache for this role
            DynamicRBACMiddleware::clearCacheForRole($role->code);
        }

        return [
            'isSuccess' => $success,
            'data' => [
                'roleId' => $roleId,
                'permissionId' => $permissionId,
                'isEnabled' => $isEnabled,
            ],
            'message' => $isEnabled ? 'Đã bật permission' : 'Đã tắt permission'
        ];
    }
}
