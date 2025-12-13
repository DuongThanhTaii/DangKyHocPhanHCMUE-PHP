<?php

namespace App\Http\Controllers\Api\SinhVien;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Application\SinhVien\UseCases\CheckRegistrationPhaseUseCase;
use App\Application\SinhVien\UseCases\GetAvailableClassesUseCase;
use App\Application\SinhVien\UseCases\GetRegisteredClassesUseCase;
use App\Application\SinhVien\UseCases\GetRegistrationHistoryUseCase;
use App\Application\SinhVien\UseCases\GetWeeklyScheduleUseCase;
use App\Application\SinhVien\UseCases\SearchOpenCoursesUseCase;
use App\Domain\SinhVien\Repositories\DangKyHocPhanRepositoryInterface;
use App\Infrastructure\SinhVien\Persistence\Models\SinhVien;
use App\Infrastructure\SinhVien\Persistence\Models\LopHocPhan;
use App\Infrastructure\Common\Persistence\Models\HocKy;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use App\Infrastructure\Redis\Services\RedisLockService;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * DangKyHocPhanController - Course Registration (Refactored - Clean Architecture)
 * 
 * Thin controller - delegates to UseCases where possible
 */
class DangKyHocPhanController extends Controller
{
    public function __construct(
        private RedisLockService $lockService,
        private DangKyHocPhanRepositoryInterface $repository,
        private CheckRegistrationPhaseUseCase $checkPhaseUseCase,
        private GetAvailableClassesUseCase $getAvailableUseCase,
        private GetRegisteredClassesUseCase $getRegisteredUseCase,
        private GetRegistrationHistoryUseCase $getHistoryUseCase,
        private GetWeeklyScheduleUseCase $getScheduleUseCase,
        private SearchOpenCoursesUseCase $searchCoursesUseCase,
    ) {
    }

    /**
     * Get SinhVien from JWT token
     */
    private function getSinhVienFromToken(): ?SinhVien
    {
        $taiKhoan = JWTAuth::parseToken()->authenticate();
        $userProfile = UserProfile::where('tai_khoan_id', $taiKhoan->id)->first();

        if (!$userProfile) {
            return null;
        }

        return SinhVien::find($userProfile->id);
    }

