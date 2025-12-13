<?php

namespace App\Http\Controllers\Api\SinhVien;

use App\Http\Controllers\Controller;
use App\Infrastructure\SinhVien\Persistence\Models\SinhVien;
use App\Infrastructure\SinhVien\Persistence\Models\KyPhase;
use App\Infrastructure\SinhVien\Persistence\Models\LopHocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\DangKyHocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\DangKyTkb;
use App\Infrastructure\SinhVien\Persistence\Models\LichSuDangKy;
use App\Infrastructure\SinhVien\Persistence\Models\ChiTietLichSuDangKy;
use App\Infrastructure\SinhVien\Persistence\Models\LichHocDinhKy;
use App\Infrastructure\SinhVien\Persistence\Models\HocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\TaiLieu;
use App\Infrastructure\Common\Persistence\Models\HocKy;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use App\Infrastructure\Redis\Services\RedisLockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;

class DangKyHocPhanController extends Controller
{
    /**
     * Redis Lock Service for preventing race conditions
     */
    private RedisLockService $lockService;

    public function __construct(RedisLockService $lockService)
    {
        $this->lockService = $lockService;
    }

    /**
     * Help method to get SinhVien from JWT token
     */
    private function getSinhVienFromToken()
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
     * Check if course registration phase is open
     */
    public function checkPhaseDangKy(Request $request)
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

            $now = now();
            $currentPhase = KyPhase::where('hoc_ky_id', $hocKyId)
                ->where('is_enabled', true)
                ->where('start_at', '<=', $now)
                ->where('end_at', '>=', $now)
                ->first();

