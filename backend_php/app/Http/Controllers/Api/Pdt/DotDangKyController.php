<?php

namespace App\Http\Controllers\Api\Pdt;

use App\Http\Controllers\Controller;
use App\Infrastructure\SinhVien\Persistence\Models\DotDangKy;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DotDangKyController extends Controller
{
    /**
     * GET /api/pdt/dot-dang-ky
     * Get all registration periods (with optional hoc_ky_id filter)
     */
    public function index(Request $request)
    {
        try {
            $hocKyId = $request->query('hoc_ky_id') ?? $request->query('hocKyId');

            $query = DotDangKy::with(['hocKy', 'khoa']);

            if ($hocKyId) {
                $query->where('hoc_ky_id', $hocKyId);
            }

            $dotDangKys = $query->orderBy('thoi_gian_bat_dau', 'desc')->get();

            $data = $dotDangKys->map(function ($d) {
                return [
                    'id' => $d->id,
                    'hocKyId' => $d->hoc_ky_id,
                    'tenHocKy' => $d->hocKy?->ten_hoc_ky ?? '',
                    'loaiDot' => $d->loai_dot,
                    'gioiHanTinChi' => $d->gioi_han_tin_chi ?? 0,
                    'thoiGianBatDau' => $d->thoi_gian_bat_dau?->toISOString(),
                    'thoiGianKetThuc' => $d->thoi_gian_ket_thuc?->toISOString(),
                    'isCheckToanTruong' => $d->is_check_toan_truong ?? false,
                    'khoaId' => $d->khoa_id,
                    'tenKhoa' => $d->khoa?->ten_khoa ?? '',
                    'isActive' => $d->isActive(),
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => "Lấy thành công {$data->count()} đợt đăng ký"
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
     * GET /api/pdt/dot-dang-ky/{hocKyId}
     * Get registration periods by semester
     */
    public function getByHocKy(Request $request, $hocKyId)
    {
        try {
            if (!$hocKyId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thiếu hocKyId'
                ], 400);
            }

            $dotDangKys = DotDangKy::with(['hocKy', 'khoa'])
                ->where('hoc_ky_id', $hocKyId)
                ->orderBy('thoi_gian_bat_dau', 'asc')
                ->get();

            $data = $dotDangKys->map(function ($d) {
                return [
                    'id' => $d->id,
                    'hocKyId' => $d->hoc_ky_id,
                    'loaiDot' => $d->loai_dot,
                    'gioiHanTinChi' => $d->gioi_han_tin_chi ?? 0,
                    'thoiGianBatDau' => $d->thoi_gian_bat_dau?->toISOString(),
                    'thoiGianKetThuc' => $d->thoi_gian_ket_thuc?->toISOString(),
                    'isCheckToanTruong' => $d->is_check_toan_truong ?? false,
                    'khoaId' => $d->khoa_id,
                    'tenKhoa' => $d->khoa?->ten_khoa ?? '',
                    'isActive' => $d->isActive(),
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => "Lấy thành công {$data->count()} đợt đăng ký"
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
     * POST /api/pdt/dot-ghi-danh/update
     * Update enrollment period
     * Body: { "id": "uuid", "thoiGianBatDau": "...", "thoiGianKetThuc": "...", "gioiHanTinChi": 50 }
     */
    public function update(Request $request)
    {
        try {
            $id = $request->input('id');

            if (!$id) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thiếu ID đợt đăng ký'
                ], 400);
            }

            $dotDangKy = DotDangKy::find($id);

            if (!$dotDangKy) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy đợt đăng ký'
                ], 404);
            }

            // Update fields if provided
            if ($request->has('thoiGianBatDau') || $request->has('thoi_gian_bat_dau')) {
                $dotDangKy->thoi_gian_bat_dau = $request->input('thoiGianBatDau') ?? $request->input('thoi_gian_bat_dau');
            }
            if ($request->has('thoiGianKetThuc') || $request->has('thoi_gian_ket_thuc')) {
                $dotDangKy->thoi_gian_ket_thuc = $request->input('thoiGianKetThuc') ?? $request->input('thoi_gian_ket_thuc');
            }
            if ($request->has('gioiHanTinChi') || $request->has('gioi_han_tin_chi')) {
                $dotDangKy->gioi_han_tin_chi = $request->input('gioiHanTinChi') ?? $request->input('gioi_han_tin_chi');
            }
            if ($request->has('isCheckToanTruong') || $request->has('is_check_toan_truong')) {
                $dotDangKy->is_check_toan_truong = $request->input('isCheckToanTruong') ?? $request->input('is_check_toan_truong');
            }

            $dotDangKy->updated_at = now();
            $dotDangKy->save();

            return response()->json([
                'isSuccess' => true,
                'data' => [
                    'id' => $dotDangKy->id,
                    'loaiDot' => $dotDangKy->loai_dot,
                    'thoiGianBatDau' => $dotDangKy->thoi_gian_bat_dau?->toISOString(),
                    'thoiGianKetThuc' => $dotDangKy->thoi_gian_ket_thuc?->toISOString(),
                ],
                'message' => 'Cập nhật đợt đăng ký thành công'
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
