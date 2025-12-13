<?php

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;
use App\Infrastructure\Common\Persistence\Models\HocKy;
use App\Infrastructure\Common\Persistence\Models\Khoa;
use App\Infrastructure\Common\Persistence\Models\NganhHoc;
use Illuminate\Http\Request;

class CommonController extends Controller
{
    /**
     * GET /api/hoc-ky-hien-hanh
     * GET /api/hien-hanh (alias)
     * 
     * Get the current active semester
     * Requires authentication
     */
    public function getHocKyHienHanh()
    {
        try {
            // Query for semester with trang_thai_hien_tai = true
            $hocKy = HocKy::with('nienKhoa')
                ->where('trang_thai_hien_tai', true)
                ->first();

            if (!$hocKy) {
                return response()->json([
                    'isSuccess' => true,
                    'data' => null,
                    'message' => 'Không có học kỳ hiện hành'
                ], 404);
            }

            // Map to DTO format matching FE expectations (camelCase)
            $data = [
                'id' => $hocKy->id,
                'tenHocKy' => $hocKy->ten_hoc_ky,
                'maHocKy' => $hocKy->ma_hoc_ky,
                'nienKhoa' => [
                    'id' => $hocKy->nienKhoa?->id ?? $hocKy->id_nien_khoa,
                    'tenNienKhoa' => $hocKy->nienKhoa?->ten_nien_khoa ?? ''
                ],
                'ngayBatDau' => $hocKy->ngay_bat_dau?->toDateString(),
                'ngayKetThuc' => $hocKy->ngay_ket_thuc?->toDateString()
            ];

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => 'Lấy học kỳ hiện hành thành công'
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => 'Lỗi khi lấy học kỳ hiện hành: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/hoc-ky-nien-khoa
     * 
     * Get all semesters grouped by academic years
     * Returns: [{ nienKhoaId, tenNienKhoa, hocKy: [{id, tenHocKy, maHocKy, ngayBatDau, ngayKetThuc}] }]
     * Requires authentication
     */
    public function getHocKyNienKhoa()
    {
        try {
            $hocKys = HocKy::with('nienKhoa')
                ->orderBy('ngay_bat_dau', 'desc')
                ->get();

            // Group by nienKhoa
            $grouped = [];
            foreach ($hocKys as $hocKy) {
                $nienKhoaId = $hocKy->nienKhoa?->id ?? $hocKy->id_nien_khoa;
                $tenNienKhoa = $hocKy->nienKhoa?->ten_nien_khoa ?? '';

                if (!isset($grouped[$nienKhoaId])) {
                    $grouped[$nienKhoaId] = [
                        'nienKhoaId' => $nienKhoaId,
                        'tenNienKhoa' => $tenNienKhoa,
                        'hocKy' => []
                    ];
                }

                $grouped[$nienKhoaId]['hocKy'][] = [
                    'id' => $hocKy->id,
                    'tenHocKy' => $hocKy->ten_hoc_ky,
                    'maHocKy' => $hocKy->ma_hoc_ky,
                    'ngayBatDau' => $hocKy->ngay_bat_dau?->toDateString(),
                    'ngayKetThuc' => $hocKy->ngay_ket_thuc?->toDateString(),
                    'trangThaiHienTai' => $hocKy->trang_thai_hien_tai,
                ];
            }

            $data = array_values($grouped);

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => "Lấy thành công " . count($data) . " niên khóa"
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
     * GET /api/dm/khoa
     * 
     * Get list of departments/faculties
     * Requires authentication
     */
    public function getDanhSachKhoa()
    {
        try {
            $khoas = Khoa::orderBy('ten_khoa')->get();

            $data = $khoas->map(function ($k) {
                return [
                    'id' => $k->id,
                    'maKhoa' => $k->ma_khoa,
                    'tenKhoa' => $k->ten_khoa
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => "Lấy thành công {$data->count()} khoa"
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
     * GET /api/dm/nganh?khoa_id={id}
     * 
     * Get list of programs/majors (specializations)
     * Optional filter by khoa_id
     * Requires authentication
     */
    public function getDanhSachNganh(Request $request)
    {
        try {
            $khoaId = $request->query('khoa_id');

            $query = NganhHoc::with('khoa')->orderBy('ten_nganh');

            if ($khoaId) {
                $query->where('khoa_id', $khoaId);
            }

            $nganhs = $query->get();

            $data = $nganhs->map(function ($n) {
                return [
                    'id' => $n->id,
                    'maNganh' => $n->ma_nganh,
                    'tenNganh' => $n->ten_nganh,
                    'khoaId' => $n->khoa_id,
                    'tenKhoa' => $n->khoa?->ten_khoa
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => "Lấy thành công {$data->count()} ngành"
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
     * GET /api/hoc-ky/dates
     * 
     * Get dates info for the current semester
     * Requires authentication
     */
    public function getHocKyDates()
    {
        try {
            $hocKy = HocKy::with('nienKhoa')
                ->where('trang_thai_hien_tai', true)
                ->first();

            if (!$hocKy) {
                return response()->json([
                    'isSuccess' => true,
                    'data' => null,
                    'message' => 'Không có học kỳ hiện hành'
                ], 404);
            }

            $data = [
                'id' => $hocKy->id,
                'tenHocKy' => $hocKy->ten_hoc_ky,
                'maHocKy' => $hocKy->ma_hoc_ky,
                'ngayBatDau' => $hocKy->ngay_bat_dau?->toDateString(),
                'ngayKetThuc' => $hocKy->ngay_ket_thuc?->toDateString(),
                'nienKhoa' => $hocKy->nienKhoa?->ten_nien_khoa
            ];

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => 'Lấy thông tin ngày học kỳ thành công'
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
     * GET /api/dm/nganh/chua-co-chinh-sach?hoc_ky_id={}&khoa_id={}
     * 
     * Get list of specializations that don't have credit policy
     * for the given semester. Used in forms to add new credit policies.
     * Requires authentication
     */
    public function getNganhChuaCoChinhSach(Request $request)
    {
        try {
            $hocKyId = $request->query('hoc_ky_id');
            $khoaId = $request->query('khoa_id');

            if (!$hocKyId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thiếu học kỳ ID'
                ], 400);
            }

            if (!$khoaId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thiếu khoa ID'
                ], 400);
            }

            // Get nganh_ids that already have policy in this hoc_ky
            $existingPolicyNganhIds = \DB::table('chinh_sach_tin_chi')
                ->where('hoc_ky_id', $hocKyId)
                ->pluck('nganh_id')
                ->toArray();

            // Get ngành in khoa that DON'T have policy in this semester
            $nganhs = NganhHoc::where('khoa_id', $khoaId)
                ->whereNotIn('id', $existingPolicyNganhIds)
                ->orderBy('ten_nganh')
                ->get();

            $data = $nganhs->map(function ($n) {
                return [
                    'id' => $n->id,
                    'ma_nganh' => $n->ma_nganh,
                    'ten_nganh' => $n->ten_nganh,
                    'khoa_id' => $n->khoa_id
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => "Lấy thành công {$data->count()} ngành chưa có chính sách"
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
     * GET /api/config/tiet-hoc
     * 
     * Get configuration for class periods (lesson times)
     * No authentication required (public config)
     */
    public function getConfigTietHoc()
    {
        // Hardcoded configuration based on typical university schedule
        $tietHocConfig = [
            ['tiet' => 1, 'start' => '07:00', 'end' => '07:50'],
            ['tiet' => 2, 'start' => '07:50', 'end' => '08:40'],
            ['tiet' => 3, 'start' => '09:00', 'end' => '09:50'],
            ['tiet' => 4, 'start' => '09:50', 'end' => '10:40'],
            ['tiet' => 5, 'start' => '10:40', 'end' => '11:30'],
            ['tiet' => 6, 'start' => '13:00', 'end' => '13:50'],
            ['tiet' => 7, 'start' => '13:50', 'end' => '14:40'],
            ['tiet' => 8, 'start' => '15:00', 'end' => '15:50'],
            ['tiet' => 9, 'start' => '15:50', 'end' => '16:40'],
            ['tiet' => 10, 'start' => '16:40', 'end' => '17:30'],
            ['tiet' => 11, 'start' => '17:40', 'end' => '18:30'],
            ['tiet' => 12, 'start' => '18:30', 'end' => '19:20'],
            ['tiet' => 13, 'start' => '19:20', 'end' => '20:10'],
            ['tiet' => 14, 'start' => '20:10', 'end' => '21:00'],
            ['tiet' => 15, 'start' => '21:00', 'end' => '21:50'],
        ];

        return response()->json([
            'success' => true,
            'data' => $tietHocConfig
        ]);
    }
}
