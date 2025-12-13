<?php

namespace App\Http\Controllers\Api\GiangVien;

use App\Http\Controllers\Controller;
use App\Infrastructure\GiangVien\Persistence\Models\GiangVien;
use App\Infrastructure\GiangVien\Persistence\Models\DiemSinhVien;
use App\Infrastructure\SinhVien\Persistence\Models\LopHocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\DangKyHocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\TaiLieu;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use App\Infrastructure\Common\Persistence\Models\HocKy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;

class GVController extends Controller
{
    /**
     * Get GiangVien from JWT token
     */
    private function getGiangVienFromToken()
    {
        $taiKhoan = JWTAuth::parseToken()->authenticate();
        $userProfile = UserProfile::where('tai_khoan_id', $taiKhoan->id)->first();

        if (!$userProfile) {
            return null;
        }

        return GiangVien::find($userProfile->id);
    }

    /**
     * GET /api/gv/lop-hoc-phan?hocKyId={id}
     * Get list of LopHocPhan assigned to instructor
     */
    public function getLopHocPhanList(Request $request)
    {
        try {
            $hocKyId = $request->query('hocKyId') ?? $request->query('hoc_ky_id');

            $taiKhoan = JWTAuth::parseToken()->authenticate();
            $userProfile = UserProfile::where('tai_khoan_id', $taiKhoan->id)->first();

            if (!$userProfile) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin giảng viên'
                ], 404);
            }

            // Get LopHocPhan where giang_vien_id = user profile id
            $query = LopHocPhan::with(['hocPhan.monHoc'])
                ->where('giang_vien_id', $userProfile->id);

            if ($hocKyId) {
                $query->whereHas('hocPhan', function ($q) use ($hocKyId) {
                    $q->where('id_hoc_ky', $hocKyId);
                });
            }

            $lopHocPhans = $query->get();

            $data = $lopHocPhans->map(function ($lhp) {
                $hocPhan = $lhp->hocPhan;
                $monHoc = $hocPhan?->monHoc;

                return [
                    'id' => $lhp->id,
                    'ma_lop' => $lhp->ma_lop,
                    'so_luong_hien_tai' => $lhp->so_luong_hien_tai ?? 0,
                    'so_luong_toi_da' => $lhp->so_luong_toi_da ?? 50,
                    'hoc_phan' => [
                        'ten_hoc_phan' => $hocPhan?->ten_hoc_phan ?? '',
                        'mon_hoc' => [
                            'ma_mon' => $monHoc?->ma_mon ?? '',
                            'ten_mon' => $monHoc?->ten_mon ?? '',
                            'so_tin_chi' => $monHoc?->so_tin_chi ?? 0,
                        ],
                    ],
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => "Lấy thành công {$data->count()} lớp học phần"
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
     * GET /api/gv/lop-hoc-phan/{id}
     * Get LopHocPhan detail
     */
    public function getLopHocPhanDetail(Request $request, $id)
    {
        try {
            $taiKhoan = JWTAuth::parseToken()->authenticate();
            $userProfile = UserProfile::where('tai_khoan_id', $taiKhoan->id)->first();

            if (!$userProfile) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin giảng viên'
                ], 404);
            }

            $lhp = LopHocPhan::with(['hocPhan.monHoc.khoa', 'lichHocDinhKys.phong'])
                ->where('giang_vien_id', $userProfile->id)
                ->find($id);

            if (!$lhp) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy lớp học phần hoặc không có quyền truy cập'
                ], 404);
            }

            $hocPhan = $lhp->hocPhan;
            $monHoc = $hocPhan?->monHoc;

            $data = [
                'id' => $lhp->id,
                'maLop' => $lhp->ma_lop,
                'soLuongHienTai' => $lhp->so_luong_hien_tai ?? 0,
                'soLuongToiDa' => $lhp->so_luong_toi_da ?? 50,
                'ngayBatDau' => $lhp->ngay_bat_dau?->format('Y-m-d'),
                'ngayKetThuc' => $lhp->ngay_ket_thuc?->format('Y-m-d'),
                'hocPhan' => [
                    'tenHocPhan' => $hocPhan?->ten_hoc_phan ?? '',
                    'monHoc' => [
                        'maMon' => $monHoc?->ma_mon ?? '',
                        'tenMon' => $monHoc?->ten_mon ?? '',
                        'soTinChi' => $monHoc?->so_tin_chi ?? 0,
                        'tenKhoa' => $monHoc?->khoa?->ten_khoa ?? '',
                    ],
                ],
                'tkb' => $lhp->lichHocDinhKys->map(function ($lich) {
                    return [
                        'thu' => $lich->thu,
                        'tietBatDau' => $lich->tiet_bat_dau,
                        'tietKetThuc' => $lich->tiet_ket_thuc,
                        'phong' => $lich->phong?->ma_phong ?? 'TBA',
                    ];
                }),
            ];

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => 'Lấy chi tiết lớp học phần thành công'
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
     * GET /api/gv/lop-hoc-phan/{id}/sinh-vien
     * Get students of a LopHocPhan
     */
    public function getLopHocPhanStudents(Request $request, $id)
    {
        try {
            $taiKhoan = JWTAuth::parseToken()->authenticate();
            $userProfile = UserProfile::where('tai_khoan_id', $taiKhoan->id)->first();

            if (!$userProfile) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin giảng viên'
                ], 404);
            }

            // Check if this LHP belongs to the instructor
            $lhp = LopHocPhan::where('giang_vien_id', $userProfile->id)->find($id);

            if (!$lhp) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy lớp học phần hoặc không có quyền truy cập'
                ], 404);
            }

            // Get students registered in this class
            $dangKys = DangKyHocPhan::with(['sinhVien.user'])
                ->where('lop_hoc_phan_id', $id)
                ->where('trang_thai', 'da_dang_ky')
                ->get();

            $data = $dangKys->map(function ($dk) {
                $sv = $dk->sinhVien;
                $user = $sv?->user;

                return [
                    'id' => $sv?->id ?? '',
                    'maSoSinhVien' => $sv?->ma_so_sinh_vien ?? '',
                    'hoTen' => $user?->ho_ten ?? '',
                    'email' => $user?->email ?? '',
                    'lop' => $sv?->lop ?? '',
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => "Lấy thành công {$data->count()} sinh viên"
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
     * GET /api/gv/lop-hoc-phan/{id}/diem
     * Get grades for a LopHocPhan
     */
    public function getGrades(Request $request, $id)
    {
        try {
            $taiKhoan = JWTAuth::parseToken()->authenticate();
            $userProfile = UserProfile::where('tai_khoan_id', $taiKhoan->id)->first();

            if (!$userProfile) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin giảng viên'
                ], 404);
            }

            $lhp = LopHocPhan::where('giang_vien_id', $userProfile->id)->find($id);

            if (!$lhp) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy lớp học phần hoặc không có quyền truy cập'
                ], 404);
            }

            // Get students and their grades
            $dangKys = DangKyHocPhan::with(['sinhVien.user'])
                ->where('lop_hoc_phan_id', $id)
                ->where('trang_thai', 'da_dang_ky')
                ->get();

            $data = $dangKys->map(function ($dk) use ($id) {
                $sv = $dk->sinhVien;
                $user = $sv?->user;

                // Get grade if exists
                $diem = DiemSinhVien::where('sinh_vien_id', $sv?->id)
                    ->where('lop_hoc_phan_id', $id)
                    ->first();

                return [
                    'sinhVienId' => $sv?->id ?? '',
                    'maSoSinhVien' => $sv?->ma_so_sinh_vien ?? '',
                    'hoTen' => $user?->ho_ten ?? '',
                    'diemChuyenCan' => $diem?->diem_chuyen_can ?? null,
                    'diemGiuaKy' => $diem?->diem_giua_ky ?? null,
                    'diemCuoiKy' => $diem?->diem_cuoi_ky ?? null,
                    'diemTongKet' => $diem?->diem_tong_ket ?? null,
                    'ghiChu' => $diem?->ghi_chu ?? '',
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => "Lấy điểm thành công"
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
     * PUT /api/gv/lop-hoc-phan/{id}/diem
     * Update grades for a LopHocPhan
     * Body: { "items": [{ "sinhVienId": "...", "diemChuyenCan": 8.0, ... }] }
     */
    public function updateGrades(Request $request, $id)
    {
        try {
            $taiKhoan = JWTAuth::parseToken()->authenticate();
            $userProfile = UserProfile::where('tai_khoan_id', $taiKhoan->id)->first();

            if (!$userProfile) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin giảng viên'
                ], 404);
            }

            $lhp = LopHocPhan::where('giang_vien_id', $userProfile->id)->find($id);

            if (!$lhp) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy lớp học phần hoặc không có quyền truy cập'
                ], 404);
            }

            $items = $request->input('items') ?? $request->input('diem', []);

            if (empty($items)) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không có dữ liệu điểm để cập nhật'
                ], 400);
            }

            $updatedCount = 0;

            foreach ($items as $item) {
                $sinhVienId = $item['sinhVienId'] ?? $item['sinh_vien_id'] ?? null;

                if (!$sinhVienId)
                    continue;

                // Upsert grade record
                DiemSinhVien::updateOrCreate(
                    [
                        'sinh_vien_id' => $sinhVienId,
                        'lop_hoc_phan_id' => $id,
                    ],
                    [
                        'diem_chuyen_can' => $item['diemChuyenCan'] ?? $item['diem_chuyen_can'] ?? null,
                        'diem_giua_ky' => $item['diemGiuaKy'] ?? $item['diem_giua_ky'] ?? null,
                        'diem_cuoi_ky' => $item['diemCuoiKy'] ?? $item['diem_cuoi_ky'] ?? null,
                        'diem_tong_ket' => $item['diemTongKet'] ?? $item['diem_tong_ket'] ?? null,
                        'ghi_chu' => $item['ghiChu'] ?? $item['ghi_chu'] ?? null,
                    ]
                );

                $updatedCount++;
            }

            return response()->json([
                'isSuccess' => true,
                'data' => ['updatedCount' => $updatedCount],
                'message' => "Cập nhật điểm thành công ({$updatedCount} sinh viên)"
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
     * GET /api/gv/lop-hoc-phan/{id}/tai-lieu
     * Get documents for a LopHocPhan
     */
    public function getTaiLieuList(Request $request, $id)
    {
        try {
            $taiKhoan = JWTAuth::parseToken()->authenticate();
            $userProfile = UserProfile::where('tai_khoan_id', $taiKhoan->id)->first();

            if (!$userProfile) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin giảng viên'
                ], 404);
            }

            $lhp = LopHocPhan::where('giang_vien_id', $userProfile->id)->find($id);

            if (!$lhp) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy lớp học phần hoặc không có quyền truy cập'
                ], 404);
            }

            $taiLieus = TaiLieu::where('lop_hoc_phan_id', $id)->get();

            $data = $taiLieus->map(function ($tl) {
                return [
                    'id' => $tl->id,
                    'tenTaiLieu' => $tl->ten_tai_lieu,
                    'fileType' => $tl->file_type,
                    'fileSize' => $tl->file_size,
                    'uploadedAt' => $tl->created_at?->toISOString(),
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => "Lấy thành công {$data->count()} tài liệu"
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
     * POST /api/gv/lop-hoc-phan/{id}/tai-lieu/upload
     * Upload document for a LopHocPhan
     */
    public function uploadTaiLieu(Request $request, $id)
    {
        try {
            $taiKhoan = JWTAuth::parseToken()->authenticate();
            $userProfile = UserProfile::where('tai_khoan_id', $taiKhoan->id)->first();

            if (!$userProfile) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin giảng viên'
                ], 404);
            }

            $lhp = LopHocPhan::where('giang_vien_id', $userProfile->id)->find($id);

            if (!$lhp) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy lớp học phần hoặc không có quyền truy cập'
                ], 404);
            }

            // Check for file
            if (!$request->hasFile('file')) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không có file được upload'
                ], 400);
            }

            $file = $request->file('file');
            $tenTaiLieu = $request->input('ten_tai_lieu', $file->getClientOriginalName());

            // Generate S3 key
            $s3Key = "tai-lieu/{$id}/" . Str::uuid() . '_' . $file->getClientOriginalName();

            // Check if S3 is configured
            $s3Configured = config('filesystems.disks.s3.key') ? true : false;

            if ($s3Configured) {
                // Upload to S3
                Storage::disk('s3')->put($s3Key, file_get_contents($file), 'private');
            }

            // Save to database
            $taiLieu = TaiLieu::create([
                'id' => Str::uuid()->toString(),
                'lop_hoc_phan_id' => $id,
                'uploaded_by' => $userProfile->id,
                'ten_tai_lieu' => $tenTaiLieu,
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                's3_key' => $s3Key,
                'created_at' => now(),
            ]);

            return response()->json([
                'isSuccess' => true,
                'data' => [
                    'id' => $taiLieu->id,
                    'tenTaiLieu' => $taiLieu->ten_tai_lieu,
                ],
                'message' => 'Upload tài liệu thành công'
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
     * GET /api/gv/lop-hoc-phan/{id}/tai-lieu/{doc_id}
     * Get document details
     */
    public function getTaiLieuDetail(Request $request, $id, $doc_id)
    {
        try {
            $taiKhoan = JWTAuth::parseToken()->authenticate();
            $userProfile = UserProfile::where('tai_khoan_id', $taiKhoan->id)->first();

            if (!$userProfile) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin giảng viên'
                ], 404);
            }

            $lhp = LopHocPhan::where('giang_vien_id', $userProfile->id)->find($id);

            if (!$lhp) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy lớp học phần hoặc không có quyền truy cập'
                ], 404);
            }

            $taiLieu = TaiLieu::where('lop_hoc_phan_id', $id)->find($doc_id);

            if (!$taiLieu) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy tài liệu'
                ], 404);
            }

            return response()->json([
                'isSuccess' => true,
                'data' => [
                    'id' => $taiLieu->id,
                    'tenTaiLieu' => $taiLieu->ten_tai_lieu,
                    'fileType' => $taiLieu->file_type,
                    'fileSize' => $taiLieu->file_size,
                    'uploadedAt' => $taiLieu->created_at?->toISOString(),
                ],
                'message' => 'Lấy chi tiết tài liệu thành công'
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
     * GET /api/gv/lop-hoc-phan/{id}/tai-lieu/{doc_id}/download
     * Download document
     */
    public function downloadTaiLieu(Request $request, $id, $doc_id)
    {
        try {
            $taiKhoan = JWTAuth::parseToken()->authenticate();
            $userProfile = UserProfile::where('tai_khoan_id', $taiKhoan->id)->first();

            if (!$userProfile) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin giảng viên'
                ], 404);
            }

            $lhp = LopHocPhan::where('giang_vien_id', $userProfile->id)->find($id);

            if (!$lhp) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy lớp học phần hoặc không có quyền truy cập'
                ], 404);
            }

            $taiLieu = TaiLieu::where('lop_hoc_phan_id', $id)->find($doc_id);

            if (!$taiLieu) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy tài liệu'
                ], 404);
            }

            // Check S3 configuration
            if (!config('filesystems.disks.s3.key')) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'S3 chưa được cấu hình'
                ], 503);
            }

            // Get from S3
            $s3Key = $taiLieu->s3_key;

            if (!Storage::disk('s3')->exists($s3Key)) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'File không tồn tại trên S3'
                ], 404);
            }

            $content = Storage::disk('s3')->get($s3Key);

            return response($content)
                ->header('Content-Type', $taiLieu->file_type ?? 'application/octet-stream')
                ->header('Content-Disposition', 'attachment; filename="' . $taiLieu->ten_tai_lieu . '"');

        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/gv/tkb-weekly?hocKyId={id}&dateStart={date}&dateEnd={date}
     * Get instructor's weekly schedule
     */
    public function getTKBWeekly(Request $request)
    {
        try {
            $hocKyId = $request->query('hocKyId') ?? $request->query('hoc_ky_id');
            $dateStart = $request->query('dateStart') ?? $request->query('date_start');
            $dateEnd = $request->query('dateEnd') ?? $request->query('date_end');

            if (!$hocKyId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'hoc_ky_id is required'
                ], 400);
            }

            if (!$dateStart || !$dateEnd) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'date_start and date_end are required'
                ], 400);
            }

            $taiKhoan = JWTAuth::parseToken()->authenticate();
            $userProfile = UserProfile::where('tai_khoan_id', $taiKhoan->id)->first();

            if (!$userProfile) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => [],
                    'message' => 'Không tìm thấy thông tin giảng viên'
                ]);
            }

            // Get all LopHocPhan for this instructor in this semester
            $lopHocPhans = LopHocPhan::with(['lichHocDinhKys.phong', 'hocPhan.monHoc'])
                ->where('giang_vien_id', $userProfile->id)
                ->whereHas('hocPhan', function ($q) use ($hocKyId) {
                    $q->where('id_hoc_ky', $hocKyId);
                })
                ->get();

            $data = [];

            foreach ($lopHocPhans as $lhp) {
                $monHoc = $lhp->hocPhan?->monHoc;

                foreach ($lhp->lichHocDinhKys as $lich) {
                    $data[] = [
                        'thu' => $lich->thu,
                        'tietBatDau' => $lich->tiet_bat_dau,
                        'tietKetThuc' => $lich->tiet_ket_thuc,
                        'phong' => $lich->phong?->ma_phong ?? 'TBA',
                        'maLop' => $lhp->ma_lop,
                        'tenMon' => $monHoc?->ten_mon ?? '',
                        'maMon' => $monHoc?->ma_mon ?? '',
                    ];
                }
            }

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => 'Lấy thời khóa biểu thành công'
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
