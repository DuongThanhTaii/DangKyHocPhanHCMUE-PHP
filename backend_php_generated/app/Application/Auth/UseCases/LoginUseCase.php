<?php

namespace App\Application\Auth\UseCases;

use App\Domain\Auth\Repositories\IAuthRepository;
use App\Application\Auth\DTOs\LoginDTO;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginUseCase
{
    public function __construct(
        private IAuthRepository $authRepository
    ) {
    }

    /**
     * Execute login logic
     *
     * @param LoginDTO $dto
     * @return array Result with status and data
     */
    public function execute(LoginDTO $dto): array
    {
        // 1. Find account by username
        $account = $this->authRepository->findAccountByUsername($dto->tenDangNhap);

        if (!$account) {
            return [
                'isSuccess' => false,
                'message' => 'Tên đăng nhập hoặc mật khẩu không đúng',
                'status_code' => 401
            ];
        }

        // 2. Verify password (using Laravel Hash facade, assuming bcrypt)
        // Note: In strict clean architecture, Hash should be wrapped in IPasswordHasher, 
        // but using Facade here for pragmatism as per plan.
        if (!Hash::check($dto->matKhau, $account->mat_khau)) {
            return [
                'isSuccess' => false,
                'message' => 'Tên đăng nhập hoặc mật khẩu không đúng',
                'status_code' => 401
            ];
        }

        // 3. Check active status
        if (!$account->trang_thai_hoat_dong) {
            return [
                'isSuccess' => false,
                'message' => 'Tài khoản đã bị vô hiệu hóa',
                'status_code' => 403
            ];
        }

        // 4. Get User Info
        $userEntity = $this->authRepository->getUserByAccountId($account->id);

        if (!$userEntity) {
            return [
                'isSuccess' => false,
                'message' => 'Không tìm thấy thông tin người dùng',
                'status_code' => 404
            ];
        }

        // 5. Generate Token (using JWTAuth facade)
        // Note: Ideally wrapped in ITokenService
        // We need to create a Subject for JWT. Assuming User model implements JWTSubject.
        // Since we are decoupling, we might need a custom claim generation or reuse the Eloquent User.
        // For this UseCase, we'll assume we pass the Eloquent model to JWTAuth::fromUser
        // But $account is an object. We need to fetch the Eloquent model in Infrastructure, 
        // OR simply return success and let Controller handle token generation (not Clean).
        // BETTER: Inject ITokenService. For now, use JWTAuth facade assuming $account is authenticatable.

        // Let's assume $account IS an Eloquent model instance coming from Repository for now,
        // or we use custom claims.
        // Ideally: $token = $this->tokenService->generateToken($userEntity);

        // simplified for migration proof-of-concept:
        // We will generate token based on the account ID and claims.

        $customClaims = [
            'id' => $userEntity->id,
            'role' => $userEntity->loaiTaiKhoan
        ];

        // TODO: This requires JWTAuth setup in Infrastructure. 
        // Assuming we can use payload factory or similar.
        // For strictness, let's assume we use the facade to generate from a "User" object
        // casted from the repository result.

        // Placeholder for token generation logic relying on Infrastructure details
        // $token = JWTAuth::claims($customClaims)->fromUser($account); 
        // For now, returning dummy token to indicate logic flow until FrameWork is ready.
        $token = "mock_jwt_token_" . $userEntity->id;
        $refreshToken = "mock_refresh_token_" . $userEntity->id;

        return [
            'isSuccess' => true,
            'message' => 'Đăng nhập thành công',
            'data' => [
                'token' => $token,
                'refreshToken' => $refreshToken,
                'user' => $userEntity->toArray()
            ],
            'status_code' => 200
        ];
    }
}
