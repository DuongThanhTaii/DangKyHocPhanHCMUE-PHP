<?php

namespace App\Http\Controllers\Api\Pdt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Application\Pdt\DTOs\CreateGiangVienDTO;
use App\Application\Pdt\DTOs\UpdateGiangVienDTO;
use App\Application\Pdt\UseCases\GetGiangVienListUseCase;
use App\Application\Pdt\UseCases\CreateGiangVienUseCase;
use App\Application\Pdt\UseCases\UpdateGiangVienUseCase;
use App\Application\Pdt\UseCases\DeleteGiangVienUseCase;

/**
 * GiangVienController - Quản lý giảng viên (Refactored - Clean Architecture)
 * 
 * Thin controller - delegates business logic to UseCases
 */
class GiangVienController extends Controller
{
    public function __construct(
        private GetGiangVienListUseCase $getListUseCase,
        private CreateGiangVienUseCase $createUseCase,
        private UpdateGiangVienUseCase $updateUseCase,
        private DeleteGiangVienUseCase $deleteUseCase,
    ) {
    }

    /**
     * GET /api/pdt/giang-vien
     * Get all instructors
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $page = (int) $request->query('page', 1);
            $pageSize = (int) $request->query('pageSize', 10000);

            $result = $this->getListUseCase->execute($page, $pageSize);
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
     * POST /api/pdt/giang-vien
     * Create instructor
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $dto = CreateGiangVienDTO::fromRequest($request->all());
            $result = $this->createUseCase->execute($dto);
            return response()->json($result, 201);
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
     * PUT /api/pdt/giang-vien/{id}
     * Update instructor
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $dto = UpdateGiangVienDTO::fromRequest($request->all());
            $result = $this->updateUseCase->execute($id, $dto);
            return response()->json($result);
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
     * DELETE /api/pdt/giang-vien/{id}
     * Delete instructor
     */
    public function destroy($id): JsonResponse
    {
        try {
            $result = $this->deleteUseCase->execute($id);
            return response()->json($result);
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
}
