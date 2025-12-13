<?php

namespace App\Http\Controllers\Api\Pdt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Application\Pdt\UseCases\TinhToanHocPhiHangLoatUseCase;

/**
 * HocPhiController - Quản lý học phí (Refactored - Clean Architecture)
 * 
 * Thin controller - delegates business logic to UseCases
 */
class HocPhiController extends Controller
{
    public function __construct(
        private TinhToanHocPhiHangLoatUseCase $tinhToanUseCase,
    ) {
    }

    /**
     * POST /api/pdt/hoc-phi/tinh-toan-hang-loat
     * Calculate tuition for all students in a semester
     */
    public function tinhToanHangLoat(Request $request): JsonResponse
    {
        try {
            $hocKyId = $request->input('hocKyId') ?? $request->input('hoc_ky_id') ?? '';
            $result = $this->tinhToanUseCase->execute($hocKyId);
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
