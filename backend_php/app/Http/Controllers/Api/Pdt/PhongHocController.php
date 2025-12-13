<?php

namespace App\Http\Controllers\Api\Pdt;

use App\Http\Controllers\Controller;
use App\Infrastructure\SinhVien\Persistence\Models\Phong;
use Illuminate\Http\Request;

class PhongHocController extends Controller
{
    /**
     * GET /api/pdt/phong-hoc/available
     * Get available rooms (not assigned to any khoa)
     */
    public function available(Request $request)
    {
        try {
            $phongs = Phong::with('coSo')
                ->whereNull('khoa_id')
                ->orderBy('ma_phong', 'asc')
                ->get();

            $data = $phongs->map(function ($p) {
                return [
                    'id' => $p->id,
                    'maPhong' => $p->ma_phong ?? '',
                    'sucChua' => $p->suc_chua ?? 0,
                    'daSuDung' => $p->da_dc_su_dung ?? false,
                    'khoaId' => $p->khoa_id,
                    'coSoId' => $p->co_so_id,
                    'tenCoSo' => $p->coSo?->ten_co_so ?? '',
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => "Lấy thành công {$data->count()} phòng trống"
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
     * GET /api/pdt/phong-hoc/khoa/{khoaId}
     * Get rooms by department
     */
    public function byKhoa(Request $request, $khoaId)
    {
        try {
            if (!$khoaId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thiếu khoaId'
                ], 400);
            }

            $phongs = Phong::with('coSo')
                ->where('khoa_id', $khoaId)
                ->orderBy('ma_phong', 'asc')
                ->get();

            $data = $phongs->map(function ($p) {
                return [
                    'id' => $p->id,
                    'maPhong' => $p->ma_phong ?? '',
                    'sucChua' => $p->suc_chua ?? 0,
                    'daSuDung' => $p->da_dc_su_dung ?? false,
                    'khoaId' => $p->khoa_id,
                    'coSoId' => $p->co_so_id,
                    'tenCoSo' => $p->coSo?->ten_co_so ?? '',
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => "Lấy thành công {$data->count()} phòng của khoa"
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
     * POST /api/pdt/phong-hoc/assign
     * Assign room(s) to department
     * Body: { "phongId": "uuid" | ["uuid1", "uuid2"], "khoaId": "uuid" }
     */
    public function assign(Request $request)
    {
        try {
            $phongId = $request->input('phongId') ?? $request->input('phong_id');
            $khoaId = $request->input('khoaId') ?? $request->input('khoa_id');

            if (!$phongId || !$khoaId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thiếu phongId hoặc khoaId'
                ], 400);
            }

            // Handle both single and array
            $phongIds = is_array($phongId) ? $phongId : [$phongId];

            $updated = Phong::whereIn('id', $phongIds)->update(['khoa_id' => $khoaId]);

            return response()->json([
                'isSuccess' => true,
                'data' => ['updatedCount' => $updated],
                'message' => "Đã gán {$updated} phòng cho khoa"
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
     * POST /api/pdt/phong-hoc/unassign
     * Unassign room(s) from department
     * Body: { "phongId": "uuid" } or { "phongHocIds": ["uuid1", "uuid2"] }
     */
    public function unassign(Request $request)
    {
        try {
            $phongIds = $request->input('phongHocIds') ?? $request->input('phong_hoc_ids');
            $phongId = $request->input('phongId') ?? $request->input('phong_id');

            // If single phongId is provided, convert to list
            if ($phongId && !$phongIds) {
                $phongIds = [$phongId];
            }

            if (empty($phongIds)) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thiếu phongId hoặc phongHocIds'
                ], 400);
            }

            $updated = Phong::whereIn('id', $phongIds)->update(['khoa_id' => null]);

            return response()->json([
                'isSuccess' => true,
                'data' => ['updatedCount' => $updated],
                'message' => "Đã gỡ {$updated} phòng khỏi khoa"
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
