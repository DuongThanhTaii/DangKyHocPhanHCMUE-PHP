<?php

namespace App\Http\Controllers\Api\Pdt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Application\Pdt\DTOs\CreateBulkKyPhaseDTO;
use App\Application\Pdt\UseCases\SetHocKyHienHanhUseCase;
use App\Application\Pdt\UseCases\CreateBulkKyPhaseUseCase;
use App\Application\Pdt\UseCases\GetPhasesByHocKyUseCase;

/**
 * HocKyController - Quản lý học kỳ (Refactored - Clean Architecture)
 * 
 * Thin controller - delegates business logic to UseCases
 */
class HocKyController extends Controller
{
    public function __construct(
        private SetHocKyHienHanhUseCase $setHocKyHienHanhUseCase,
        private CreateBulkKyPhaseUseCase $createBulkKyPhaseUseCase,
        private GetPhasesByHocKyUseCase $getPhasesByHocKyUseCase,
    ) {
    }

    /**
     * POST /api/pdt/quan-ly-hoc-ky/hoc-ky-hien-hanh
     * Set current semester
     */
    public function setHocKyHienHanh(Request $request): JsonResponse
    {
        try {
            $hocKyId = $request->input('hocKyId') ?? $request->input('hoc_ky_id') ?? '';
            $result = $this->setHocKyHienHanhUseCase->execute($hocKyId);
            return response()->json($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => $e->getMessage(),
                'errorCode' => 'MISSING_PARAMS'
            ], 400);
        } catch (\RuntimeException $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => $e->getMessage(),
                'errorCode' => 'NOT_FOUND'
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
     * POST /api/pdt/quan-ly-hoc-ky/ky-phase/bulk
     * Create semester phases
     */
    public function createBulkKyPhase(Request $request): JsonResponse
    {
        try {
            $dto = CreateBulkKyPhaseDTO::fromRequest($request->all());
            $result = $this->createBulkKyPhaseUseCase->execute($dto);
            return response()->json($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => $e->getMessage(),
                'errorCode' => 'INVALID_PARAMS'
            ], 400);
        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/pdt/quan-ly-hoc-ky/ky-phase/{hocKyId}
     * Get phases by semester
     */
    public function getPhasesByHocKy(Request $request, $hocKyId): JsonResponse
    {
        try {
            $result = $this->getPhasesByHocKyUseCase->execute($hocKyId ?? '');
            return response()->json($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }
}
