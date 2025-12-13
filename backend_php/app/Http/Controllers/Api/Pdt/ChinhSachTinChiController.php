<?php

namespace App\Http\Controllers\Api\Pdt;

use App\Http\Controllers\Controller;
use App\Infrastructure\SinhVien\Persistence\Models\ChinhSachTinChi;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChinhSachTinChiController extends Controller
{
    /**
     * GET /api/pdt/chinh-sach-tin-chi
     * Get all tuition policies
     */
    public function index(Request $request)
    {
        try {
            $policies = ChinhSachTinChi::with(['hocKy', 'khoa', 'nganh'])
                ->orderBy('ngay_hieu_luc', 'desc')
                ->get();

            $data = $policies->map(function ($p) {
                return [
                    'id' => $p->id,
                    'hocKy' => $p->hocKy ? [
                        'tenHocKy' => $p->hocKy->ten_hoc_ky ?? null,
                        'maHocKy' => $p->hocKy->ma_hoc_ky ?? null,
                    ] : null,
                    'khoa' => $p->khoa ? [
                        'tenKhoa' => $p->khoa->ten_khoa ?? null,
                    ] : null,
                    'nganhHoc' => $p->nganh ? [
                        'tenNganh' => $p->nganh->ten_nganh ?? null,
                    ] : null,
                    'phiMoiTinChi' => (float) $p->phi_moi_tin_chi,
                    'ngayHieuLuc' => $p->ngay_hieu_luc?->format('Y-m-d'),
                    'ngayHetHieuLuc' => $p->ngay_het_hieu_luc?->format('Y-m-d'),
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => "Lấy thành công {$data->count()} chính sách"
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
     * GET /api/pdt/chinh-sach-tin-chi/{id}
     * Get policy details
     */
    public function show(Request $request, $id)
    {
        try {
            $policy = ChinhSachTinChi::with(['hocKy', 'khoa', 'nganh'])->find($id);

            if (!$policy) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy chính sách'
                ], 404);
            }

            $data = [
                'id' => $policy->id,
                'hocKyId' => $policy->hoc_ky_id,
                'tenHocKy' => $policy->hocKy?->ten_hoc_ky ?? '',
                'khoaId' => $policy->khoa_id,
                'tenKhoa' => $policy->khoa?->ten_khoa ?? '',
                'nganhId' => $policy->nganh_id,
                'tenNganh' => $policy->nganh?->ten_nganh ?? '',
                'phiMoiTinChi' => (float) $policy->phi_moi_tin_chi,
                'ngayHieuLuc' => $policy->ngay_hieu_luc?->format('Y-m-d'),
                'ngayHetHieuLuc' => $policy->ngay_het_hieu_luc?->format('Y-m-d'),
            ];

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => 'Lấy thành công'
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
     * POST /api/pdt/chinh-sach-tin-chi
     * Create tuition policy
     */
    public function store(Request $request)
    {
        try {
            $hocKyId = $request->input('hocKyId') ?? $request->input('hoc_ky_id');
            $phiMoiTinChi = $request->input('phiMoiTinChi') ?? $request->input('phi_moi_tin_chi');

            if (!$hocKyId || !$phiMoiTinChi) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thiếu thông tin bắt buộc (hocKyId, phiMoiTinChi)'
                ], 400);
            }

            // Get dates - if not provided, use HocKy dates or default to today + 6 months
            $ngayHieuLuc = $request->input('ngayHieuLuc') ?? $request->input('ngay_hieu_luc');
            $ngayHetHieuLuc = $request->input('ngayHetHieuLuc') ?? $request->input('ngay_het_hieu_luc');

            if (!$ngayHieuLuc || !$ngayHetHieuLuc) {
                // Try to get dates from HocKy
                $hocKy = \App\Infrastructure\Common\Persistence\Models\HocKy::find($hocKyId);
                if ($hocKy) {
                    $ngayHieuLuc = $ngayHieuLuc ?? $hocKy->ngay_bat_dau?->format('Y-m-d') ?? now()->format('Y-m-d');
                    $ngayHetHieuLuc = $ngayHetHieuLuc ?? $hocKy->ngay_ket_thuc?->format('Y-m-d') ?? now()->addMonths(6)->format('Y-m-d');
                } else {
                    $ngayHieuLuc = $ngayHieuLuc ?? now()->format('Y-m-d');
                    $ngayHetHieuLuc = $ngayHetHieuLuc ?? now()->addMonths(6)->format('Y-m-d');
                }
            }

            $policy = ChinhSachTinChi::create([
                'id' => Str::uuid()->toString(),
                'hoc_ky_id' => $hocKyId,
                'khoa_id' => $request->input('khoaId') ?? $request->input('khoa_id'),
                'nganh_id' => $request->input('nganhId') ?? $request->input('nganh_id'),
                'phi_moi_tin_chi' => $phiMoiTinChi,
                'ngay_hieu_luc' => $ngayHieuLuc,
                'ngay_het_hieu_luc' => $ngayHetHieuLuc,
            ]);

            return response()->json([
                'isSuccess' => true,
                'data' => ['id' => $policy->id],
                'message' => 'Tạo chính sách thành công'
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /api/pdt/chinh-sach-tin-chi/{id}
     * Update tuition policy
     */
    public function update(Request $request, $id)
    {
        try {
            $policy = ChinhSachTinChi::find($id);

            if (!$policy) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy chính sách'
                ], 404);
            }

            // Update fields if provided
            if ($request->has('phiMoiTinChi') || $request->has('phi_moi_tin_chi')) {
                $policy->phi_moi_tin_chi = $request->input('phiMoiTinChi') ?? $request->input('phi_moi_tin_chi');
            }
            if ($request->has('ngayHieuLuc') || $request->has('ngay_hieu_luc')) {
                $policy->ngay_hieu_luc = $request->input('ngayHieuLuc') ?? $request->input('ngay_hieu_luc');
            }
            if ($request->has('ngayHetHieuLuc') || $request->has('ngay_het_hieu_luc')) {
                $policy->ngay_het_hieu_luc = $request->input('ngayHetHieuLuc') ?? $request->input('ngay_het_hieu_luc');
            }
            if ($request->has('khoaId') || $request->has('khoa_id')) {
                $policy->khoa_id = $request->input('khoaId') ?? $request->input('khoa_id');
            }
            if ($request->has('nganhId') || $request->has('nganh_id')) {
                $policy->nganh_id = $request->input('nganhId') ?? $request->input('nganh_id');
            }


            $policy->save();

            return response()->json([
                'isSuccess' => true,
                'data' => ['id' => $policy->id],
                'message' => 'Cập nhật chính sách thành công'
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
     * DELETE /api/pdt/chinh-sach-tin-chi/{id}
     * Delete tuition policy
     */
    public function destroy($id)
    {
        try {
            $policy = ChinhSachTinChi::find($id);

            if (!$policy) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy chính sách'
                ], 404);
            }

            $policy->delete();

            return response()->json([
                'isSuccess' => true,
                'data' => null,
                'message' => 'Xóa chính sách thành công'
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
