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
        private LoginUseCase $loginUseCase
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
}
