<?php

namespace App\Application\Admin\UseCases;

use App\Domain\Admin\Repositories\AdminRepositoryInterface;

/**
 * UseCase: Lấy danh sách tất cả Roles
 */
class GetAllRolesUseCase
{
    public function __construct(
        private AdminRepositoryInterface $repository
    ) {}

    public function execute(): array
    {
        $roles = $this->repository->getAllRoles();

        return [
            'isSuccess' => true,
            'data' => $roles->map(fn($role) => [
                'id' => $role->id,
                'code' => $role->code,
                'name' => $role->name,
                'description' => $role->description,
                'isSystem' => $role->is_system,
            ])->toArray(),
            'message' => 'Lấy danh sách roles thành công'
        ];
    }
}
