<?php

namespace App\Http\Controllers\Api\Pdt;

use App\Http\Controllers\Controller;
use App\Infrastructure\Common\Persistence\Models\HocKy;
use App\Infrastructure\SinhVien\Persistence\Models\KyPhase;
use App\Infrastructure\SinhVien\Persistence\Models\DotDangKy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class HocKyController extends Controller
{
    /**
     * POST /api/pdt/quan-ly-hoc-ky/hoc-ky-hien-hanh
     * Set current semester
     * Body: { "hocKyId": "uuid" }
     */
    public function setHocKyHienHanh(Request $request)
    {
        try {
            $hocKyId = $request->input('hocKyId') ?? $request->input('hoc_ky_id');

            if (!$hocKyId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thiếu thông tin (hocKyId)',
                    'errorCode' => 'MISSING_PARAMS'
                ], 400);
            }

            // Check if semester exists
            $hocKy = HocKy::find($hocKyId);

            if (!$hocKy) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Học kỳ không tồn tại',
                    'errorCode' => 'NOT_FOUND'
                ], 404);
            }

            // Unset all current semesters, then set the new one
            DB::transaction(function () use ($hocKyId) {
                HocKy::where('trang_thai_hien_tai', true)->update(['trang_thai_hien_tai' => false]);
                HocKy::where('id', $hocKyId)->update(['trang_thai_hien_tai' => true]);
            });

            return response()->json([
                'isSuccess' => true,
                'data' => null,
                'message' => 'Đã đặt học kỳ hiện hành thành công'
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
     * POST /api/pdt/quan-ly-hoc-ky/ky-phase/bulk
     * Create semester phases
     * Body: { "hocKyId": "uuid", "hocKyStartAt": "...", "hocKyEndAt": "...", "phases": [...] }
     */
    public function createBulkKyPhase(Request $request)
    {
        try {
            $hocKyId = $request->input('hocKyId') ?? $request->input('hoc_ky_id');
            $hocKyStartAt = $request->input('hocKyStartAt') ?? $request->input('hoc_ky_start_at');
            $hocKyEndAt = $request->input('hocKyEndAt') ?? $request->input('hoc_ky_end_at');
            $phases = $request->input('phases', []);

            // Validate required fields
            if (!$hocKyId || !$hocKyStartAt || !$hocKyEndAt) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thiếu thông tin học kỳ',
                    'errorCode' => 'MISSING_PARAMS'
                ], 400);
            }

            if (empty($phases) || !is_array($phases)) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Danh sách phases rỗng',
                    'errorCode' => 'MISSING_PARAMS'
                ], 400);
            }

            // Parse dates
            try {
                $startAt = Carbon::parse($hocKyStartAt);
                $endAt = Carbon::parse($hocKyEndAt);
            } catch (\Exception $e) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Định dạng ngày không hợp lệ',
                    'errorCode' => 'INVALID_DATE_FORMAT'
                ], 400);
            }

            if ($startAt >= $endAt) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thời gian bắt đầu học kỳ phải trước thời gian kết thúc',
                    'errorCode' => 'INVALID_TIME_RANGE'
                ], 400);
            }

            $createdPhases = [];

            DB::transaction(function () use ($hocKyId, $startAt, $endAt, $phases, &$createdPhases) {
                // 1. Update HocKy dates
                HocKy::where('id', $hocKyId)->update([
                    'ngay_bat_dau' => $startAt->toDateString(),
                    'ngay_ket_thuc' => $endAt->toDateString(),
                    'updated_at' => now(),
                ]);

                // 2. Delete old phases and dot_dang_ky for this semester
                KyPhase::where('hoc_ky_id', $hocKyId)->delete();
                DotDangKy::where('hoc_ky_id', $hocKyId)->delete();

                // 3. Create new phases
                $ghiDanhPhase = null;
                $dangKyPhase = null;

                foreach ($phases as $phase) {
                    $phaseName = $phase['phase'] ?? $phase['tenPhase'] ?? '';
                    $phaseStart = $phase['startAt'] ?? $phase['ngayBatDau'] ?? null;
                    $phaseEnd = $phase['endAt'] ?? $phase['ngayKetThuc'] ?? null;

                    if (!$phaseName || !$phaseStart || !$phaseEnd)
                        continue;

                    $newPhase = KyPhase::create([
                        'id' => Str::uuid()->toString(),
                        'hoc_ky_id' => $hocKyId,
                        'phase' => $phaseName,
                        'start_at' => Carbon::parse($phaseStart),
                        'end_at' => Carbon::parse($phaseEnd),
                        'is_enabled' => $phase['isEnabled'] ?? true,
                    ]);

                    $createdPhases[] = [
                        'id' => $newPhase->id,
                        'phase' => $newPhase->phase,
                        'startAt' => $newPhase->start_at?->toISOString(),
                        'endAt' => $newPhase->end_at?->toISOString(),
                        'isEnabled' => $newPhase->is_enabled,
                    ];

                    if ($phaseName === 'ghi_danh') {
                        $ghiDanhPhase = $newPhase;
                    } elseif ($phaseName === 'dang_ky_hoc_phan') {
                        $dangKyPhase = $newPhase;
                    }
                }

                // 4. Create default DotDangKy for ghi_danh and dang_ky phases
                if ($ghiDanhPhase) {
                    DotDangKy::create([
                        'id' => Str::uuid()->toString(),
                        'hoc_ky_id' => $hocKyId,
                        'loai_dot' => 'ghi_danh',
                        'is_check_toan_truong' => true,
                        'thoi_gian_bat_dau' => $ghiDanhPhase->start_at,
                        'thoi_gian_ket_thuc' => $ghiDanhPhase->end_at,
                        'gioi_han_tin_chi' => 50,
                        'khoa_id' => null,
                    ]);
                }

                if ($dangKyPhase) {
                    DotDangKy::create([
                        'id' => Str::uuid()->toString(),
                        'hoc_ky_id' => $hocKyId,
                        'loai_dot' => 'dang_ky',
                        'is_check_toan_truong' => true,
                        'thoi_gian_bat_dau' => $dangKyPhase->start_at,
                        'thoi_gian_ket_thuc' => $dangKyPhase->end_at,
                        'gioi_han_tin_chi' => 9999,
                        'khoa_id' => null,
                    ]);
                }
            });

            return response()->json([
                'isSuccess' => true,
                'data' => $createdPhases,
                'message' => 'Tạo phases thành công'
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
     * GET /api/pdt/quan-ly-hoc-ky/ky-phase/{hocKyId}
     * Get phases by semester
     */
    public function getPhasesByHocKy(Request $request, $hocKyId)
    {
        try {
            if (!$hocKyId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thiếu hocKyId'
                ], 400);
            }

            $phases = KyPhase::where('hoc_ky_id', $hocKyId)
                ->orderBy('start_at', 'asc')
                ->get();

            $data = $phases->map(function ($p) {
                return [
                    'id' => $p->id,
                    'phase' => $p->phase,
                    'startAt' => $p->start_at?->toISOString(),
                    'endAt' => $p->end_at?->toISOString(),
                    'isEnabled' => $p->is_enabled ?? false,
                    'isActive' => $p->isActive(),
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => "Lấy thành công {$data->count()} phases"
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }
}
