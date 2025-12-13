<?php

namespace App\Http\Controllers\Api\SinhVien;

use App\Http\Controllers\Controller;
use App\Infrastructure\SinhVien\Persistence\Models\SinhVien;
use App\Infrastructure\SinhVien\Persistence\Models\TaiLieu;
use App\Infrastructure\SinhVien\Persistence\Models\DangKyHocPhan;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

class SinhVienController extends Controller
{
    /**
     * GET /api/sv/profile
     * 
     * Get current student's profile
     * Requires authentication and sinh_vien role
     */
    public function getProfile()
    {
        try {
            $taiKhoan = JWTAuth::parseToken()->authenticate();
            $taiKhoanId = $taiKhoan->id;

            // Get UserProfile by tai_khoan_id
            $userProfile = UserProfile::where('tai_khoan_id', $taiKhoanId)->first();

            if (!$userProfile) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin người dùng'
                ], 404);
            }

            // SinhVien.id = Users.id (OneToOne relationship)
            $sinhVien = SinhVien::with(['user', 'khoa', 'nganh'])
                ->find($userProfile->id);

            if (!$sinhVien) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin sinh viên'
                ], 404);
            }

            // Map to DTO format matching FE expectations (camelCase)
            $data = [
                'id' => $sinhVien->id,
                'maSoSinhVien' => $sinhVien->ma_so_sinh_vien,
                'hoTen' => $sinhVien->user?->ho_ten,
                'email' => $sinhVien->user?->email,
                'khoaId' => $sinhVien->khoa_id,
                'tenKhoa' => $sinhVien->khoa?->ten_khoa,
                'nganhId' => $sinhVien->nganh_id,
                'tenNganh' => $sinhVien->nganh?->ten_nganh,
                'lop' => $sinhVien->lop,
                'khoaHoc' => $sinhVien->khoa_hoc,
                'ngayNhapHoc' => $sinhVien->ngay_nhap_hoc?->toDateString(),
            ];

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => 'Lấy thông tin sinh viên thành công'
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
     * GET /api/sv/lop-hoc-phan/{id}/tai-lieu
     * 
     * Get list of documents for a class section
     * Only enrolled students can access
     */
    public function getTaiLieu($lopHocPhanId)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $userId = $user->id;

            // Check if student is enrolled in this class
            $isEnrolled = $this->isStudentEnrolled($lopHocPhanId, $userId);

            if (!$isEnrolled) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không có quyền truy cập lớp học phần này'
                ], 403);
            }

            // Get documents
            $documents = TaiLieu::with('uploadedBy')
                ->where('lop_hoc_phan_id', $lopHocPhanId)
                ->orderBy('created_at', 'desc')
                ->get();

            $data = $documents->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'tenTaiLieu' => $doc->ten_tai_lieu,
                    'fileType' => $doc->file_type,
                    'fileUrl' => '', // Will be generated on download
                    'uploadedAt' => $doc->created_at?->toISOString(),
                    'uploadedBy' => $doc->uploadedBy?->ho_ten ?? '',
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
     * GET /api/sv/lop-hoc-phan/{id}/tai-lieu/{doc_id}/download
     * 
     * Download a document from S3
     * Only enrolled students can download
     */
    public function downloadTaiLieu($lopHocPhanId, $docId)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $userId = $user->id;

            // Check if student is enrolled
            $isEnrolled = $this->isStudentEnrolled($lopHocPhanId, $userId);

            if (!$isEnrolled) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không có quyền truy cập lớp học phần này'
                ], 403);
            }

            // Get document
            $document = TaiLieu::find($docId);

            if (!$document) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Tài liệu không tồn tại'
                ], 404);
            }

            // Verify document belongs to the class
            if ($document->lop_hoc_phan_id !== $lopHocPhanId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Tài liệu không thuộc lớp học phần này'
                ], 400);
            }

            // For now, return error since S3 is not configured
            // In production, this would use AWS S3 SDK or Laravel's Storage facade
            if (!config('filesystems.disks.s3.key')) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Dịch vụ lưu trữ file chưa được cấu hình'
                ], 503);
            }

            // Extract filename from file_path
            $filePath = $document->file_path;
            $filename = basename($filePath);

            // Download from S3
            try {
                $fileContent = Storage::disk('s3')->get($filePath);

                return response($fileContent)
                    ->header('Content-Type', $document->file_type ?? 'application/octet-stream')
                    ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");

            } catch (\Throwable $e) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không thể tải file: ' . $e->getMessage()
                ], 500);
            }

        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if a student is enrolled in a class
     * 
     * @param string $lopHocPhanId UUID of LopHocPhan
     * @param string $taiKhoanId UUID of TaiKhoan (from JWT)
     */
    private function isStudentEnrolled(string $lopHocPhanId, string $taiKhoanId): bool
    {
        // Get UserProfile by tai_khoan_id
        $userProfile = UserProfile::where('tai_khoan_id', $taiKhoanId)->first();

        if (!$userProfile) {
            return false;
        }

        // SinhVien.id = Users.id
        $sinhVien = SinhVien::find($userProfile->id);

        if (!$sinhVien) {
            return false;
        }

        // Check enrollment with active status
        return DangKyHocPhan::where('sinh_vien_id', $sinhVien->id)
            ->where('lop_hoc_phan_id', $lopHocPhanId)
            ->whereIn('trang_thai', [
                'da_dang_ky',
                'da_duyet',
                'cho_thanh_toan',
                'da_thanh_toan',
                'completed'
            ])
            ->exists();
    }
}