    /**
     * GET /api/sv/check-phase-dang-ky?hocKyId={id}
     */
    public function checkPhaseDangKy(Request $request): JsonResponse
    {
        try {
            $hocKyId = $request->query('hocKyId') ?? $request->query('hoc_ky_id') ?? '';
            $result = $this->checkPhaseUseCase->execute($hocKyId);

            if (!$result['isValid']) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => $result['phase'] ? ['phase' => $result['phase']] : null,
                    'message' => $result['message']
                ], 400);
            }

            return response()->json([
                'isSuccess' => true,
                'data' => [
                    'phase' => $result['phase'],
                    'startAt' => $result['startAt'],
                    'endAt' => $result['endAt'],
                ],
                'message' => $result['message']
            ]);

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
     * GET /api/sv/lop-hoc-phan?hocKyId={id}
     */
    public function getLopHocPhan(Request $request): JsonResponse
    {
        try {
            $hocKyId = $request->query('hocKyId') ?? $request->query('hoc_ky_id');

            if (!$hocKyId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thiếu học kỳ ID'
                ], 400);
            }

            $sinhVien = $this->getSinhVienFromToken();
            if (!$sinhVien) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin sinh viên'
                ], 404);
            }

            $result = $this->getAvailableUseCase->execute($sinhVien->id, $hocKyId);
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
     * GET /api/sv/lop-da-dang-ky?hocKyId={id}
     */
    public function getLopDaDangKy(Request $request): JsonResponse
    {
        try {
            $hocKyId = $request->query('hocKyId') ?? $request->query('hoc_ky_id');

            if (!$hocKyId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thiếu học kỳ ID'
                ], 400);
            }

            $sinhVien = $this->getSinhVienFromToken();
            if (!$sinhVien) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin sinh viên'
                ], 404);
            }

            $result = $this->getRegisteredUseCase->execute($sinhVien->id, $hocKyId);
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
     * POST /api/sv/dang-ky-hoc-phan
     * Register for a course class (with Redis lock for race condition prevention)
     */
    public function dangKyHocPhan(Request $request): JsonResponse
    {
        try {
            $lopHocPhanId = $request->input('lopHocPhanId');
            $hocKyId = $request->input('hocKyId');

            if (!$lopHocPhanId || !$hocKyId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thiếu thông tin đăng ký (lopHocPhanId, hocKyId)'
                ], 400);
            }

            $sinhVien = $this->getSinhVienFromToken();
            if (!$sinhVien) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Sinh viên không tồn tại'
                ], 404);
            }

            // Check Phase
            $phaseResult = $this->checkPhaseUseCase->execute($hocKyId);
            if (!$phaseResult['isValid']) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không trong thời gian đăng ký học phần'
                ], 400);
            }

            // Get Class Info
            $lhp = $this->repository->findLopHocPhan($lopHocPhanId);
            if (!$lhp) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Lớp học phần không tồn tại'
                ], 404);
            }

            // Check if already registered for this subject
            $monHocId = $lhp->hocPhan?->mon_hoc_id;
            if ($monHocId && $this->repository->hasRegisteredForSubject($sinhVien->id, $monHocId, $hocKyId)) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Sinh viên đã đăng ký môn học này trong học kỳ'
                ], 400);
            }

            // Check Time Conflict
            $newSchedules = $lhp->lichHocDinhKys;
            $registeredClasses = $this->repository->getRegisteredClasses($sinhVien->id, $hocKyId);

            foreach ($registeredClasses as $reg) {
                $existingSchedules = $reg->lopHocPhan?->lichHocDinhKys ?? collect();
                if ($this->checkTimeConflict($newSchedules, $existingSchedules)) {
                    return response()->json([
                        'isSuccess' => false,
                        'data' => null,
                        'message' => 'Trùng lịch học với lớp ' . $reg->lopHocPhan?->ma_lop
                    ], 400);
                }
            }

            // Critical Section - Use Redis Lock
            $lockKey = "dkhp:lop:{$lopHocPhanId}";

            return $this->lockService->withLock($lockKey, function () use ($sinhVien, $lopHocPhanId, $hocKyId) {
                // Re-fetch class for latest count
                $lhp = LopHocPhan::find($lopHocPhanId);
                $currentCount = $lhp->so_luong_hien_tai ?? 0;
                $maxCount = $lhp->so_luong_toi_da ?? 50;

                if ($currentCount >= $maxCount) {
                    return response()->json([
                        'isSuccess' => false,
                        'data' => null,
                        'message' => 'Lớp học phần đã đầy'
                    ], 400);
                }

                // Double-check registration
                if ($this->repository->hasRegisteredForClass($sinhVien->id, $lopHocPhanId)) {
                    return response()->json([
                        'isSuccess' => false,
                        'data' => null,
                        'message' => 'Bạn đã đăng ký lớp học phần này rồi'
                    ], 400);
                }

                // Perform Registration
                DB::transaction(function () use ($sinhVien, $lopHocPhanId, $hocKyId) {
                    $dangKy = $this->repository->createRegistration($sinhVien->id, $lopHocPhanId);
                    $this->repository->incrementClassCount($lopHocPhanId);
                    $this->repository->logRegistrationAction($sinhVien->id, $hocKyId, $dangKy->id, 'dang_ky');
                });

                return response()->json([
                    'isSuccess' => true,
                    'data' => null,
                    'message' => 'Đăng ký học phần thành công'
                ]);
            });

        } catch (\RuntimeException $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => $e->getMessage()
            ], 503);
        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/sv/huy-dang-ky-hoc-phan
     */
    public function huyDangKyHocPhan(Request $request): JsonResponse
    {
        try {
            $lopHocPhanId = $request->input('lopHocPhanId') ?? $request->input('lop_hoc_phan_id');
            $hocKyId = $request->input('hocKyId') ?? $request->input('hoc_ky_id');

            if (!$lopHocPhanId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thiếu lopHocPhanId'
                ], 400);
            }

            $sinhVien = $this->getSinhVienFromToken();
            if (!$sinhVien) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Sinh viên không tồn tại'
                ], 404);
            }

            // Get hocKyId if not provided
            if (!$hocKyId) {
                $hocKy = HocKy::where('trang_thai_hien_tai', true)->first();
                if (!$hocKy) {
                    return response()->json([
                        'isSuccess' => false,
                        'data' => null,
                        'message' => 'Không tìm thấy học kỳ hiện hành'
                    ], 400);
                }
                $hocKyId = $hocKy->id;
            }

            // Check Phase
            $phaseResult = $this->checkPhaseUseCase->execute($hocKyId);
            if (!$phaseResult['isValid']) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không trong thời gian đăng ký học phần'
                ], 400);
            }

            // Find Registration
            $dangKy = $this->repository->findRegistration($sinhVien->id, $lopHocPhanId);
            if (!$dangKy) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Chưa đăng ký lớp học phần này'
                ], 404);
            }

            if ($dangKy->trang_thai !== 'da_dang_ky') {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không thể hủy đăng ký với trạng thái: ' . $dangKy->trang_thai
                ], 400);
            }

            // Perform Cancellation
            DB::transaction(function () use ($sinhVien, $lopHocPhanId, $hocKyId, $dangKy) {
                $this->repository->logRegistrationAction($sinhVien->id, $hocKyId, $dangKy->id, 'huy_dang_ky');
                $this->repository->deleteRegistration($dangKy);
                $this->repository->decrementClassCount($lopHocPhanId);
            });

            return response()->json([
                'isSuccess' => true,
                'data' => null,
                'message' => 'Hủy đăng ký học phần thành công'
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
     * POST /api/sv/chuyen-lop-hoc-phan
     */
    public function chuyenLopHocPhan(Request $request): JsonResponse
    {
        try {
            $lopCuId = $request->input('lopCuId') ?? $request->input('lop_hoc_phan_id_cu');
            $lopMoiId = $request->input('lopMoiId') ?? $request->input('lop_hoc_phan_id_moi');
            $hocKyId = $request->input('hocKyId') ?? $request->input('hoc_ky_id');

            if (!$lopCuId || !$lopMoiId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thiếu thông tin chuyển lớp (lopCuId, lopMoiId)'
                ], 400);
            }

            $sinhVien = $this->getSinhVienFromToken();
            if (!$sinhVien) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Sinh viên không tồn tại'
                ], 404);
            }

            if (!$hocKyId) {
                $hocKy = HocKy::where('trang_thai_hien_tai', true)->first();
                if (!$hocKy) {
                    return response()->json([
                        'isSuccess' => false,
                        'data' => null,
                        'message' => 'Không tìm thấy học kỳ hiện hành'
                    ], 400);
                }
                $hocKyId = $hocKy->id;
            }

            // Check Phase
            $phaseResult = $this->checkPhaseUseCase->execute($hocKyId);
            if (!$phaseResult['isValid']) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không trong thời gian đăng ký học phần'
                ], 400);
            }

            // Get new class
            $lopMoi = $this->repository->findLopHocPhan($lopMoiId);
            if (!$lopMoi) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Lớp mới không tồn tại'
                ], 404);
            }

            // Check capacity
            if (($lopMoi->so_luong_hien_tai ?? 0) >= ($lopMoi->so_luong_toi_da ?? 50)) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Lớp mới đã đầy'
                ], 400);
            }

            // Find existing registration
            $dangKyCu = $this->repository->findRegistration($sinhVien->id, $lopCuId);
            if (!$dangKyCu) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Chưa đăng ký lớp cũ'
                ], 404);
            }

            // Check time conflict with other classes (excluding old class)
            $registeredClasses = $this->repository->getRegisteredClasses($sinhVien->id, $hocKyId);

            foreach ($registeredClasses as $reg) {
                if ($reg->lop_hoc_phan_id === $lopCuId)
                    continue;

                $existingSchedules = $reg->lopHocPhan?->lichHocDinhKys ?? collect();
                if ($this->checkTimeConflict($lopMoi->lichHocDinhKys, $existingSchedules)) {
                    return response()->json([
                        'isSuccess' => false,
                        'data' => null,
                        'message' => 'Trùng lịch học với lớp ' . $reg->lopHocPhan?->ma_lop
                    ], 400);
                }
            }

            // Perform Transfer
            DB::transaction(function () use ($sinhVien, $lopCuId, $lopMoiId, $hocKyId, $dangKyCu) {
                $this->repository->logRegistrationAction($sinhVien->id, $hocKyId, $dangKyCu->id, 'chuyen_lop');
                $this->repository->transferRegistration($dangKyCu, $lopMoiId);
                $this->repository->decrementClassCount($lopCuId);
                $this->repository->incrementClassCount($lopMoiId);
            });

            return response()->json([
                'isSuccess' => true,
                'data' => null,
                'message' => 'Chuyển lớp thành công'
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
     * GET /api/sv/lop-hoc-phan/mon-hoc?monHocId={id}&hocKyId={id}
     */
    public function getLopByMonHoc(Request $request): JsonResponse
    {
        try {
            $monHocId = $request->query('monHocId') ?? $request->query('mon_hoc_id');
            $hocKyId = $request->query('hocKyId') ?? $request->query('hoc_ky_id');

            if (!$monHocId || !$hocKyId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thiếu thông tin (monHocId, hocKyId)'
                ], 400);
            }

            $sinhVien = $this->getSinhVienFromToken();
            if (!$sinhVien) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin sinh viên'
                ], 404);
            }

            $registeredIds = $this->repository->getRegisteredClassIds($sinhVien->id, $hocKyId);
            $lopHocPhans = $this->repository->getClassesByMonHoc($monHocId, $hocKyId, $registeredIds);

            $data = $lopHocPhans->map(function ($lhp) {
                return [
                    'id' => $lhp->id,
                    'maLop' => $lhp->ma_lop,
                    'soLuongHienTai' => $lhp->so_luong_hien_tai ?? 0,
                    'soLuongToiDa' => $lhp->so_luong_toi_da ?? 50,
                    'conCho' => ($lhp->so_luong_toi_da ?? 50) - ($lhp->so_luong_hien_tai ?? 0),
                    'tkb' => $lhp->lichHocDinhKys->map(function ($lich) {
                        return [
                            'thu' => $lich->thu,
                            'tietBatDau' => $lich->tiet_bat_dau,
                            'tietKetThuc' => $lich->tiet_ket_thuc,
                            'phong' => $lich->phong?->ma_phong ?? 'TBA',
                        ];
                    }),
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => "Lấy thành công {$data->count()} lớp"
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
     * GET /api/sv/lich-su-dang-ky?hocKyId={id}
     */
    public function getLichSuDangKy(Request $request): JsonResponse
    {
        try {
            $hocKyId = $request->query('hocKyId') ?? $request->query('hoc_ky_id');

            if (!$hocKyId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thiếu hocKyId'
                ], 400);
            }

            $sinhVien = $this->getSinhVienFromToken();
            if (!$sinhVien) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin sinh viên'
                ], 404);
            }

            $result = $this->getHistoryUseCase->execute($sinhVien->id, $hocKyId);
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
     * GET /api/sv/tkb-weekly?hocKyId={id}&dateStart={date}&dateEnd={date}
     */
    public function getTKBWeekly(Request $request): JsonResponse
    {
        try {
            $hocKyId = $request->query('hocKyId') ?? $request->query('hoc_ky_id');
            $dateStart = $request->query('dateStart') ?? $request->query('date_start');
            $dateEnd = $request->query('dateEnd') ?? $request->query('date_end');

            if (!$hocKyId || !$dateStart || !$dateEnd) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thiếu thông tin (hocKyId, dateStart, dateEnd)'
                ], 400);
            }

            $sinhVien = $this->getSinhVienFromToken();
            if (!$sinhVien) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => [],
                    'message' => 'Không tìm thấy thông tin sinh viên'
                ]);
            }

            $result = $this->getScheduleUseCase->execute($sinhVien->id, $hocKyId, $dateStart, $dateEnd);
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
     * GET /api/sv/tra-cuu-hoc-phan?hocKyId={id}
     */
    public function traCuuHocPhan(Request $request): JsonResponse
    {
        try {
            $hocKyId = $request->query('hocKyId') ?? $request->query('hoc_ky_id');

            if (!$hocKyId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thiếu hocKyId'
                ], 400);
            }

            $result = $this->searchCoursesUseCase->execute($hocKyId);
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
     * GET /api/sv/hoc-phi?hocKyId={id}
     */
    public function getHocPhi(Request $request): JsonResponse
    {
        try {
            $hocKyId = $request->query('hocKyId') ?? $request->query('hoc_ky_id');

            if (!$hocKyId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thiếu hocKyId'
                ], 400);
            }

            $sinhVien = $this->getSinhVienFromToken();
            if (!$sinhVien) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin sinh viên'
                ], 404);
            }

            $tuitionInfo = $this->repository->getTuitionInfo($sinhVien->id, $hocKyId);

            if (!$tuitionInfo) {
                return response()->json([
                    'isSuccess' => true,
                    'data' => null,
                    'message' => 'Không có đăng ký học phần trong học kỳ này'
                ]);
            }

            return response()->json([
                'isSuccess' => true,
                'data' => $tuitionInfo,
                'message' => 'Lấy thông tin học phí thành công'
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
     * GET /api/sv/lop-da-dang-ky/tai-lieu?hocKyId={id}
     */
    public function getTaiLieuLopDaDangKy(Request $request): JsonResponse
    {
        try {
            $hocKyId = $request->query('hocKyId') ?? $request->query('hoc_ky_id');

            if (!$hocKyId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thiếu hocKyId'
                ], 400);
            }

            $sinhVien = $this->getSinhVienFromToken();
            if (!$sinhVien) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin sinh viên'
                ], 404);
            }

            $data = $this->repository->getDocumentsForRegisteredClasses($sinhVien->id, $hocKyId);

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => 'Lấy tài liệu thành công'
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
     * Check if two schedule lists have time conflict
     */
    private function checkTimeConflict($schedules1, $schedules2): bool
    {
        foreach ($schedules1 as $s1) {
            foreach ($schedules2 as $s2) {
                if ($s1->thu !== $s2->thu) {
                    continue;
                }
                if ($s1->tiet_bat_dau < $s2->tiet_ket_thuc && $s2->tiet_bat_dau < $s1->tiet_ket_thuc) {
                    return true;
                }
            }
        }
        return false;
    }
}
