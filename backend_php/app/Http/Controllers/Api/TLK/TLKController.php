<?php

namespace App\Http\Controllers\Api\TLK;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Application\TLK\UseCases\GetMonHocByKhoaUseCase;
use App\Application\TLK\UseCases\GetGiangVienByKhoaUseCase;
use App\Application\TLK\UseCases\GetPhongHocByKhoaUseCase;
use App\Application\TLK\UseCases\GetDeXuatForTLKUseCase;
use App\Application\TLK\UseCases\CreateDeXuatUseCase;
use App\Application\TLK\UseCases\GetHocPhanForSemesterUseCase;
use App\Application\TLK\UseCases\CreateLopHocPhanWithTKBUseCase;
use App\Infrastructure\TLK\Persistence\Models\TroLyKhoa;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use App\Infrastructure\SinhVien\Persistence\Models\LopHocPhan;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * TLKController - Endpoints cho Trợ Lý Khoa (Refactored - Clean Architecture)
 * 
 * Thin controller - delegates business logic to UseCases
 */
class TLKController extends Controller
{
    public function __construct(
        private GetMonHocByKhoaUseCase $getMonHocUseCase,
        private GetGiangVienByKhoaUseCase $getGiangVienUseCase,
        private GetPhongHocByKhoaUseCase $getPhongHocUseCase,
        private GetDeXuatForTLKUseCase $getDeXuatUseCase,
        private CreateDeXuatUseCase $createDeXuatUseCase,
        private GetHocPhanForSemesterUseCase $getHocPhanForSemesterUseCase,
        private CreateLopHocPhanWithTKBUseCase $createLopWithTKBUseCase,
    ) {
    }

    /**
     * Get TroLyKhoa from JWT token
     */
    private function getTLKFromToken(): ?TroLyKhoa
    {
        $taiKhoan = JWTAuth::parseToken()->authenticate();
        $userProfile = UserProfile::where('tai_khoan_id', $taiKhoan->id)->first();

        if (!$userProfile) {
            return null;
        }

        return TroLyKhoa::with('khoa')->find($userProfile->id);
    }

