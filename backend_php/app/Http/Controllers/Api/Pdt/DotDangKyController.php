<?php

namespace App\Http\Controllers\Api\Pdt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Application\Pdt\DTOs\UpdateDotDangKyDTO;
use App\Application\Pdt\UseCases\GetDotDangKyListUseCase;
use App\Application\Pdt\UseCases\GetDotDangKyByHocKyUseCase;
use App\Application\Pdt\UseCases\UpdateDotDangKyUseCase;

/**
 * DotDangKyController - Quản lý đợt đăng ký (Refactored - Clean Architecture)
 * 
 * Thin controller - delegates business logic to UseCases
 */
class DotDangKyController extends Controller
{
    public function __construct(
        private GetDotDangKyListUseCase $getDotDangKyListUseCase,
        private GetDotDangKyByHocKyUseCase $getDotDangKyByHocKyUseCase,
        private UpdateDotDangKyUseCase $updateDotDangKyUseCase,
    ) {
    }

    /**
     * GET /api/pdt/dot-dang-ky
     * Get all registration periods (with optional hoc_ky_id filter)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $hocKyId = $request->query('hoc_ky_id') ?? $request->query('hocKyId');
            $result = $this->getDotDangKyListUseCase->execute($hocKyId);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/pdt/dot-dang-ky/{hocKyId}
     * Get registration periods by semester
     */
    public function getByHocKy(Request $request, $hocKyId): JsonResponse
    {
        try {
            $result = $this->getDotDangKyByHocKyUseCase->execute($hocKyId);
            return response()->json($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/pdt/dot-ghi-danh/update
     * Update enrollment period
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $dto = UpdateDotDangKyDTO::fromRequest($request->all());
            $result = $this->updateDotDangKyUseCase->execute($dto);
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
        } catch (\Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }
}
