<?php

namespace App\Http\Controllers\Api\SinhVien;

use App\Http\Controllers\Controller;
use App\Infrastructure\SinhVien\Persistence\Models\SinhVien;
use App\Infrastructure\SinhVien\Persistence\Models\KyPhase;
use App\Infrastructure\SinhVien\Persistence\Models\DotDangKy;
use App\Infrastructure\SinhVien\Persistence\Models\HocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\GhiDanhHocPhan;
use App\Infrastructure\Common\Persistence\Models\HocKy;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use App\Infrastructure\TLK\Persistence\Models\DeXuatHocPhan;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;

class GhiDanhController extends Controller
{
    /**
     * GET /api/sv/check-ghi-danh
     * 
     * Check if student can enroll in subjects
     * Checks: student info, current semester, current phase, registration period
     */
    public function checkGhiDanh()
    {
        try {
            $taiKhoan = JWTAuth::parseToken()->authenticate();
            $userProfile = UserProfile::where('tai_khoan_id', $taiKhoan->id)->first();

            if (!$userProfile) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin người dùng'
                ], 404);
            }

            // 1. Check student info
            $sinhVien = SinhVien::find($userProfile->id);
            if (!$sinhVien || !$sinhVien->khoa_id) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Sinh viên không tồn tại hoặc chưa có khoa'
                ], 400);
            }

            // 2. Check current semester
            $currentHocKy = HocKy::where('trang_thai_hien_tai', true)->first();
            if (!$currentHocKy) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Chưa có học kỳ hiện hành'
                ], 400);
            }

            // 3. Check current phase (ghi_danh)
            $now = now();
            $currentPhase = KyPhase::where('hoc_ky_id', $currentHocKy->id)
                ->where('is_enabled', true)
                ->where('start_at', '<=', $now)
                ->where('end_at', '>=', $now)
                ->first();

            if (!$currentPhase) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Chưa có giai đoạn hiện hành'
                ], 400);
            }

            if ($currentPhase->phase !== 'ghi_danh') {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Chưa đến giai đoạn ghi danh'
                ], 400);
            }

            // 4. Check registration period (Toan truong)
            $dotToanTruong = DotDangKy::where('hoc_ky_id', $currentHocKy->id)
                ->where('loai_dot', 'ghi_danh')
                ->where('is_check_toan_truong', true)
                ->where('thoi_gian_bat_dau', '<=', $now)
                ->where('thoi_gian_ket_thuc', '>=', $now)
                ->first();

            if ($dotToanTruong) {
                return response()->json([
                    'isSuccess' => true,
                    'data' => [
                        'hocKyId' => $currentHocKy->id,
                        'tenHocKy' => $currentHocKy->ten_hoc_ky,
                        'phase' => $currentPhase->phase,
                    ],
                    'message' => 'Đợt ghi danh toàn trường đang mở, sinh viên có thể ghi danh'
                ]);
            }

            // 5. Check registration period (Theo khoa)
            $dotTheoKhoa = DotDangKy::where('hoc_ky_id', $currentHocKy->id)
                ->where('loai_dot', 'ghi_danh')
                ->where('khoa_id', $sinhVien->khoa_id)
                ->where('thoi_gian_bat_dau', '<=', $now)
                ->where('thoi_gian_ket_thuc', '>=', $now)
                ->first();

            if ($dotTheoKhoa) {
                return response()->json([
                    'isSuccess' => true,
                    'data' => [
                        'hocKyId' => $currentHocKy->id,
                        'tenHocKy' => $currentHocKy->ten_hoc_ky,
                        'phase' => $currentPhase->phase,
                    ],
                    'message' => 'Đợt ghi danh theo khoa đang mở, sinh viên có thể ghi danh'
                ]);
            }

            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => 'Không có đợt ghi danh nào đang mở'
            ], 400);

        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/sv/mon-hoc-ghi-danh?hocKyId={id}
     * 
     * Get list of subjects available for enrollment
     */
    public function getMonHocGhiDanh(Request $request)
    {
        try {
            $hocKyId = $request->query('hocKyId');

            // If no hocKyId provided, use current semester
            if (!$hocKyId) {
                $currentHocKy = HocKy::where('trang_thai_hien_tai', true)->first();
                if (!$currentHocKy) {
                    return response()->json([
                        'isSuccess' => false,
                        'data' => null,
                        'message' => 'Không có học kỳ hiện hành'
                    ], 400);
                }
                $hocKyId = $currentHocKy->id;
            }

            // Get open subjects for the semester
            $hocPhans = HocPhan::with(['monHoc', 'monHoc.khoa'])
                ->where('id_hoc_ky', $hocKyId)
                ->where('trang_thai_mo', true)
                ->get();

            $data = $hocPhans->map(function ($hp) use ($hocKyId) {
                $monHoc = $hp->monHoc;

                // Lookup giang vien from DeXuatHocPhan
                $tenGiangVien = 'Chưa có giảng viên';
                if ($monHoc) {
                    $deXuat = DeXuatHocPhan::with('giangVienDeXuat.user')
                        ->where('mon_hoc_id', $monHoc->id)
                        ->where('hoc_ky_id', $hocKyId)
                        ->whereIn('trang_thai', ['cho_duyet', 'da_duyet_tk', 'da_duyet_pdt'])
                        ->first();

                    if ($deXuat && $deXuat->giangVienDeXuat && $deXuat->giangVienDeXuat->user) {
                        $tenGiangVien = $deXuat->giangVienDeXuat->user->ho_ten;
                    }
                }

                return [
                    'id' => $hp->id,
                    'maMonHoc' => $monHoc?->ma_mon ?? '',
                    'tenMonHoc' => $monHoc?->ten_mon ?? '',
                    'soTinChi' => $monHoc?->so_tin_chi ?? 0,
                    'tenKhoa' => $monHoc?->khoa?->ten_khoa ?? '',
                    'tenGiangVien' => $tenGiangVien,
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => "Lấy danh sách môn học ghi danh thành công"
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
     * POST /api/sv/ghi-danh
     * 
     * Enroll in a subject
     * Body: { "monHocId": "uuid" }
     */
    public function ghiDanh(Request $request)
    {
        try {
            $taiKhoan = JWTAuth::parseToken()->authenticate();
            $userProfile = UserProfile::where('tai_khoan_id', $taiKhoan->id)->first();

            if (!$userProfile) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin người dùng'
                ], 404);
            }

            $sinhVien = SinhVien::find($userProfile->id);
            if (!$sinhVien) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin sinh viên'
                ], 404);
            }

            $monHocId = $request->input('monHocId');
            if (!$monHocId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Mã học phần không hợp lệ'
                ], 400);
            }

            // 1. Check subject exists and is open
            $hocPhan = HocPhan::find($monHocId);
            if (!$hocPhan) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy học phần'
                ], 404);
            }

            if (!$hocPhan->trang_thai_mo) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Học phần đã đóng, không thể ghi danh'
                ], 400);
            }

            // 2. Check if already registered
            $isRegistered = GhiDanhHocPhan::where('sinh_vien_id', $sinhVien->id)
                ->where('hoc_phan_id', $monHocId)
                ->exists();

            if ($isRegistered) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Bạn đã ghi danh học phần này rồi'
                ], 400);
            }

            // 3. Create registration
            GhiDanhHocPhan::create([
                'id' => Str::uuid()->toString(),
                'sinh_vien_id' => $sinhVien->id,
                'hoc_phan_id' => $monHocId,
                'ngay_ghi_danh' => now(),
                'trang_thai' => 'da_ghi_danh',
            ]);

            return response()->json([
                'isSuccess' => true,
                'data' => null,
                'message' => 'Ghi danh môn học thành công'
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
     * GET /api/sv/ghi-danh/my
     * 
     * Get list of enrolled subjects for current student
     */
    public function getDanhSachDaGhiDanh()
    {
        try {
            $taiKhoan = JWTAuth::parseToken()->authenticate();
            $userProfile = UserProfile::where('tai_khoan_id', $taiKhoan->id)->first();

            if (!$userProfile) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin người dùng'
                ], 404);
            }

            $sinhVien = SinhVien::find($userProfile->id);
            if (!$sinhVien) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin sinh viên'
                ], 404);
            }

            // Get enrolled subjects
            $ghiDanhList = GhiDanhHocPhan::with(['hocPhan', 'hocPhan.monHoc', 'hocPhan.monHoc.khoa'])
                ->where('sinh_vien_id', $sinhVien->id)
                ->get();

            $data = $ghiDanhList->map(function ($gd) {
                $hocPhan = $gd->hocPhan;
                $monHoc = $hocPhan?->monHoc;

                // Lookup giang vien from DeXuatHocPhan
                $tenGiangVien = 'Chưa có giảng viên';
                if ($monHoc && $hocPhan) {
                    $deXuat = DeXuatHocPhan::with('giangVienDeXuat.user')
                        ->where('mon_hoc_id', $monHoc->id)
                        ->where('hoc_ky_id', $hocPhan->id_hoc_ky)
                        ->whereIn('trang_thai', ['cho_duyet', 'da_duyet_tk', 'da_duyet_pdt'])
                        ->first();

                    if ($deXuat && $deXuat->giangVienDeXuat && $deXuat->giangVienDeXuat->user) {
                        $tenGiangVien = $deXuat->giangVienDeXuat->user->ho_ten;
                    }
                }

                return [
                    'ghiDanhId' => $gd->id,
                    'monHocId' => $hocPhan?->id ?? '',
                    'maMonHoc' => $monHoc?->ma_mon ?? '',
                    'tenMonHoc' => $monHoc?->ten_mon ?? '',
                    'soTinChi' => $monHoc?->so_tin_chi ?? 0,
                    'tenKhoa' => $monHoc?->khoa?->ten_khoa ?? '',
                    'tenGiangVien' => $tenGiangVien,
                    'ngayGhiDanh' => $gd->ngay_ghi_danh?->toISOString(),
                    'trangThai' => $gd->trang_thai,
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => "Lấy thành công {$data->count()} môn học đã ghi danh"
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
     * DELETE /api/sv/ghi-danh/{id}
     * 
     * Cancel enrollment for a subject
     */
    public function huyGhiDanh(string $id)
    {
        try {
            $taiKhoan = JWTAuth::parseToken()->authenticate();
            $userProfile = UserProfile::where('tai_khoan_id', $taiKhoan->id)->first();

            if (!$userProfile) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin người dùng'
                ], 404);
            }

            $sinhVien = SinhVien::find($userProfile->id);
            if (!$sinhVien) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin sinh viên'
                ], 404);
            }

            // Find the enrollment record
            $ghiDanh = GhiDanhHocPhan::where('id', $id)
                ->where('sinh_vien_id', $sinhVien->id)
                ->first();

            if (!$ghiDanh) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy bản ghi ghi danh hoặc bạn không có quyền hủy'
                ], 404);
            }

            // Delete the enrollment
            $ghiDanh->delete();

            return response()->json([
                'isSuccess' => true,
                'data' => null,
                'message' => 'Hủy ghi danh thành công'
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
     * POST /api/sv/huy-ghi-danh
     * 
     * Cancel multiple enrollments
     * Body: { "ghiDanhIds": ["id1", "id2", ...] }
     */
    public function huyGhiDanhBatch(Request $request)
    {
        try {
            $taiKhoan = JWTAuth::parseToken()->authenticate();
            $userProfile = UserProfile::where('tai_khoan_id', $taiKhoan->id)->first();

            if (!$userProfile) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin người dùng'
                ], 404);
            }

            $sinhVien = SinhVien::find($userProfile->id);
            if (!$sinhVien) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin sinh viên'
                ], 404);
            }

            $ghiDanhIds = $request->input('ghiDanhIds', []);

            if (empty($ghiDanhIds)) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Danh sách ghi danh cần hủy không được trống'
                ], 400);
            }

            // Delete all enrollment records matching the IDs and belong to this student
            $deletedCount = GhiDanhHocPhan::whereIn('id', $ghiDanhIds)
                ->where('sinh_vien_id', $sinhVien->id)
                ->delete();

            if ($deletedCount === 0) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy bản ghi ghi danh nào để hủy'
                ], 404);
            }

            return response()->json([
                'isSuccess' => true,
                'data' => ['deletedCount' => $deletedCount],
                'message' => "Đã hủy thành công {$deletedCount} ghi danh"
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