    /**
     * GET /api/tlk/mon-hoc
     * Get courses for TLK's department
     */
    public function getMonHoc(Request $request): JsonResponse
    {
        try {
            $tlk = $this->getTLKFromToken();

            if (!$tlk) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin trợ lý khoa'
                ], 404);
            }

            $result = $this->getMonHocUseCase->execute($tlk->khoa_id);
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
     * GET /api/tlk/giang-vien
     * Get instructors for TLK's department
     */
    public function getGiangVien(Request $request): JsonResponse
    {
        try {
            $tlk = $this->getTLKFromToken();

            if (!$tlk) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin trợ lý khoa'
                ], 404);
            }

            $result = $this->getGiangVienUseCase->execute($tlk->khoa_id);
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
     * GET /api/tlk/phong-hoc
     * Get rooms for TLK's department
     */
    public function getPhongHoc(Request $request): JsonResponse
    {
        try {
            $tlk = $this->getTLKFromToken();

            if (!$tlk) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin trợ lý khoa'
                ], 404);
            }

            $result = $this->getPhongHocUseCase->execute($tlk->khoa_id, false);
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
     * GET /api/tlk/phong-hoc/available
     * Get available rooms for TLK's department
     */
    public function getAvailablePhongHoc(Request $request): JsonResponse
    {
        try {
            $tlk = $this->getTLKFromToken();

            if (!$tlk) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin trợ lý khoa'
                ], 404);
            }

            $result = $this->getPhongHocUseCase->execute($tlk->khoa_id, true);
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
     * GET /api/tlk/de-xuat-hoc-phan?hocKyId={id}
     * Get proposals for TLK's department
     */
    public function getDeXuatHocPhan(Request $request): JsonResponse
    {
        try {
            $hocKyId = $request->query('hocKyId') ?? $request->query('hoc_ky_id');

            $tlk = $this->getTLKFromToken();

            if (!$tlk) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin trợ lý khoa'
                ], 404);
            }

            $result = $this->getDeXuatUseCase->execute($tlk->khoa_id, $hocKyId);
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
     * POST /api/tlk/de-xuat-hoc-phan
     * Create a course proposal
     */
    public function createDeXuatHocPhan(Request $request): JsonResponse
    {
        try {
            $monHocId = $request->input('maHocPhan');
            $giangVienId = $request->input('maGiangVien');

            $tlk = $this->getTLKFromToken();

            if (!$tlk) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin trợ lý khoa'
                ], 404);
            }

            $result = $this->createDeXuatUseCase->execute($monHocId ?? '', $tlk->khoa_id, $tlk->id, $giangVienId);
            return response()->json($result);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => $e->getMessage()
            ], 400);
        } catch (\RuntimeException $e) {
            $code = str_contains($e->getMessage(), 'đã tồn tại') ? 409 : 404;
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => $e->getMessage()
            ], $code);
        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/tlk/lop-hoc-phan/get-hoc-phan/{hoc_ky_id}
     * Get HocPhan for semester
     */
    public function getHocPhanForSemester(Request $request, $hoc_ky_id): JsonResponse
    {
        try {
            $tlk = $this->getTLKFromToken();

            if (!$tlk) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin trợ lý khoa'
                ], 404);
            }

            $result = $this->getHocPhanForSemesterUseCase->execute($hoc_ky_id, $tlk->khoa_id);
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
     * POST /api/tlk/thoi-khoa-bieu/batch
     * Get TKB for multiple hoc phans
     */
    public function getTKBBatch(Request $request): JsonResponse
    {
        try {
            $maHocPhans = $request->input('maHocPhans', []);
            $hocKyId = $request->input('hocKyId');

            if (empty($maHocPhans)) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Danh sách mã học phần không được rỗng'
                ], 400);
            }

            if (!$hocKyId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Học kỳ ID không được rỗng'
                ], 400);
            }

            // Get LopHocPhan by HocPhan ma codes
            $lopHocPhans = LopHocPhan::with(['hocPhan.monHoc', 'lichHocDinhKys.phong', 'giangVien'])
                ->whereHas('hocPhan', function ($q) use ($hocKyId) {
                    $q->where('id_hoc_ky', $hocKyId);
                })
                ->whereHas('hocPhan.monHoc', function ($q) use ($maHocPhans) {
                    $q->whereIn('ma_mon', $maHocPhans);
                })
                ->get();

            // Group by MonHoc
            $result = [];
            foreach ($lopHocPhans as $lhp) {
                $monHoc = $lhp->hocPhan?->monHoc;
                $maMon = $monHoc?->ma_mon ?? 'UNKNOWN';

                if (!isset($result[$maMon])) {
                    $result[$maMon] = [
                        'maMon' => $maMon,
                        'tenMon' => $monHoc?->ten_mon ?? '',
                        'danhSachLop' => [],
                    ];
                }

                $result[$maMon]['danhSachLop'][] = [
                    'id' => $lhp->id,
                    'maLop' => $lhp->ma_lop,
                    'soLuongHienTai' => $lhp->so_luong_hien_tai ?? 0,
                    'soLuongToiDa' => $lhp->so_luong_toi_da ?? 50,
                    'giangVien' => $lhp->giangVien?->ho_ten ?? '',
                    'tkb' => $lhp->lichHocDinhKys->map(function ($lich) {
                        return [
                            'thu' => $lich->thu,
                            'tietBatDau' => $lich->tiet_bat_dau,
                            'tietKetThuc' => $lich->tiet_ket_thuc,
                            'phong' => $lich->phong?->ma_phong ?? 'TBA',
                        ];
                    }),
                ];
            }

            return response()->json([
                'isSuccess' => true,
                'data' => array_values($result),
                'message' => "Lấy TKB thành công"
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
     * POST /api/tlk/thoi-khoa-bieu
     * Create schedule for a hoc phan
     */
    public function xepThoiKhoaBieu(Request $request): JsonResponse
    {
        try {
            $maHocPhan = $request->input('maHocPhan') ?? '';
            $hocKyId = $request->input('hocKyId') ?? '';
            $giangVienId = $request->input('giangVienId');
            $danhSachLop = $request->input('danhSachLop', []);

            $tlk = $this->getTLKFromToken();

            if (!$tlk) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin trợ lý khoa'
                ], 404);
            }

            $result = $this->createLopWithTKBUseCase->execute($maHocPhan, $hocKyId, $giangVienId, $danhSachLop);
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
