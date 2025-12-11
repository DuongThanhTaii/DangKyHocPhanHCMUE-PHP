<?php

namespace App\Application\Auth\UseCases;

use App\Application\Auth\DTOs\LoginDTO;
use App\Domain\Auth\Repositories\AuthRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use RuntimeException;

class LoginUseCase
{
    public function __construct(
        private AuthRepositoryInterface $authRepository,
        private \App\Domain\Auth\Services\DjangoPasswordService $djangoPasswordService
    ) {
    }

    public function execute(LoginDTO $dto): array
    {
        // 1. Tìm tài khoản theo tên đăng nhập
        $account = $this->authRepository->findAccountByUsername($dto->tenDangNhap);

        if (!$account) {
            throw new RuntimeException('Tên đăng nhập hoặc mật khẩu không đúng');
        }

        // 2. Check mật khẩu
        $isLaravelPass = false;
        try {
            // Ưu tiên check Laravel hash (Bcrypt/Argon2)
            if (Hash::check($dto->matKhau, $account->mat_khau)) {
                $isLaravelPass = true;
            }
        } catch (\RuntimeException $e) {
            // Hash không phải format của Laravel -> bỏ qua để check tiếp kiểu khác
        }

        if ($isLaravelPass) {
            // OK, pass
        }
        // Nếu fail, check xem có phải hash cũ của Django không
        elseif ($this->djangoPasswordService->check($dto->matKhau, $account->mat_khau)) {
            // Đúng là pass cũ -> Rehash sang chuẩn mới của Laravel
            $account->mat_khau = Hash::make($dto->matKhau);
            $account->save(); // Lưu lại luôn để lần sau login nhanh hơn
        } else {
            // Cả 2 đều sai
            throw new RuntimeException('Tên đăng nhập hoặc mật khẩu không đúng');
        }

        // 3. Sinh JWT (sub bây giờ là uuid)
        $token = JWTAuth::fromUser($account);

        // Nếu em dùng refresh token riêng:
        $refreshToken = JWTAuth::claims(['typ' => 'refresh'])->fromUser($account);

        // 4. Build thông tin user trả về
        $userEntity = $this->authRepository->buildAuthUserFromAccount($account);

        return [
            'isSuccess' => true,
            'message' => 'Đăng nhập thành công',
            'data' => [
                'token' => $token,
                'refreshToken' => $refreshToken,
                'user' => $userEntity->toArray(),
            ],
            'status_code' => 200,
        ];
    }
}
