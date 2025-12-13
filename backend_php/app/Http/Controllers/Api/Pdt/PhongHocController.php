<?php

namespace App\Http\Controllers\Api\Pdt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Application\Pdt\DTOs\AssignPhongHocDTO;
use App\Application\Pdt\DTOs\UnassignPhongHocDTO;
use App\Application\Pdt\UseCases\GetAvailablePhongHocUseCase;
use App\Application\Pdt\UseCases\GetPhongHocByKhoaUseCase;
use App\Application\Pdt\UseCases\AssignPhongHocUseCase;
use App\Application\Pdt\UseCases\UnassignPhongHocUseCase;

/**
 * PhongHocController - Quản lý phòng học (Refactored - Clean Architecture)
 * 
 * Thin controller - delegates business logic to UseCases
 */
class PhongHocController extends Controller
{
    public function __construct(
        private GetAvailablePhongHocUseCase $getAvailableUseCase,
        private GetPhongHocByKhoaUseCase $getByKhoaUseCase,
        private AssignPhongHocUseCase $assignUseCase,
        private UnassignPhongHocUseCase $unassignUseCase,
    ) {
    }

    /**
     * GET /api/pdt/phong-hoc/available
     * Get available rooms (not assigned to any khoa)
     */
    public function available(Request $request): JsonResponse
    {
        try {
            $result = $this->getAvailableUseCase->execute();
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/pdt/phong-hoc/khoa/{khoaId}
     * Get rooms by department
     */
    public function byKhoa(Request $request, $khoaId): JsonResponse
    {
        try {
            $result = $this->getByKhoaUseCase->execute($khoaId);
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

    /**
     * POST /api/pdt/phong-hoc/assign
     * Assign room(s) to department
     */
    public function assign(Request $request): JsonResponse
    {
        try {
            $dto = AssignPhongHocDTO::fromRequest($request->all());
            $result = $this->assignUseCase->execute($dto);
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

    /**
     * POST /api/pdt/phong-hoc/unassign
     * Unassign room(s) from department
     */
    public function unassign(Request $request): JsonResponse
    {
        try {
            $dto = UnassignPhongHocDTO::fromRequest($request->all());
            $result = $this->unassignUseCase->execute($dto);
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
