<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Application\Auth\UseCases\LoginUseCase;
use App\Application\Auth\DTOs\LoginDTO;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private LoginUseCase $loginUseCase,
        private \App\Application\Auth\UseCases\ChangePasswordUseCase $changePasswordUseCase,
        private \App\Application\Auth\UseCases\ForgotPasswordUseCase $forgotPasswordUseCase,
        private \App\Application\Auth\UseCases\ResetPasswordUseCase $resetPasswordUseCase
    ) {
    }

    /**
     * Handle Login Request
     * 
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            // 1. Map Request to DTO
            $dto = LoginDTO::fromRequest($request->validated());

            // 2. Execute Use Case
            $result = $this->loginUseCase->execute($dto);

            // 3. Return Response
            return response()->json($result, $result['status_code']);
        } catch (\RuntimeException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => $e->getMessage(),
                'status_code' => 401
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Đã có lỗi xảy ra',
                'error' => $e->getMessage(),
                'status_code' => 500
            ], 500);
        }
    }

    public function logout(): JsonResponse
    {
        auth()->logout();
        return response()->json(['message' => 'Đăng xuất thành công']);
    }

    public function refresh(): JsonResponse
    {
        return response()->json([
            'token' => auth()->refresh(),
            'message' => 'Refresh token thành công'
        ]);
    }

    public function changePassword(\Illuminate\Http\Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'old_password' => 'required',
                'new_password' => 'required|min:6',
                'new_password_confirmation' => 'required|same:new_password',
            ]);

            $dto = \App\Application\Auth\DTOs\ChangePasswordDTO::fromRequest($data);
            $user = auth()->user(); // TaiKhoan model

            $result = $this->changePasswordUseCase->execute($user->id, $dto);

            return response()->json($result, $result['status_code']);

        } catch (\RuntimeException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => $e->getMessage(),
                'status_code' => 400
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Lỗi hệ thống',
                'error' => $e->getMessage(),
                'status_code' => 500
            ], 500);
        }
    }

    public function forgotPassword(\Illuminate\Http\Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'email' => 'required|email',
            ]);

            $dto = \App\Application\Auth\DTOs\ForgotPasswordDTO::fromRequest($data);
            $result = $this->forgotPasswordUseCase->execute($dto);

            return response()->json($result, $result['status_code']);

        } catch (\RuntimeException $e) {
            // Return 200 even if email not found? Or 400? UseCase throws exception.
            // If we want security, we catch it and return success message anyway?
            // But existing code returns error message.
            return response()->json([
                'isSuccess' => false,
                'message' => $e->getMessage(),
                'status_code' => 400
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Lỗi hệ thống',
                'error' => $e->getMessage(),
                'status_code' => 500
            ], 500);
        }
    }

    public function resetPassword(\Illuminate\Http\Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|min:6',
                'password_confirmation' => 'required|same:password',
            ]);

            $dto = \App\Application\Auth\DTOs\ResetPasswordDTO::fromRequest($data);
            $result = $this->resetPasswordUseCase->execute($dto);

            return response()->json($result, $result['status_code']);

        } catch (\RuntimeException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => $e->getMessage(),
                'status_code' => 400
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Lỗi hệ thống',
                'error' => $e->getMessage(),
                'status_code' => 500
            ], 500);
        }
    }
}
