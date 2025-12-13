<?php

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Application\Common\UseCases\GetCurrentHocKyUseCase;
use App\Application\Common\UseCases\GetHocKyNienKhoaListUseCase;
use App\Application\Common\UseCases\GetNganhListUseCase;
use App\Application\Common\UseCases\GetNganhWithoutPolicyUseCase;
use App\Domain\Pdt\Repositories\KhoaRepositoryInterface;

/**
 * CommonController - Endpoints chung (Refactored - Clean Architecture)
 * 
 * Thin controller - delegates business logic to UseCases
 */
class CommonController extends Controller
{
    public function __construct(
        private GetCurrentHocKyUseCase $getCurrentHocKyUseCase,
        private GetHocKyNienKhoaListUseCase $getHocKyNienKhoaListUseCase,
        private GetNganhListUseCase $getNganhListUseCase,
        private GetNganhWithoutPolicyUseCase $getNganhWithoutPolicyUseCase,
        private KhoaRepositoryInterface $khoaRepository,
    ) {
    }

    /**
     * GET /api/hoc-ky-hien-hanh
     * GET /api/hien-hanh (alias)
     * Get the current active semester
     */
    public function getHocKyHienHanh(): JsonResponse
    {
        try {
            $result = $this->getCurrentHocKyUseCase->execute();
            $statusCode = $result['data'] === null ? 404 : 200;
            return response()->json($result, $statusCode);
        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => 'Lỗi khi lấy học kỳ hiện hành: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/hoc-ky-nien-khoa
     * Get all semesters grouped by academic years
     */
    public function getHocKyNienKhoa(): JsonResponse
    {
        try {
            $result = $this->getHocKyNienKhoaListUseCase->execute();
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
     * GET /api/dm/khoa
     * Get list of departments/faculties
     */
    public function getDanhSachKhoa(): JsonResponse
    {
        try {
            $khoas = $this->khoaRepository->getAll();

            $data = $khoas->map(function ($k) {
                return [
                    'id' => $k->id,
                    'maKhoa' => $k->ma_khoa,
                    'tenKhoa' => $k->ten_khoa
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => "Lấy thành công {$data->count()} khoa"
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/dm/nganh?khoa_id={id}
     * Get list of programs/majors
     */
    public function getDanhSachNganh(Request $request): JsonResponse
    {
        try {
            $khoaId = $request->query('khoa_id');
            $result = $this->getNganhListUseCase->execute($khoaId);
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
     * GET /api/hoc-ky/dates
     * Get dates info for the current semester
     */
    public function getHocKyDates(): JsonResponse
    {
        try {
            $result = $this->getCurrentHocKyUseCase->execute();
            $statusCode = $result['data'] === null ? 404 : 200;
            return response()->json($result, $statusCode);
        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/dm/nganh/chua-co-chinh-sach?hoc_ky_id={}&khoa_id={}
     * Get list of specializations that don't have credit policy
     */
    public function getNganhChuaCoChinhSach(Request $request): JsonResponse
    {
        try {
            $hocKyId = $request->query('hoc_ky_id') ?? '';
            $khoaId = $request->query('khoa_id') ?? '';

            $result = $this->getNganhWithoutPolicyUseCase->execute($hocKyId, $khoaId);
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
     * GET /api/config/tiet-hoc
     * Get configuration for class periods (lesson times)
     */
    public function getConfigTietHoc(): JsonResponse
    {
        // Static configuration - no need for UseCase
        $tietHocConfig = [
            ['tiet' => 1, 'start' => '07:00', 'end' => '07:50'],
            ['tiet' => 2, 'start' => '07:50', 'end' => '08:40'],
            ['tiet' => 3, 'start' => '09:00', 'end' => '09:50'],
            ['tiet' => 4, 'start' => '09:50', 'end' => '10:40'],
            ['tiet' => 5, 'start' => '10:40', 'end' => '11:30'],
            ['tiet' => 6, 'start' => '13:00', 'end' => '13:50'],
            ['tiet' => 7, 'start' => '13:50', 'end' => '14:40'],
            ['tiet' => 8, 'start' => '15:00', 'end' => '15:50'],
            ['tiet' => 9, 'start' => '15:50', 'end' => '16:40'],
            ['tiet' => 10, 'start' => '16:40', 'end' => '17:30'],
            ['tiet' => 11, 'start' => '17:40', 'end' => '18:30'],
            ['tiet' => 12, 'start' => '18:30', 'end' => '19:20'],
            ['tiet' => 13, 'start' => '19:20', 'end' => '20:10'],
            ['tiet' => 14, 'start' => '20:10', 'end' => '21:00'],
            ['tiet' => 15, 'start' => '21:00', 'end' => '21:50'],
        ];

        return response()->json([
            'success' => true,
            'data' => $tietHocConfig
        ]);
    }
}
