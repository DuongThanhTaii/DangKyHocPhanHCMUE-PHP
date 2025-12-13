<?php

namespace App\Http\Controllers\Api\Pdt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Application\Pdt\DTOs\BaoCaoFilterDTO;
use App\Application\Pdt\UseCases\GetBaoCaoOverviewUseCase;
use App\Application\Pdt\UseCases\GetDangKyTheoKhoaUseCase;
use App\Application\Pdt\UseCases\GetDangKyTheoNganhUseCase;
use App\Application\Pdt\UseCases\GetTaiGiangVienUseCase;

/**
 * BaoCaoController - Thống kê báo cáo (Refactored - Clean Architecture)
 * 
 * Thin controller - delegates business logic to UseCases
 * 
 * Endpoints:
 * - GET /bao-cao/overview         - Thống kê tổng quan
 * - GET /bao-cao/dk-theo-khoa     - Thống kê đăng ký theo khoa
 * - GET /bao-cao/dk-theo-nganh    - Thống kê đăng ký theo ngành
 * - GET /bao-cao/tai-giang-vien   - Thống kê tải giảng viên
 */
class BaoCaoController extends Controller
{
    public function __construct(
        private GetBaoCaoOverviewUseCase $getBaoCaoOverviewUseCase,
        private GetDangKyTheoKhoaUseCase $getDangKyTheoKhoaUseCase,
        private GetDangKyTheoNganhUseCase $getDangKyTheoNganhUseCase,
        private GetTaiGiangVienUseCase $getTaiGiangVienUseCase,
    ) {
    }

    /**
     * GET /bao-cao/overview
     * 
     * Thống kê tổng quan: SV unique, số đăng ký, số LHP, tài chính
     */
    public function overview(Request $request): JsonResponse
    {
        try {
            // 1. Map request to DTO
            $dto = BaoCaoFilterDTO::fromRequest($request->query());

            // 2. Execute use case
            $result = $this->getBaoCaoOverviewUseCase->execute($dto);

            // 3. Return response
            return response()->json($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /bao-cao/dk-theo-khoa
     * 
     * Thống kê số lượng đăng ký theo từng khoa
     */
    public function dangKyTheoKhoa(Request $request): JsonResponse
    {
        try {
            $dto = BaoCaoFilterDTO::fromRequest($request->query());
            $result = $this->getDangKyTheoKhoaUseCase->execute($dto);
            return response()->json($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /bao-cao/dk-theo-nganh
     * 
     * Thống kê số lượng đăng ký theo ngành
     */
    public function dangKyTheoNganh(Request $request): JsonResponse
    {
        try {
            $dto = BaoCaoFilterDTO::fromRequest($request->query());
            $result = $this->getDangKyTheoNganhUseCase->execute($dto);
            return response()->json($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /bao-cao/tai-giang-vien
     * 
     * Thống kê số lượng lớp học phần theo giảng viên
     */
    public function taiGiangVien(Request $request): JsonResponse
    {
        try {
            $dto = BaoCaoFilterDTO::fromRequest($request->query());
            $result = $this->getTaiGiangVienUseCase->execute($dto);
            return response()->json($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