            if (!$currentPhase) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Chưa có giai đoạn hiện hành'
                ], 400);
            }

            if ($currentPhase->phase !== 'dang_ky_hoc_phan') {
                return response()->json([
                    'isSuccess' => false,
                    'data' => ['phase' => $currentPhase->phase],
                    'message' => 'Không trong giai đoạn đăng ký học phần'
                ], 400);
            }

            return response()->json([
                'isSuccess' => true,
                'data' => [
                    'phase' => $currentPhase->phase,
                    'startAt' => $currentPhase->start_at->toISOString(),
                    'endAt' => $currentPhase->end_at->toISOString(),
                ],
                'message' => 'Đang trong giai đoạn đăng ký học phần'
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
     * GET /api/sv/lop-hoc-phan?hocKyId={id}
     * Get list of available course classes
     */
    public function getLopHocPhan(Request $request)
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

            // Get all LopHocPhan for the semester with related info
            $lopHocPhans = LopHocPhan::with(['hocPhan.monHoc.khoa', 'lichHocDinhKys.phong', 'giangVien'])
                ->whereHas('hocPhan', function ($q) use ($hocKyId) {
                    $q->where('id_hoc_ky', $hocKyId);
                })
                ->get();

            // DEBUG: Log query result count
            \Log::info("DEBUG getLopHocPhan: hocKyId={$hocKyId}, found=" . $lopHocPhans->count() . " lop hoc phan");

            // Get registered lop_hoc_phan_ids for this student
            $registeredIds = DangKyHocPhan::where('sinh_vien_id', $sinhVien->id)
                ->whereHas('lopHocPhan.hocPhan', function ($q) use ($hocKyId) {
                    $q->where('id_hoc_ky', $hocKyId);
                })
                ->pluck('lop_hoc_phan_id')
                ->toArray();

            // Group by MonHoc (like Django implementation)
            $monHocMap = [];

            foreach ($lopHocPhans as $lhp) {
                // Skip if already registered
                if (in_array($lhp->id, $registeredIds)) {
                    continue;
                }

                $hocPhan = $lhp->hocPhan;
                $monHoc = $hocPhan?->monHoc;
                $maMon = $monHoc?->ma_mon ?? 'UNKNOWN';

                if (!isset($monHocMap[$maMon])) {
                    $monHocMap[$maMon] = [
                        'monHocId' => $monHoc?->id ?? '',
                        'maMon' => $maMon,
                        'tenMon' => $monHoc?->ten_mon ?? '',
                        'soTinChi' => $monHoc?->so_tin_chi ?? 0,
                        'laMonChung' => $monHoc?->la_mon_chung ?? false,
                        'loaiMon' => $monHoc?->loai_mon ?? '',
                        'danhSachLop' => [],
                    ];
                }

                // Build TKB info
                $tkbList = $lhp->lichHocDinhKys->map(function ($lich) {
                    return [
                        'thu' => $lich->thu,
                        'tietBatDau' => $lich->tiet_bat_dau,
                        'tietKetThuc' => $lich->tiet_ket_thuc,
                        'phong' => $lich->phong?->ma_phong ?? 'TBA',
                    ];
                });

                $monHocMap[$maMon]['danhSachLop'][] = [
                    'id' => $lhp->id,
                    'maLop' => $lhp->ma_lop,
                    'tenLop' => $lhp->ma_lop,
                    'soLuongHienTai' => $lhp->so_luong_hien_tai ?? 0,
                    'soLuongToiDa' => $lhp->so_luong_toi_da ?? 50,
                    'tkb' => $tkbList,
                ];
            }

            // Categorize into monChung, batBuoc, tuChon
            $monChung = [];
            $batBuoc = [];
            $tuChon = [];

            foreach ($monHocMap as $dto) {
                $laMonChung = $dto['laMonChung'] ?? false;
                $loaiMon = $dto['loaiMon'] ?? '';

                // Remove internal fields before adding to output
                unset($dto['laMonChung']);
                unset($dto['loaiMon']);

                if ($laMonChung) {
                    $monChung[] = $dto;
                } elseif ($loaiMon === 'chuyen_nganh') {
                    $batBuoc[] = $dto;
                } else {
                    $tuChon[] = $dto;
                }
            }

            return response()->json([
                'isSuccess' => true,
                'data' => [
                    'monChung' => $monChung,
                    'batBuoc' => $batBuoc,
                    'tuChon' => $tuChon,
                ],
                'message' => "Lấy thành công " . count($monHocMap) . " môn học"
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
     * GET /api/sv/lop-da-dang-ky?hocKyId={id}
     * Get list of registered course classes
     */
    public function getLopDaDangKy(Request $request)
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

            // Get registered classes with related info
            $dangKys = DangKyHocPhan::with(['lopHocPhan.hocPhan.monHoc.khoa', 'lopHocPhan.lichHocDinhKys.phong'])
                ->where('sinh_vien_id', $sinhVien->id)
                ->whereHas('lopHocPhan.hocPhan', function ($q) use ($hocKyId) {
                    $q->where('id_hoc_ky', $hocKyId);
                })
                ->get();

            // Group by MonHoc
            $monHocMap = [];

            foreach ($dangKys as $dk) {
                $lhp = $dk->lopHocPhan;
                $monHoc = $lhp?->hocPhan?->monHoc;
                $maMon = $monHoc?->ma_mon ?? 'UNKNOWN';

                if (!isset($monHocMap[$maMon])) {
                    $monHocMap[$maMon] = [
                        'maMon' => $maMon,
                        'tenMon' => $monHoc?->ten_mon ?? '',
                        'soTinChi' => $monHoc?->so_tin_chi ?? 0,
                        'danhSachLop' => [],
                    ];
                }

                // Build TKB info
                $tkbList = $lhp->lichHocDinhKys->map(function ($lich) {
                    return [
                        'thu' => $lich->thu,
                        'tietBatDau' => $lich->tiet_bat_dau,
                        'tietKetThuc' => $lich->tiet_ket_thuc,
                        'phong' => $lich->phong?->ma_phong ?? 'TBA',
                    ];
                });

                $monHocMap[$maMon]['danhSachLop'][] = [
                    'id' => $lhp->id,
                    'maLop' => $lhp->ma_lop,
                    'soLuongHienTai' => $lhp->so_luong_hien_tai ?? 0,
                    'soLuongToiDa' => $lhp->so_luong_toi_da ?? 50,
                    'tkb' => $tkbList,
                    'trangThai' => $dk->trang_thai,
                ];
            }

            return response()->json([
                'isSuccess' => true,
                'data' => array_values($monHocMap),
                'message' => 'Lấy danh sách lớp đã đăng ký thành công'
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
     * POST /api/sv/dang-ky-hoc-phan
     * Register for a course class
     * Body: { "lopHocPhanId": "uuid", "hocKyId": "uuid" }
     *
     * Uses Redis distributed lock to prevent race conditions when
     * multiple students try to register for the same class simultaneously.
     */
    public function dangKyHocPhan(Request $request)
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

            // 1. Check Phase (outside lock - read-only)
            $now = now();
            $currentPhase = KyPhase::where('hoc_ky_id', $hocKyId)
                ->where('is_enabled', true)
                ->where('start_at', '<=', $now)
                ->where('end_at', '>=', $now)
                ->first();

            if (!$currentPhase || $currentPhase->phase !== 'dang_ky_hoc_phan') {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không trong thời gian đăng ký học phần'
                ], 400);
            }

            // 2. Get Class Info (outside lock - read-only)
            $lhp = LopHocPhan::with(['hocPhan.monHoc', 'lichHocDinhKys'])->find($lopHocPhanId);
            if (!$lhp) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Lớp học phần không tồn tại'
                ], 404);
            }

            // 3. Check if already registered for this subject (outside lock - student-specific)
            $monHocId = $lhp->hocPhan?->mon_hoc_id;
            if ($monHocId) {
                $hasRegistered = DangKyHocPhan::where('sinh_vien_id', $sinhVien->id)
                    ->whereHas('lopHocPhan.hocPhan', function ($q) use ($monHocId, $hocKyId) {
                        $q->where('mon_hoc_id', $monHocId)
                            ->where('id_hoc_ky', $hocKyId);
                    })
                    ->exists();

                if ($hasRegistered) {
                    return response()->json([
                        'isSuccess' => false,
                        'data' => null,
                        'message' => 'Sinh viên đã đăng ký môn học này trong học kỳ'
                    ], 400);
                }
            }

            // 4. Check Time Conflict (outside lock - student-specific)
            $newSchedules = $lhp->lichHocDinhKys;

            $existingRegistrations = DangKyHocPhan::with('lopHocPhan.lichHocDinhKys')
                ->where('sinh_vien_id', $sinhVien->id)
                ->whereHas('lopHocPhan.hocPhan', function ($q) use ($hocKyId) {
                    $q->where('id_hoc_ky', $hocKyId);
                })
                ->get();

            foreach ($existingRegistrations as $reg) {
                $existingSchedules = $reg->lopHocPhan?->lichHocDinhKys ?? collect();

                if ($this->checkTimeConflict($newSchedules, $existingSchedules)) {
                    return response()->json([
                        'isSuccess' => false,
                        'data' => null,
                        'message' => 'Trùng lịch học với lớp ' . $reg->lopHocPhan?->ma_lop
                    ], 400);
                }
            }

            // ============================================================
            // 5. CRITICAL SECTION - Use Redis Lock to prevent race condition
            // ============================================================
            // Lock key is unique per class to allow parallel registration to different classes
            $lockKey = "dkhp:lop:{$lopHocPhanId}";

            return $this->lockService->withLock($lockKey, function () use ($sinhVien, $lopHocPhanId, $hocKyId, $lhp) {
                // Re-fetch class to get latest slot count (fresh from DB)
                $lhp = LopHocPhan::find($lopHocPhanId);
                $currentCount = $lhp->so_luong_hien_tai ?? 0;
                $maxCount = $lhp->so_luong_toi_da ?? 50;

                // Check slot availability (inside lock - atomic!)
                if ($currentCount >= $maxCount) {
                    return response()->json([
                        'isSuccess' => false,
                        'data' => null,
                        'message' => 'Lớp học phần đã đầy'
                    ], 400);
                }

                // Double-check if student hasn't registered while waiting for lock
                $alreadyRegistered = DangKyHocPhan::where('sinh_vien_id', $sinhVien->id)
                    ->where('lop_hoc_phan_id', $lopHocPhanId)
                    ->exists();

                if ($alreadyRegistered) {
                    return response()->json([
                        'isSuccess' => false,
                        'data' => null,
                        'message' => 'Bạn đã đăng ký lớp học phần này rồi'
                    ], 400);
                }

                // 6. Perform Registration (inside lock - safe now!)
                DB::transaction(function () use ($sinhVien, $lopHocPhanId, $hocKyId, $lhp) {
                    // Create DangKyHocPhan
                    $dangKy = DangKyHocPhan::create([
                        'id' => Str::uuid()->toString(),
                        'sinh_vien_id' => $sinhVien->id,
                        'lop_hoc_phan_id' => $lopHocPhanId,
                        'ngay_dang_ky' => now(),
                        'trang_thai' => 'da_dang_ky',
                    ]);

                    // Create DangKyTkb
                    DangKyTkb::create([
                        'id' => Str::uuid()->toString(),
                        'dang_ky_id' => $dangKy->id,
                        'sinh_vien_id' => $sinhVien->id,
                        'lop_hoc_phan_id' => $lopHocPhanId,
                    ]);

                    // Update Quantity
                    $lhp->increment('so_luong_hien_tai');

                    // Log History
                    $lichSu = LichSuDangKy::firstOrCreate(
                        [
                            'sinh_vien_id' => $sinhVien->id,
                            'hoc_ky_id' => $hocKyId,
                        ],
                        [
                            'id' => Str::uuid()->toString(),
                            'ngay_tao' => now(),
                        ]
                    );

                    ChiTietLichSuDangKy::create([
                        'id' => Str::uuid()->toString(),
                        'lich_su_dang_ky_id' => $lichSu->id,
                        'dang_ky_hoc_phan_id' => $dangKy->id,
                        'hanh_dong' => 'dang_ky',
                        'thoi_gian' => now(),
                    ]);
                });

                return response()->json([
                    'isSuccess' => true,
                    'data' => null,
                    'message' => 'Đăng ký học phần thành công'
                ]);
            });

        } catch (\RuntimeException $e) {
            // Lock acquisition timeout
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => $e->getMessage()
            ], 503); // Service Unavailable

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
     * Cancel course registration
     * Body: { "lopHocPhanId": "uuid", "hocKyId": "uuid" (optional) }
     */
    public function huyDangKyHocPhan(Request $request)
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

            // 1. Check Phase
            $now = now();
            $currentPhase = KyPhase::where('hoc_ky_id', $hocKyId)
                ->where('is_enabled', true)
                ->where('start_at', '<=', $now)
                ->where('end_at', '>=', $now)
                ->first();

            if (!$currentPhase || $currentPhase->phase !== 'dang_ky_hoc_phan') {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không trong thời gian đăng ký học phần'
                ], 400);
            }

            // 2. Find Registration
            $dangKy = DangKyHocPhan::where('sinh_vien_id', $sinhVien->id)
                ->where('lop_hoc_phan_id', $lopHocPhanId)
                ->first();

            if (!$dangKy) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Chưa đăng ký lớp học phần này'
                ], 404);
            }

            // Only da_dang_ky can be cancelled
            if ($dangKy->trang_thai !== 'da_dang_ky') {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không thể hủy đăng ký với trạng thái: ' . $dangKy->trang_thai
                ], 400);
            }

            // 3. Perform Cancellation
            DB::transaction(function () use ($sinhVien, $lopHocPhanId, $hocKyId, $dangKy) {
                // Log History first
                LichSuDangKy::create([
                    'id' => Str::uuid()->toString(),
                    'sinh_vien_id' => $sinhVien->id,
                    'hoc_ky_id' => $hocKyId,
                    'dang_ky_hoc_phan_id' => $dangKy->id,
                    'hanh_dong' => 'huy_dang_ky',
                    'thoi_gian' => now(),
                ]);

                // Delete TKB
                DangKyTkb::where('dang_ky_id', $dangKy->id)->delete();

                // Delete registration
                $dangKy->delete();

                // Update Quantity
                LopHocPhan::where('id', $lopHocPhanId)->decrement('so_luong_hien_tai');
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
     * Transfer to another class
     * Body: { "lopCuId": "uuid", "lopMoiId": "uuid", "hocKyId": "uuid" (optional) }
     */
    public function chuyenLopHocPhan(Request $request)
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
            $now = now();
            $currentPhase = KyPhase::where('hoc_ky_id', $hocKyId)
                ->where('is_enabled', true)
                ->where('start_at', '<=', $now)
                ->where('end_at', '>=', $now)
                ->first();

            if (!$currentPhase || $currentPhase->phase !== 'dang_ky_hoc_phan') {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không trong thời gian đăng ký học phần'
                ], 400);
            }

            // Get new class
            $lopMoi = LopHocPhan::with('lichHocDinhKys')->find($lopMoiId);
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
            $dangKyCu = DangKyHocPhan::where('sinh_vien_id', $sinhVien->id)
                ->where('lop_hoc_phan_id', $lopCuId)
                ->first();

            if (!$dangKyCu) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Chưa đăng ký lớp cũ'
                ], 404);
            }

            // Check time conflict with other classes (excluding old class)
            $existingRegistrations = DangKyHocPhan::with('lopHocPhan.lichHocDinhKys')
                ->where('sinh_vien_id', $sinhVien->id)
                ->where('lop_hoc_phan_id', '!=', $lopCuId)
                ->whereHas('lopHocPhan.hocPhan', function ($q) use ($hocKyId) {
                    $q->where('id_hoc_ky', $hocKyId);
                })
                ->get();

            foreach ($existingRegistrations as $reg) {
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
            DB::transaction(function () use ($sinhVien, $lopCuId, $lopMoiId, $hocKyId, $dangKyCu, $lopMoi) {
                // Log history for old class
                LichSuDangKy::create([
                    'id' => Str::uuid()->toString(),
                    'sinh_vien_id' => $sinhVien->id,
                    'hoc_ky_id' => $hocKyId,
                    'dang_ky_hoc_phan_id' => $dangKyCu->id,
                    'hanh_dong' => 'chuyen_lop',
                    'thoi_gian' => now(),
                ]);

                // Update registration to new class
                $dangKyCu->lop_hoc_phan_id = $lopMoiId;
                $dangKyCu->ngay_dang_ky = now();
                $dangKyCu->save();

                // Update TKB
                DangKyTkb::where('dang_ky_id', $dangKyCu->id)
                    ->update(['lop_hoc_phan_id' => $lopMoiId]);

                // Update quantities
                LopHocPhan::where('id', $lopCuId)->decrement('so_luong_hien_tai');
                $lopMoi->increment('so_luong_hien_tai');
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
     * Get classes by subject (for switching)
     */
    public function getLopByMonHoc(Request $request)
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

            // Get registered lop_hoc_phan_ids for this student/semester
            $registeredIds = DangKyHocPhan::where('sinh_vien_id', $sinhVien->id)
                ->whereHas('lopHocPhan.hocPhan', function ($q) use ($hocKyId) {
                    $q->where('id_hoc_ky', $hocKyId);
                })
                ->pluck('lop_hoc_phan_id')
                ->toArray();

            // Get classes for this subject (not registered)
            $lopHocPhans = LopHocPhan::with(['lichHocDinhKys.phong'])
                ->whereHas('hocPhan', function ($q) use ($monHocId, $hocKyId) {
                    $q->where('mon_hoc_id', $monHocId)
                        ->where('id_hoc_ky', $hocKyId);
                })
                ->whereNotIn('id', $registeredIds)
                ->get();

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
     * Get registration history from chi_tiet_lich_su_dang_ky table
     */
    public function getLichSuDangKy(Request $request)
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

            // Get lich_su_dang_ky record for this student/semester
            $lichSu = LichSuDangKy::where('sinh_vien_id', $sinhVien->id)
                ->where('hoc_ky_id', $hocKyId)
                ->first();

            if (!$lichSu) {
                return response()->json([
                    'isSuccess' => true,
                    'data' => [],
                    'message' => 'Không có lịch sử đăng ký'
                ]);
            }

            // Get details from chi_tiet_lich_su_dang_ky
            $chiTiets = ChiTietLichSuDangKy::with(['dangKyHocPhan.lopHocPhan.hocPhan.monHoc'])
                ->where('lich_su_dang_ky_id', $lichSu->id)
                ->orderBy('thoi_gian', 'desc')
                ->get();

            $lichSuItems = $chiTiets->map(function ($ct) {
                $dangKy = $ct->dangKyHocPhan;
                $lhp = $dangKy?->lopHocPhan;
                $monHoc = $lhp?->hocPhan?->monHoc;

                return [
                    'id' => $ct->id,
                    'hanhDong' => $ct->hanh_dong,
                    'thoiGian' => $ct->thoi_gian?->toISOString(),
                    'monHoc' => [
                        'maMon' => $monHoc?->ma_mon ?? '',
                        'tenMon' => $monHoc?->ten_mon ?? '',
                        'soTinChi' => $monHoc?->so_tin_chi ?? 0,
                    ],
                    'lopHocPhan' => [
                        'id' => $lhp?->id ?? '',
                        'maLop' => $lhp?->ma_lop ?? '',
                    ],
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => [
                    'lichSu' => $lichSuItems,
                ],
                'message' => "Lấy thành công {$lichSuItems->count()} lịch sử"
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
     * GET /api/sv/tkb-weekly?hocKyId={id}&dateStart={date}&dateEnd={date}
     * Get weekly schedule
     */
    public function getTKBWeekly(Request $request)
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

            // Get registered classes
            $dangKys = DangKyHocPhan::with(['lopHocPhan.lichHocDinhKys.phong', 'lopHocPhan.hocPhan.monHoc', 'lopHocPhan.giangVien'])
                ->where('sinh_vien_id', $sinhVien->id)
                ->whereHas('lopHocPhan.hocPhan', function ($q) use ($hocKyId) {
                    $q->where('id_hoc_ky', $hocKyId);
                })
                ->get();

            // Parse date range
            $startDate = new \DateTime($dateStart);
            $endDate = new \DateTime($dateEnd);

            // Build schedule data - generate entries for each day in range
            $data = [];

            foreach ($dangKys as $dk) {
                $lhp = $dk->lopHocPhan;
                $monHoc = $lhp?->hocPhan?->monHoc;
                $giangVien = $lhp?->giangVien;

                foreach ($lhp->lichHocDinhKys as $lich) {
                    // Calculate actual dates for this weekday within the date range
                    // thu: 2 = Monday, 3 = Tuesday, ..., 8 = Sunday (alternate)
                    $thu = $lich->thu;
                    $phpDayOfWeek = ($thu == 8) ? 0 : ($thu - 1); // Convert to PHP: 0=Sun, 1=Mon, ...

                    // Find all occurrences of this weekday in the date range
                    $currentDate = clone $startDate;
                    while ($currentDate <= $endDate) {
                        if ((int) $currentDate->format('w') == $phpDayOfWeek) {
                            $data[] = [
                                'ngay_hoc' => $currentDate->format('Y-m-d'),
                                'thu' => $thu,
                                'tiet_bat_dau' => $lich->tiet_bat_dau,
                                'tiet_ket_thuc' => $lich->tiet_ket_thuc,
                                'phong' => [
                                    'id' => $lich->phong?->id ?? '',
                                    'ma_phong' => $lich->phong?->ma_phong ?? 'TBA',
                                ],
                                'mon_hoc' => [
                                    'ma_mon' => $monHoc?->ma_mon ?? '',
                                    'ten_mon' => $monHoc?->ten_mon ?? '',
                                ],
                                'giang_vien' => $giangVien?->ho_ten ?? 'Chưa phân công',
                                'ma_lop' => $lhp->ma_lop ?? '',
                            ];
                        }
                        $currentDate->modify('+1 day');
                    }
                }
            }

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => 'Lấy thời khóa biểu thành công'
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
     * GET /api/sv/tra-cuu-hoc-phan?hocKyId={id}
     * Search classes
     */
    public function traCuuHocPhan(Request $request)
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

            // Get open HocPhan for the semester with classes
            $hocPhans = HocPhan::with(['monHoc.khoa', 'lopHocPhans.lichHocDinhKys.phong', 'lopHocPhans.giangVien'])
                ->where('id_hoc_ky', $hocKyId)
                ->where('trang_thai_mo', true)
                ->get();

            $stt = 0;
            $data = $hocPhans->map(function ($hp) use (&$stt) {
                $stt++;
                $monHoc = $hp->monHoc;

                // Determine loaiMon (simplified - can be enhanced with actual mon_hoc category)
                $loaiMon = 'dai_cuong'; // Default value
                // If you have a 'loai' column in mon_hoc table, use it:
                // $loaiMon = $monHoc?->loai ?? 'dai_cuong';

                // Build danhSachLop
                $danhSachLop = ($hp->lopHocPhans ?? collect())->map(function ($lhp) {
                    // Build TKB string
                    $tkbLines = [];
                    foreach ($lhp->lichHocDinhKys ?? [] as $lich) {
                        $thuText = "Thứ " . $lich->thu;
                        $tietText = "Tiết " . $lich->tiet_bat_dau . "-" . $lich->tiet_ket_thuc;
                        $phongText = $lich->phong?->ma_phong ?? 'TBA';
                        $tkbLines[] = "{$thuText}, {$tietText}, {$phongText}";
                    }

                    return [
                        'id' => $lhp->id,
                        'maLop' => $lhp->ma_lop,
                        'giangVien' => $lhp->giangVien?->ho_ten ?? 'Chưa phân công',
                        'soLuongToiDa' => $lhp->so_luong_toi_da ?? 50,
                        'soLuongHienTai' => $lhp->so_luong_hien_tai ?? 0,
                        'conSlot' => ($lhp->so_luong_toi_da ?? 50) - ($lhp->so_luong_hien_tai ?? 0),
                        'thoiKhoaBieu' => implode("\n", $tkbLines) ?: 'Chưa xếp TKB',
                    ];
                });

                return [
                    'stt' => $stt,
                    'maMon' => $monHoc?->ma_mon ?? '',
                    'tenMon' => $monHoc?->ten_mon ?? '',
                    'soTinChi' => $monHoc?->so_tin_chi ?? 0,
                    'loaiMon' => $loaiMon,
                    'danhSachLop' => $danhSachLop,
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => "Tìm thấy {$data->count()} học phần"
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
     * GET /api/sv/hoc-phi?hocKyId={id}
     * Get tuition details
     */
    public function getHocPhi(Request $request)
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

            // Get registered classes and calculate tuition
            $dangKys = DangKyHocPhan::with(['lopHocPhan.hocPhan.monHoc'])
                ->where('sinh_vien_id', $sinhVien->id)
                ->whereHas('lopHocPhan.hocPhan', function ($q) use ($hocKyId) {
                    $q->where('id_hoc_ky', $hocKyId);
                })
                ->get();

            // If no registrations, return null data (FE will show empty state)
            if ($dangKys->isEmpty()) {
                return response()->json([
                    'isSuccess' => true,
                    'data' => null,
                    'message' => 'Không có đăng ký học phần trong học kỳ này'
                ]);
            }

            $totalCredits = 0;
            $chiTiet = [];
            $pricePerCredit = 800000; // Default price

            // TODO: Get actual price from ChinhSachTinChi if available
            // $chinhSach = ChinhSachTinChi::where('hoc_ky_id', $hocKyId)->first();
            // if ($chinhSach) { $pricePerCredit = $chinhSach->don_gia; }

            foreach ($dangKys as $dk) {
                $lhp = $dk->lopHocPhan;
                $monHoc = $lhp?->hocPhan?->monHoc;
                $credits = $monHoc?->so_tin_chi ?? 0;
                $totalCredits += $credits;
                $thanhTien = $credits * $pricePerCredit;

                $chiTiet[] = [
                    'tenMon' => $monHoc?->ten_mon ?? '',
                    'maMon' => $monHoc?->ma_mon ?? '',
                    'maLop' => $lhp?->ma_lop ?? '',
                    'soTinChi' => $credits,
                    'donGia' => $pricePerCredit,
                    'thanhTien' => $thanhTien,
                ];
            }

            // Calculate total tuition
            $tongHocPhi = $totalCredits * $pricePerCredit;

            // TODO: Check if already paid from payment_transaction table
            // For now, always return "chua_thanh_toan"
            $trangThaiThanhToan = 'chua_thanh_toan';

            return response()->json([
                'isSuccess' => true,
                'data' => [
                    'soTinChiDangKy' => $totalCredits,
                    'donGiaTinChi' => $pricePerCredit,
                    'tongHocPhi' => $tongHocPhi,
                    'chiTiet' => $chiTiet,
                    'trangThaiThanhToan' => $trangThaiThanhToan,
                ],
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
     * Get documents for registered classes
     */
    public function getTaiLieuLopDaDangKy(Request $request)
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

            // Get registered classes
            $dangKys = DangKyHocPhan::with(['lopHocPhan.hocPhan.monHoc'])
                ->where('sinh_vien_id', $sinhVien->id)
                ->whereHas('lopHocPhan.hocPhan', function ($q) use ($hocKyId) {
                    $q->where('id_hoc_ky', $hocKyId);
                })
                ->get();

            $data = [];

            foreach ($dangKys as $dk) {
                $lhp = $dk->lopHocPhan;
                $monHoc = $lhp?->hocPhan?->monHoc;

                // Get documents for this class
                $taiLieus = TaiLieu::where('lop_hoc_phan_id', $lhp->id)->get();

                $data[] = [
                    'lopHocPhanId' => $lhp->id,
                    'maLop' => $lhp->ma_lop,
                    'tenMon' => $monHoc?->ten_mon ?? '',
                    'taiLieu' => $taiLieus->map(function ($tl) {
                        return [
                            'id' => $tl->id,
                            'tenTaiLieu' => $tl->ten_tai_lieu,
                            'fileType' => $tl->file_type,
                        ];
                    }),
                ];
            }

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
                // Check same day
                if ($s1->thu !== $s2->thu) {
                    continue;
                }

                // Check time overlap
                if ($s1->tiet_bat_dau < $s2->tiet_ket_thuc && $s2->tiet_bat_dau < $s1->tiet_ket_thuc) {
                    return true;
                }
            }
        }

        return false;
    }
}
