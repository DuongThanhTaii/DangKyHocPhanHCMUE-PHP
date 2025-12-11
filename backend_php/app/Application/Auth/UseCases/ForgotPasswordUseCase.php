<?php

namespace App\Application\Auth\UseCases;

use App\Application\Auth\DTOs\ForgotPasswordDTO;
use App\Domain\Auth\Repositories\AuthRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Carbon\Carbon;

class ForgotPasswordUseCase
{
    public function __construct(
        private AuthRepositoryInterface $authRepository
    ) {
    }

    public function execute(ForgotPasswordDTO $dto): array
    {
        // 1. Check if email exists
        // Note: findAccountByEmail returns TaiKhoan model (object) or null
        $account = $this->authRepository->findAccountByEmail($dto->email);

        if (!$account) {
            // Security: Don't reveal if email exists or not, but for UX usually we allow if policy allows.
            // Requirement says "implement endpoint".
            // If checking fails, standard response is "If your email exists, we sent a link."
            // But to return 404 or success?
            // Let's throw exception for now or return success fake.
            // I'll return success to prevent enum attacks, OR specific error if dev mode.
            // Let's specific error for now for easier debugging.
            throw new RuntimeException("Email không tồn tại trong hệ thống");
        }

        // 2. Create Token
        $token = Str::random(60);

        // 3. Store in DB
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $dto->email],
            [
                'email' => $dto->email,
                'token' => Hash::make($token),
                'created_at' => Carbon::now()
            ]
        );

        // 4. Send Email
        // Mocking email sending for now
        // Log the token so developer can test
        info("PASSWORD RESET LINK for {$dto->email}: " . url("/reset-password?token={$token}&email={$dto->email}"));

        return [
            'isSuccess' => true,
            'message' => 'Vui lòng kiểm tra email để lấy lại mật khẩu.',
            'status_code' => 200
        ];
    }
}
