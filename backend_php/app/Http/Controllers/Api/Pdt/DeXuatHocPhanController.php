<?php

namespace App\Http\Controllers\Api\Pdt;

use App\Http\Controllers\Controller;
use App\Infrastructure\TLK\Persistence\Models\DeXuatHocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\HocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\MonHoc;
use App\Infrastructure\Common\Persistence\Models\HocKy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeXuatHocPhanController extends Controller
{
    /**
     * GET /api/pdt/de-xuat-hoc-phan
     * Get course proposals that TK has approved (waiting for PDT approval)
     */
    public function index(Request $request)
    {
        try {
            // Get proposals with trang_thai = 'da_duyet_tk'
            $deXuats = DeXuatHocPhan::with(['monHoc', 'giangVienDeXuat.user', 'nguoiTao', 'khoa'])
                ->where('trang_thai', 'da_duyet_tk')
                ->orderBy('created_at', 'desc')
                ->get();

            $data = $deXuats->map(function ($dx) {
                $monHoc = $dx->monHoc;
                $gvDeXuat = $dx->giangVienDeXuat;

                return [
                    'id' => $dx->id,
                    'maHocPhan' => $monHoc?->ma_mon ?? '',
                    'tenHocPhan' => $monHoc?->ten_mon ?? '',
                    'soTinChi' => $monHoc?->so_tin_chi ?? 0,
                    'giangVien' => $gvDeXuat?->user?->ho_ten ?? '',
                    'trangThai' => $dx->trang_thai ?? '',
                    'soLopDuKien' => $dx->so_lop_du_kien ?? 0,
                    'khoa' => $dx->khoa?->ten_khoa ?? '',
                    'ngayTao' => $dx->created_at?->toISOString(),
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => "Lấy thành công {$data->count()} đề xuất chờ duyệt PDT"
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
     * POST /api/pdt/de-xuat-hoc-phan
     * Create course proposal (PDT can also create)
     * Body: { "monHocId": "uuid", "hocKyId": "uuid", "soLopDuKien": 2, "giangVienDeXuat": "uuid" }
     */
    public function store(Request $request)
    {
        try {
            $monHocId = $request->input('monHocId') ?? $request->input('mon_hoc_id');
            $hocKyId = $request->input('hocKyId') ?? $request->input('hoc_ky_id');
            $soLopDuKien = $request->input('soLopDuKien') ?? $request->input('so_lop_du_kien') ?? 1;
            $giangVienDeXuat = $request->input('giangVienDeXuat') ?? $request->input('giang_vien_de_xuat');

            if (!$monHocId || !$hocKyId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thiếu thông tin (monHocId, hocKyId)'
                ], 400);
            }

            // Check monHoc exists
            $monHoc = MonHoc::find($monHocId);
            if (!$monHoc) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Môn học không tồn tại'
                ], 404);
            }

            // Check hocKy exists
            $hocKy = HocKy::find($hocKyId);
            if (!$hocKy) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Học kỳ không tồn tại'
                ], 404);
            }

            // Create proposal (PDT creates directly with da_duyet_tk status to skip TK approval)
            $deXuat = DeXuatHocPhan::create([
                'id' => Str::uuid()->toString(),
                'khoa_id' => $monHoc->khoa_id,
                'nguoi_tao' => null, // TODO: get from auth user
                'hoc_ky_id' => $hocKyId,
                'mon_hoc_id' => $monHocId,
                'so_lop_du_kien' => $soLopDuKien,
                'giang_vien_de_xuat' => $giangVienDeXuat,
                'trang_thai' => 'da_duyet_tk', // PDT creates with TK approved status
                'cap_duyet_hien_tai' => 'pdt',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'isSuccess' => true,
                'data' => [
                    'id' => $deXuat->id,
                    'monHocId' => $deXuat->mon_hoc_id,
                    'hocKyId' => $deXuat->hoc_ky_id,
                    'trangThai' => $deXuat->trang_thai,
                ],
                'message' => 'Tạo đề xuất thành công'
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
     * POST /api/pdt/de-xuat-hoc-phan/duyet
     * Approve course proposal
     * Body: { "id": "uuid" }
     */
    public function duyet(Request $request)
    {
        try {
            $deXuatId = $request->input('id');

            if (!$deXuatId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'ID đề xuất không được rỗng'
                ], 400);
            }

            // Find proposal with da_duyet_tk status
            $deXuat = DeXuatHocPhan::with(['monHoc', 'hocKy'])
                ->where('trang_thai', 'da_duyet_tk')
                ->find($deXuatId);

            if (!$deXuat) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy đề xuất hoặc đề xuất chưa được TK duyệt'
                ], 404);
            }

            DB::transaction(function () use ($deXuat) {
                // 1. Update status to da_duyet_pdt
                $deXuat->trang_thai = 'da_duyet_pdt';
                $deXuat->cap_duyet_hien_tai = 'pdt'; // Keep as 'pdt' - constraint allows only 'truong_khoa' or 'pdt'
                $deXuat->updated_at = now();
                $deXuat->save();

                // 2. Create or update HocPhan
                $existingHocPhan = HocPhan::where('mon_hoc_id', $deXuat->mon_hoc_id)
                    ->where('id_hoc_ky', $deXuat->hoc_ky_id)
                    ->first();

                if ($existingHocPhan) {
                    // Increase so_lop
                    $existingHocPhan->so_lop = ($existingHocPhan->so_lop ?? 0) + $deXuat->so_lop_du_kien;
                    $existingHocPhan->updated_at = now();
                    $existingHocPhan->save();
                } else {
                    // Create new HocPhan
                    HocPhan::create([
                        'id' => Str::uuid()->toString(),
                        'mon_hoc_id' => $deXuat->mon_hoc_id,
                        'ten_hoc_phan' => $deXuat->monHoc?->ten_mon ?? 'Học phần mới',
                        'so_lop' => $deXuat->so_lop_du_kien,
                        'trang_thai_mo' => true,
                        'id_hoc_ky' => $deXuat->hoc_ky_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });

            return response()->json([
                'isSuccess' => true,
                'data' => [
                    'id' => $deXuat->id,
                    'trangThai' => $deXuat->trang_thai,
                ],
                'message' => 'Duyệt đề xuất thành công'
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
     * POST /api/pdt/de-xuat-hoc-phan/tu-choi
     * Reject course proposal
     * Body: { "id": "uuid", "lyDo": "reason" }
     */
    public function tuChoi(Request $request)
    {
        try {
            $deXuatId = $request->input('id');
            $lyDo = $request->input('lyDo') ?? $request->input('ly_do') ?? '';

            if (!$deXuatId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'ID đề xuất không được rỗng'
                ], 400);
            }

            // Find proposal
            $deXuat = DeXuatHocPhan::find($deXuatId);

            if (!$deXuat) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy đề xuất'
                ], 404);
            }

            // Update status to tu_choi
            $deXuat->trang_thai = 'tu_choi';
            $deXuat->cap_duyet_hien_tai = 'pdt'; // Keep as 'pdt' - constraint allows only 'truong_khoa' or 'pdt'
            $deXuat->ghi_chu = $lyDo ? "Lý do từ chối PDT: {$lyDo}" : 'Bị từ chối bởi Phòng Đào Tạo';
            $deXuat->updated_at = now();
            $deXuat->save();

            return response()->json([
                'isSuccess' => true,
                'data' => [
                    'id' => $deXuat->id,
                    'trangThai' => $deXuat->trang_thai,
                ],
                'message' => 'Từ chối đề xuất thành công'
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
