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
        // 1. Map Request to DTO
        $dto = LoginDTO::fromRequest($request->validated());

        // 2. Execute Use Case
        // Note: In real Laravel, we'd rely on Service Provider to inject the Repo into UseCase.
        // For this generated code to work 'conceptually' without binding:
        // $result = $this->loginUseCase->execute($dto);

        // Since we didn't set up the ServiceProvider binding yet, this would fail in runtime 
        // if user doesn't bind IAuthRepository -> EloquentAuthRepository.
        // We will assume the user (or next step) handles bindings.

        $result = $this->loginUseCase->execute($dto);

        // 3. Return Response
        return response()->json($result, $result['status_code']);
    }
}
