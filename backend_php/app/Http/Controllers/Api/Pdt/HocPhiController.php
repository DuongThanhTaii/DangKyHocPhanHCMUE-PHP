<?php

namespace App\Http\Controllers\Api\Pdt;

use App\Http\Controllers\Controller;
use App\Infrastructure\SinhVien\Persistence\Models\ChinhSachTinChi;
use App\Infrastructure\SinhVien\Persistence\Models\DangKyHocPhan;
use App\Infrastructure\Payment\Persistence\Models\HocPhi;
use App\Infrastructure\Pdt\Persistence\Models\SinhVien;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HocPhiController extends Controller
{
    /**
     * POST /api/pdt/hoc-phi/tinh-toan-hang-loat
     * Calculate tuition for all students in a semester
     * Body: { "hocKyId": "uuid" }
     */
    public function tinhToanHangLoat(Request $request)
    {
        try {
            $hocKyId = $request->input('hocKyId') ?? $request->input('hoc_ky_id');

            if (!$hocKyId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thiếu hocKyId'
                ], 400);
            }

            // Get all students with registered courses in this semester
            $studentIds = DangKyHocPhan::join('lop_hoc_phan', 'dang_ky_hoc_phan.lop_hoc_phan_id', '=', 'lop_hoc_phan.id')
                ->join('hoc_phan', 'lop_hoc_phan.hoc_phan_id', '=', 'hoc_phan.id')
                ->where('hoc_phan.id_hoc_ky', $hocKyId)
                ->whereIn('dang_ky_hoc_phan.trang_thai', ['da_dang_ky', 'dang_ky'])
                ->distinct()
                ->pluck('dang_ky_hoc_phan.sinh_vien_id');

            $count = 0;

            foreach ($studentIds as $svId) {
                // Get total credits for this student
                $totalCredits = DangKyHocPhan::join('lop_hoc_phan', 'dang_ky_hoc_phan.lop_hoc_phan_id', '=', 'lop_hoc_phan.id')
                    ->join('hoc_phan', 'lop_hoc_phan.hoc_phan_id', '=', 'hoc_phan.id')
                    ->join('mon_hoc', 'hoc_phan.mon_hoc_id', '=', 'mon_hoc.id')
                    ->where('dang_ky_hoc_phan.sinh_vien_id', $svId)
                    ->where('hoc_phan.id_hoc_ky', $hocKyId)
                    ->whereIn('dang_ky_hoc_phan.trang_thai', ['da_dang_ky', 'dang_ky'])
                    ->sum('mon_hoc.so_tin_chi');

                if ($totalCredits == 0)
                    continue;

                // Find policy for student
                $sv = SinhVien::find($svId);
                if (!$sv)
                    continue;

                // Priority: Nganh > Khoa > General
                $policy = ChinhSachTinChi::where('hoc_ky_id', $hocKyId)
                    ->where(function ($q) use ($sv) {
                        $q->where('nganh_id', $sv->nganh_id)
                            ->orWhere(function ($q2) use ($sv) {
                                $q2->where('khoa_id', $sv->khoa_id)
                                    ->whereNull('nganh_id');
                            })
                            ->orWhere(function ($q3) {
                                $q3->whereNull('khoa_id')
                                    ->whereNull('nganh_id');
                            });
                    })
                    ->orderByRaw('CASE WHEN nganh_id IS NOT NULL THEN 1 WHEN khoa_id IS NOT NULL THEN 2 ELSE 3 END')
                    ->first();

                if (!$policy)
                    continue;

                $totalFee = $totalCredits * $policy->phi_moi_tin_chi;

                // Update or create HocPhi
                HocPhi::updateOrCreate(
                    [
                        'sinh_vien_id' => $svId,
                        'hoc_ky_id' => $hocKyId,
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'tong_hoc_phi' => $totalFee,
                        'chinh_sach_id' => $policy->id,
                        'ngay_tinh_toan' => now(),
                        'trang_thai_thanh_toan' => 'chua_thanh_toan',
                    ]
                );

                $count++;
            }

            return response()->json([
                'isSuccess' => true,
                'data' => ['processedCount' => $count],
                'message' => "Đã tính học phí cho {$count} sinh viên"
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
