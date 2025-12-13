<?php

namespace App\Http\Controllers\Api\Pdt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Application\Pdt\DTOs\CreateDeXuatHocPhanDTO;
use App\Application\Pdt\UseCases\GetDeXuatHocPhanListUseCase;
use App\Application\Pdt\UseCases\CreateDeXuatHocPhanUseCase;
use App\Application\Pdt\UseCases\ApproveDeXuatHocPhanUseCase;
use App\Application\Pdt\UseCases\RejectDeXuatHocPhanUseCase;

/**
 * DeXuatHocPhanController - Quản lý đề xuất học phần (Refactored - Clean Architecture)
 * 
 * Thin controller - delegates business logic to UseCases
 */
class DeXuatHocPhanController extends Controller
{
    public function __construct(
        private GetDeXuatHocPhanListUseCase $getListUseCase,
        private CreateDeXuatHocPhanUseCase $createUseCase,
        private ApproveDeXuatHocPhanUseCase $approveUseCase,
        private RejectDeXuatHocPhanUseCase $rejectUseCase,
    ) {
    }

    /**
     * GET /api/pdt/de-xuat-hoc-phan
     * Get course proposals that TK has approved (waiting for PDT approval)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $result = $this->getListUseCase->execute();
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
     * POST /api/pdt/de-xuat-hoc-phan
     * Create course proposal (PDT can also create)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $dto = CreateDeXuatHocPhanDTO::fromRequest($request->all());
            $result = $this->createUseCase->execute($dto);
            return response()->json($result, 201);
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
     * POST /api/pdt/de-xuat-hoc-phan/duyet
     * Approve course proposal
     */
    public function duyet(Request $request): JsonResponse
    {
        try {
            $deXuatId = $request->input('id') ?? '';
            $result = $this->approveUseCase->execute($deXuatId);
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
     * POST /api/pdt/de-xuat-hoc-phan/tu-choi
     * Reject course proposal
     */
    public function tuChoi(Request $request): JsonResponse
    {
        try {
            $deXuatId = $request->input('id') ?? '';
            $lyDo = $request->input('lyDo') ?? $request->input('ly_do') ?? '';
            $result = $this->rejectUseCase->execute($deXuatId, $lyDo);
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
}
