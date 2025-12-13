<?php

namespace App\Http\Controllers\Api\Pdt;

use App\Http\Controllers\Controller;
use App\Infrastructure\SinhVien\Persistence\Models\KyPhase;
use App\Infrastructure\Common\Persistence\Models\HocKy;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DemoController extends Controller
{
    /**
     * POST /api/pdt/demo/toggle-phase
     * POST /api/pdt/ky-phase/toggle (alias)
     * Toggle phase enabled status
     * Body: { "phase": "ghi_danh", "hocKyId"?: "uuid" }
     * 
     * If hocKyId is not provided, uses current semester
     */
    public function togglePhase(Request $request)
    {
        try {
            $phaseName = $request->input('phase');
            $hocKyId = $request->input('hocKyId') ?? $request->input('hoc_ky_id');

            // Validate phase is required
            if (!$phaseName) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'phase is required'
                ], 400);
            }

            // Validate phase name
            $validPhases = ['de_xuat_phe_duyet', 'ghi_danh', 'dang_ky_hoc_phan', 'sap_xep_tkb', 'binh_thuong'];
            if (!in_array($phaseName, $validPhases)) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Phase không hợp lệ. Valid: ' . implode(', ', $validPhases)
                ], 400);
            }

            // If hocKyId not provided, get current semester
            if (!$hocKyId) {
                $currentHocKy = HocKy::where('trang_thai_hien_tai', true)->first();
                if (!$currentHocKy) {
                    return response()->json([
                        'isSuccess' => false,
                        'data' => null,
                        'message' => 'Không tìm thấy học kỳ hiện hành'
                    ], 404);
                }
                $hocKyId = $currentHocKy->id;
            }

            // 1. Disable all phases for this semester (move to past dates)
            $now = Carbon::now();
            KyPhase::where('hoc_ky_id', $hocKyId)->update([
                'is_enabled' => false,
                'start_at' => $now->copy()->subYear(),
                'end_at' => $now->copy()->subYear()->addDay(),
            ]);

            // 2. Enable the selected phase
            $kyPhase = KyPhase::where('hoc_ky_id', $hocKyId)
                ->where('phase', $phaseName)
                ->first();

            if (!$kyPhase) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => "Không tìm thấy phase '{$phaseName}' trong học kỳ này (dữ liệu chưa được khởi tạo)"
                ], 404);
            }

            $startAt = $now->copy()->subHour();
            $endAt = $now->copy()->addDays(30);

            $kyPhase->is_enabled = true;
            $kyPhase->start_at = $startAt;
            $kyPhase->end_at = $endAt;
            $kyPhase->save();

            return response()->json([
                'isSuccess' => true,
                'data' => [
                    'message' => "Đã chuyển sang giai đoạn: {$phaseName}",
                    'active_phase' => $phaseName,
                    'start_at' => $startAt->toIso8601String(),
                    'end_at' => $endAt->toIso8601String(),
                ],
                'message' => "Đã chuyển sang giai đoạn: {$phaseName}"
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
     * POST /api/pdt/demo/reset-data
     * Reset demo data - dangerous operation
     */
    public function resetData(Request $request)
    {
        try {
            // This is a demo/testing endpoint
            // In production, this should be protected or removed

            return response()->json([
                'isSuccess' => true,
                'data' => null,
                'message' => 'Reset data is disabled in this implementation'
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
