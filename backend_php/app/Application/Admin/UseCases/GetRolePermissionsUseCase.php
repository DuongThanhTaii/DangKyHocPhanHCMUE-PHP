<?php

namespace App\Application\Admin\UseCases;

use App\Domain\Admin\Repositories\AdminRepositoryInterface;

/**
 * UseCase: Lấy danh sách permissions của 1 Role
 */
class GetRolePermissionsUseCase
{
    public function __construct(
        private AdminRepositoryInterface $repository
    ) {}

    public function execute(string $roleId): array
    {
        $role = $this->repository->getRoleById($roleId);

        if (!$role) {
            return [
                'isSuccess' => false,
                'data' => null,
                'message' => 'Không tìm thấy role'
            ];
        }

        $permissions = $this->repository->getRolePermissions($roleId);

        // Group by module
        $grouped = $permissions->groupBy('module')->map(function ($items, $module) {
            return [
                'module' => $module ?: 'Other',
                'permissions' => $items->map(fn($p) => [
                    'id' => $p->id,
                    'routeName' => $p->route_name,
                    'routePath' => $p->route_path,
                    'method' => $p->method,
                    'description' => $p->description,
                    'isPublic' => $p->is_public,
                    'isEnabled' => (bool) $p->is_enabled,
                ])->values()->toArray()
            ];
        })->values()->toArray();

        return [
            'isSuccess' => true,
            'data' => [
                'role' => [
                    'id' => $role->id,
                    'code' => $role->code,
                    'name' => $role->name,
                    'description' => $role->description,
                ],
                'permissionGroups' => $grouped,
                'totalPermissions' => $permissions->count(),
                'enabledCount' => $permissions->where('is_enabled', true)->count(),
            ],
            'message' => 'Lấy permissions thành công'
        ];
    }
}
