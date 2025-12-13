<?php

namespace App\Http\Controllers\Api\Pdt;

use App\Http\Controllers\Controller;
use App\Domain\Pdt\Repositories\ChinhSachTinChiRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * ChinhSachTinChiController - Quản lý chính sách tín chỉ (Refactored - Clean Architecture)
 * 
 * Thin controller - delegates data access to Repository
 */
class ChinhSachTinChiController extends Controller
{
    public function __construct(
        private ChinhSachTinChiRepositoryInterface $repository
    ) {
    }

    /**
     * GET /api/pdt/chinh-sach-tin-chi
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $policies = $this->repository->getAll();

            $data = $policies->map(function ($p) {
                return [
                    'id' => $p->id,
                    'hocKy' => $p->hocKy ? [
                        'tenHocKy' => $p->hocKy->ten_hoc_ky ?? null,
                        'maHocKy' => $p->hocKy->ma_hoc_ky ?? null,
                    ] : null,
                    'khoa' => $p->khoa ? ['tenKhoa' => $p->khoa->ten_khoa ?? null] : null,
                    'nganhHoc' => $p->nganh ? ['tenNganh' => $p->nganh->ten_nganh ?? null] : null,
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
            return response()->json(['isSuccess' => false, 'data' => null, 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/pdt/chinh-sach-tin-chi/{id}
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $policy = $this->repository->findById($id);

            if (!$policy) {
                return response()->json(['isSuccess' => false, 'data' => null, 'message' => 'Không tìm thấy chính sách'], 404);
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

            return response()->json(['isSuccess' => true, 'data' => $data, 'message' => 'Lấy thành công']);
        } catch (\Throwable $e) {
            return response()->json(['isSuccess' => false, 'data' => null, 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/pdt/chinh-sach-tin-chi
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $hocKyId = $request->input('hocKyId') ?? $request->input('hoc_ky_id');
            $phiMoiTinChi = $request->input('phiMoiTinChi') ?? $request->input('phi_moi_tin_chi');

            if (!$hocKyId || !$phiMoiTinChi) {
                return response()->json(['isSuccess' => false, 'data' => null, 'message' => 'Thiếu thông tin bắt buộc'], 400);
            }

            $ngayHieuLuc = $request->input('ngayHieuLuc') ?? $request->input('ngay_hieu_luc');
            $ngayHetHieuLuc = $request->input('ngayHetHieuLuc') ?? $request->input('ngay_het_hieu_luc');

            if (!$ngayHieuLuc || !$ngayHetHieuLuc) {
                $hocKy = $this->repository->getHocKy($hocKyId);
                $ngayHieuLuc = $ngayHieuLuc ?? ($hocKy?->ngay_bat_dau?->format('Y-m-d') ?? now()->format('Y-m-d'));
                $ngayHetHieuLuc = $ngayHetHieuLuc ?? ($hocKy?->ngay_ket_thuc?->format('Y-m-d') ?? now()->addMonths(6)->format('Y-m-d'));
            }

            $policy = $this->repository->create([
                'id' => Str::uuid()->toString(),
                'hoc_ky_id' => $hocKyId,
                'khoa_id' => $request->input('khoaId') ?? $request->input('khoa_id'),
                'nganh_id' => $request->input('nganhId') ?? $request->input('nganh_id'),
                'phi_moi_tin_chi' => $phiMoiTinChi,
                'ngay_hieu_luc' => $ngayHieuLuc,
                'ngay_het_hieu_luc' => $ngayHetHieuLuc,
            ]);

            return response()->json(['isSuccess' => true, 'data' => ['id' => $policy->id], 'message' => 'Tạo chính sách thành công'], 201);
        } catch (\Throwable $e) {
            return response()->json(['isSuccess' => false, 'data' => null, 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * PUT /api/pdt/chinh-sach-tin-chi/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $policy = $this->repository->findById($id);
            if (!$policy) {
                return response()->json(['isSuccess' => false, 'data' => null, 'message' => 'Không tìm thấy chính sách'], 404);
            }

            $updateData = [];
            if ($request->has('phiMoiTinChi') || $request->has('phi_moi_tin_chi')) {
                $updateData['phi_moi_tin_chi'] = $request->input('phiMoiTinChi') ?? $request->input('phi_moi_tin_chi');
            }
            if ($request->has('ngayHieuLuc') || $request->has('ngay_hieu_luc')) {
                $updateData['ngay_hieu_luc'] = $request->input('ngayHieuLuc') ?? $request->input('ngay_hieu_luc');
            }
            if ($request->has('ngayHetHieuLuc') || $request->has('ngay_het_hieu_luc')) {
                $updateData['ngay_het_hieu_luc'] = $request->input('ngayHetHieuLuc') ?? $request->input('ngay_het_hieu_luc');
            }
            if ($request->has('khoaId') || $request->has('khoa_id')) {
                $updateData['khoa_id'] = $request->input('khoaId') ?? $request->input('khoa_id');
            }
            if ($request->has('nganhId') || $request->has('nganh_id')) {
                $updateData['nganh_id'] = $request->input('nganhId') ?? $request->input('nganh_id');
            }

            $this->repository->update($id, $updateData);

            return response()->json(['isSuccess' => true, 'data' => ['id' => $id], 'message' => 'Cập nhật chính sách thành công']);
        } catch (\Throwable $e) {
            return response()->json(['isSuccess' => false, 'data' => null, 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/pdt/chinh-sach-tin-chi/{id}
     */
    public function destroy($id): JsonResponse
    {
        try {
            $policy = $this->repository->findById($id);
            if (!$policy) {
                return response()->json(['isSuccess' => false, 'data' => null, 'message' => 'Không tìm thấy chính sách'], 404);
            }

            $this->repository->delete($id);

            return response()->json(['isSuccess' => true, 'data' => null, 'message' => 'Xóa chính sách thành công']);
        } catch (\Throwable $e) {
            return response()->json(['isSuccess' => false, 'data' => null, 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }
}
