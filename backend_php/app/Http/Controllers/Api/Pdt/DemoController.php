<?php

namespace App\Http\Controllers\Api\Pdt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Application\Pdt\DTOs\TogglePhaseDTO;
use App\Application\Pdt\UseCases\TogglePhaseUseCase;

/**
 * DemoController - Quản lý demo/test (Refactored - Clean Architecture)
 * 
 * Thin controller - delegates business logic to UseCases
 */
class DemoController extends Controller
{
    public function __construct(
        private TogglePhaseUseCase $togglePhaseUseCase,
    ) {
    }

    /**
     * POST /api/pdt/demo/toggle-phase
     * POST /api/pdt/ky-phase/toggle (alias)
     * Toggle phase enabled status
     */
    public function togglePhase(Request $request): JsonResponse
    {
        try {
            $dto = TogglePhaseDTO::fromRequest($request->all());
            $result = $this->togglePhaseUseCase->execute($dto);
            return response()->json($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => $e->getMessage()
            ], 400);
        } catch (\RuntimeException $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => $e->getMessage()
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/pdt/demo/reset-data
     * Reset demo data - dangerous operation
     */
    public function resetData(Request $request): JsonResponse
    {
        try {
            // This is a demo/testing endpoint
            // In production, this should be protected or removed
            return response()->json([
                'isSuccess' => true,
                'data' => null,
                'message' => 'Reset data is disabled in this implementation'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }
}
