<?php

namespace App\Application\Auth\UseCases;

use App\Application\Auth\DTOs\ChangePasswordDTO;
use App\Domain\Auth\Repositories\AuthRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use App\Domain\Auth\Services\DjangoPasswordService;

class ChangePasswordUseCase
{
    public function __construct(
        private AuthRepositoryInterface $authRepository,
        private DjangoPasswordService $djangoPasswordService
    ) {
    }

    public function execute(string|int $accountId, ChangePasswordDTO $dto): array
    {
        // 1. Verify confirmation (Should be done in Request validation, but check here too)
        if ($dto->newPassword !== $dto->newPasswordConfirmation) {
            throw new RuntimeException("Mật khẩu xác nhận không khớp");
        }

        // 2. Verify old password
        if (!$this->authRepository->validatePassword($accountId, $dto->oldPassword)) {
            throw new RuntimeException("Mật khẩu hiện tại không đúng");
        }

        // 3. Update password
        $this->authRepository->updatePassword($accountId, Hash::make($dto->newPassword));

        return [
            'isSuccess' => true,
            'message' => 'Đổi mật khẩu thành công',
            'status_code' => 200
        ];
    }
}
