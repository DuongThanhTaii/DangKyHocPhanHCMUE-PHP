<?php

namespace App\Http\Controllers\Api\Pdt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Infrastructure\SinhVien\Persistence\Models\DangKyHocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\LopHocPhan;
use App\Infrastructure\Payment\Persistence\Models\HocPhi;

/**
 * BaoCaoController - Thống kê báo cáo
 * 
 * Endpoints:
 * - GET /bao-cao/overview         - Thống kê tổng quan
 * - GET /bao-cao/dk-theo-khoa     - Thống kê đăng ký theo khoa
 * - GET /bao-cao/dk-theo-nganh    - Thống kê đăng ký theo ngành
 * - GET /bao-cao/tai-giang-vien   - Thống kê tải giảng viên
 */
class BaoCaoController extends Controller
{
    /**
     * GET /bao-cao/overview
     * 
     * Thống kê tổng quan: SV unique, số đăng ký, số LHP, tài chính
     */
    public function overview(Request $request)
    {
        $hocKyId = $request->query('hoc_ky_id');
        $khoaId = $request->query('khoa_id');
        $nganhId = $request->query('nganh_id');

        if (!$hocKyId) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'hoc_ky_id is required'
            ], 400);
        }

        try {
            // Build base query for DangKyHocPhan
            $dkQuery = DangKyHocPhan::query()
                ->join('lop_hoc_phan', 'dang_ky_hoc_phan.lop_hoc_phan_id', '=', 'lop_hoc_phan.id')
                ->join('hoc_phan', 'lop_hoc_phan.hoc_phan_id', '=', 'hoc_phan.id')
                ->where('hoc_phan.id_hoc_ky', $hocKyId);

            // Build base query for LopHocPhan
            $lhpQuery = LopHocPhan::query()
                ->join('hoc_phan', 'lop_hoc_phan.hoc_phan_id', '=', 'hoc_phan.id')
                ->where('hoc_phan.id_hoc_ky', $hocKyId);

            // Build base query for HocPhi
            $hpQuery = HocPhi::query()
                ->where('hoc_ky_id', $hocKyId);

            // Apply Khoa filter if provided
            if ($khoaId) {
                $dkQuery->join('sinh_vien', 'dang_ky_hoc_phan.sinh_vien_id', '=', 'sinh_vien.id')
                    ->where('sinh_vien.khoa_id', $khoaId);

                $lhpQuery->join('mon_hoc', 'hoc_phan.mon_hoc_id', '=', 'mon_hoc.id')
                    ->where('mon_hoc.khoa_id', $khoaId);

                $hpQuery->join('sinh_vien as sv_hp', 'hoc_phi.sinh_vien_id', '=', 'sv_hp.id')
                    ->where('sv_hp.khoa_id', $khoaId);
            }

            // Apply Nganh filter if provided
            if ($nganhId) {
                if (!$khoaId) {
                    $dkQuery->join('sinh_vien', 'dang_ky_hoc_phan.sinh_vien_id', '=', 'sinh_vien.id');
                    $hpQuery->join('sinh_vien as sv_hp', 'hoc_phi.sinh_vien_id', '=', 'sv_hp.id');
                }
                $dkQuery->where('sinh_vien.nganh_id', $nganhId);
                $hpQuery->where('sv_hp.nganh_id', $nganhId);
            }

            // 1. SV Unique count
            $svUnique = (clone $dkQuery)
                ->distinct('dang_ky_hoc_phan.sinh_vien_id')
                ->count('dang_ky_hoc_phan.sinh_vien_id');

            // 2. Total registrations
            $soDangKy = (clone $dkQuery)->count('dang_ky_hoc_phan.id');

            // 3. Total class sections
            $soLopHocPhan = (clone $lhpQuery)->count('lop_hoc_phan.id');

            // 4. Financials
            $kyVong = (clone $hpQuery)->sum('tong_hoc_phi') ?? 0;
            $thucThu = (clone $hpQuery)
                ->where('trang_thai_thanh_toan', 'DA_THANH_TOAN')
                ->sum('tong_hoc_phi') ?? 0;

            return response()->json([
                'isSuccess' => true,
                'data' => [
                    'svUnique' => $svUnique,
                    'soDangKy' => $soDangKy,
                    'soLopHocPhan' => $soLopHocPhan,
                    'taiChinh' => [
                        'thuc_thu' => (float) $thucThu,
                        'ky_vong' => (float) $kyVong,
                    ],
                    'ketLuan' => "Tổng quan: {$svUnique} sinh viên đã đăng ký {$soDangKy} lượt học phần.",
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /bao-cao/dk-theo-khoa
     * 
     * Thống kê số lượng đăng ký theo từng khoa
     */
    public function dangKyTheoKhoa(Request $request)
    {
        $hocKyId = $request->query('hoc_ky_id');

        if (!$hocKyId) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'hoc_ky_id is required'
            ], 400);
        }

        try {
            $stats = DB::table('dang_ky_hoc_phan')
                ->join('lop_hoc_phan', 'dang_ky_hoc_phan.lop_hoc_phan_id', '=', 'lop_hoc_phan.id')
                ->join('hoc_phan', 'lop_hoc_phan.hoc_phan_id', '=', 'hoc_phan.id')
                ->join('sinh_vien', 'dang_ky_hoc_phan.sinh_vien_id', '=', 'sinh_vien.id')
                ->join('khoa', 'sinh_vien.khoa_id', '=', 'khoa.id')
                ->where('hoc_phan.id_hoc_ky', $hocKyId)
                ->select('khoa.ten_khoa', DB::raw('COUNT(dang_ky_hoc_phan.id) as so_dang_ky'))
                ->groupBy('khoa.id', 'khoa.ten_khoa')
                ->orderByDesc('so_dang_ky')
                ->get();

            $data = $stats->map(function ($item) {
                return [
                    'ten_khoa' => $item->ten_khoa,
                    'so_dang_ky' => (int) $item->so_dang_ky,
                ];
            })->toArray();

            return response()->json([
                'isSuccess' => true,
                'data' => [
                    'data' => $data,
                    'ketLuan' => 'Thống kê số lượng đăng ký theo từng khoa.',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /bao-cao/dk-theo-nganh
     * 
     * Thống kê số lượng đăng ký theo ngành
     */
    public function dangKyTheoNganh(Request $request)
    {
        $hocKyId = $request->query('hoc_ky_id');
        $khoaId = $request->query('khoa_id');

        if (!$hocKyId) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'hoc_ky_id is required'
            ], 400);
        }

        try {
            $query = DB::table('dang_ky_hoc_phan')
                ->join('lop_hoc_phan', 'dang_ky_hoc_phan.lop_hoc_phan_id', '=', 'lop_hoc_phan.id')
                ->join('hoc_phan', 'lop_hoc_phan.hoc_phan_id', '=', 'hoc_phan.id')
                ->join('sinh_vien', 'dang_ky_hoc_phan.sinh_vien_id', '=', 'sinh_vien.id')
                ->leftJoin('nganh_hoc', 'sinh_vien.nganh_id', '=', 'nganh_hoc.id')
                ->where('hoc_phan.id_hoc_ky', $hocKyId);

            if ($khoaId) {
                $query->where('sinh_vien.khoa_id', $khoaId);
            }

            $stats = $query
                ->select(
                    DB::raw('COALESCE(nganh_hoc.ten_nganh, \'Chưa phân ngành\') as ten_nganh'),
                    DB::raw('COUNT(dang_ky_hoc_phan.id) as so_dang_ky')
                )
                ->groupBy('nganh_hoc.id', 'nganh_hoc.ten_nganh')
                ->orderByDesc('so_dang_ky')
                ->get();

            $data = $stats->map(function ($item) {
                return [
                    'ten_nganh' => $item->ten_nganh,
                    'so_dang_ky' => (int) $item->so_dang_ky,
                ];
            })->toArray();

            return response()->json([
                'isSuccess' => true,
                'data' => [
                    'data' => $data,
                    'ketLuan' => 'Thống kê số lượng đăng ký theo ngành.',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /bao-cao/tai-giang-vien
     * 
     * Thống kê số lượng lớp học phần theo giảng viên
     */
    public function taiGiangVien(Request $request)
    {
        $hocKyId = $request->query('hoc_ky_id');
        $khoaId = $request->query('khoa_id');

        if (!$hocKyId) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'hoc_ky_id is required'
            ], 400);
        }

        try {
            $query = DB::table('lop_hoc_phan')
                ->join('hoc_phan', 'lop_hoc_phan.hoc_phan_id', '=', 'hoc_phan.id')
                ->leftJoin('users', 'lop_hoc_phan.giang_vien_id', '=', 'users.id')
                ->where('hoc_phan.id_hoc_ky', $hocKyId);

            if ($khoaId) {
                $query->join('giang_vien', 'users.id', '=', 'giang_vien.id')
                    ->where('giang_vien.khoa_id', $khoaId);
            }

            $stats = $query
                ->select(
                    DB::raw('COALESCE(users.ho_ten, \'Chưa phân công\') as ho_ten'),
                    DB::raw('COUNT(lop_hoc_phan.id) as so_lop')
                )
                ->groupBy('users.id', 'users.ho_ten')
                ->orderByDesc('so_lop')
                ->get();

            $data = $stats->map(function ($item) {
                return [
                    'ho_ten' => $item->ho_ten,
                    'so_lop' => (int) $item->so_lop,
                ];
            })->toArray();

            return response()->json([
                'isSuccess' => true,
                'data' => [
                    'data' => $data,
                    'ketLuan' => 'Thống kê số lượng lớp học phần theo giảng viên.',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
