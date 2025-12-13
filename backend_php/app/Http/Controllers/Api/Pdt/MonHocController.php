<?php

namespace App\Http\Controllers\Api\Pdt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Application\Pdt\DTOs\CreateMonHocDTO;
use App\Application\Pdt\DTOs\UpdateMonHocDTO;
use App\Application\Pdt\UseCases\GetMonHocListUseCase;
use App\Application\Pdt\UseCases\CreateMonHocUseCase;
use App\Application\Pdt\UseCases\UpdateMonHocUseCase;
use App\Application\Pdt\UseCases\DeleteMonHocUseCase;

/**
 * MonHocController - Quản lý môn học (Refactored - Clean Architecture)
 * 
 * Thin controller - delegates business logic to UseCases
 */
class MonHocController extends Controller
{
    public function __construct(
        private GetMonHocListUseCase $getListUseCase,
        private CreateMonHocUseCase $createUseCase,
        private UpdateMonHocUseCase $updateUseCase,
        private DeleteMonHocUseCase $deleteUseCase,
    ) {
    }

    /**
     * GET /api/pdt/mon-hoc
     * Get all courses
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
     * POST /api/pdt/mon-hoc
     * Create course
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $dto = CreateMonHocDTO::fromRequest($request->all());
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
     * PUT /api/pdt/mon-hoc/{id}
     * Update course
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $dto = UpdateMonHocDTO::fromRequest($request->all());
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
     * DELETE /api/pdt/mon-hoc/{id}
     * Delete course
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
