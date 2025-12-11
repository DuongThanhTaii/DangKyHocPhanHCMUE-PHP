<?php

namespace App\Application\Auth\UseCases;

use App\Application\Auth\DTOs\ResetPasswordDTO;
use App\Domain\Auth\Repositories\AuthRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Carbon\Carbon;

class ResetPasswordUseCase
{
    public function __construct(
        private AuthRepositoryInterface $authRepository
    ) {
    }

    public function execute(ResetPasswordDTO $dto): array
    {
        // 1. Find Token
        $record = DB::table('password_reset_tokens')->where('email', $dto->email)->first();

        if (!$record) {
            throw new RuntimeException("Yêu cầu đặt lại mật khẩu không hợp lệ.");
        }

        // 2. Check Expiry (60 mins default)
        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $dto->email)->delete();
            throw new RuntimeException("Link đặt lại mật khẩu đã hết hạn.");
        }

        // 3. Check Token match
        if (!Hash::check($dto->token, $record->token)) {
            throw new RuntimeException("Token không hợp lệ.");
        }

        // 4. Check Password Confirmation
        if ($dto->password !== $dto->passwordConfirmation) {
            throw new RuntimeException("Mật khẩu xác nhận không khớp.");
        }

        // 5. Update Password
        $account = $this->authRepository->findAccountByEmail($dto->email);
        if (!$account) {
            throw new RuntimeException("Tài khoản không tồn tại.");
        }

        $this->authRepository->updatePassword($account->id, Hash::make($dto->password));

        // 6. Delete Token
        DB::table('password_reset_tokens')->where('email', $dto->email)->delete();

        return [
            'isSuccess' => true,
            'message' => 'Đặt lại mật khẩu thành công.',
            'status_code' => 200
        ];
    }
}
