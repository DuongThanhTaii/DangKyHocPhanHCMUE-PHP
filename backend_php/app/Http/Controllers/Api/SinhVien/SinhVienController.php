<?php

namespace App\Http\Controllers\Api\SinhVien;

use App\Http\Controllers\Controller;
use App\Application\SinhVien\UseCases\GetStudentProfileUseCase;
use App\Application\SinhVien\UseCases\GetClassDocumentsUseCase;
use App\Domain\SinhVien\Repositories\SinhVienPortalRepositoryInterface;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use App\Infrastructure\SinhVien\Persistence\Models\SinhVien;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * SinhVienController - Student Profile & Documents (Refactored - Clean Architecture)
 * 
 * Thin controller - delegates to UseCases
 */
class SinhVienController extends Controller
{
    public function __construct(
        private GetStudentProfileUseCase $getProfileUseCase,
        private GetClassDocumentsUseCase $getDocumentsUseCase,
        private SinhVienPortalRepositoryInterface $repository,
    ) {
    }

    /**
     * Get UserProfile from JWT token
     */
    private function getUserProfileFromToken(): ?UserProfile
    {
        $taiKhoan = JWTAuth::parseToken()->authenticate();
        return UserProfile::where('tai_khoan_id', $taiKhoan->id)->first();
    }

    /**
     * GET /api/sv/profile
     */
    public function getProfile(): JsonResponse
    {
        try {
            $userProfile = $this->getUserProfileFromToken();

            if (!$userProfile) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin người dùng'
                ], 404);
            }

            $result = $this->getProfileUseCase->execute($userProfile->id);
            return response()->json($result);

        } catch (\RuntimeException $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => $e->getMessage()
            ], 404);
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
     */
    public function getTaiLieu($lopHocPhanId): JsonResponse
    {
        try {
            $userProfile = $this->getUserProfileFromToken();

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

            $result = $this->getDocumentsUseCase->execute($sinhVien->id, $lopHocPhanId);
            return response()->json($result);

        } catch (\RuntimeException $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => $e->getMessage()
            ], 403);
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
     */
    public function downloadTaiLieu($lopHocPhanId, $docId): mixed
    {
        try {
            $userProfile = $this->getUserProfileFromToken();

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

            // Check enrollment
            if (!$this->repository->isStudentEnrolled($sinhVien->id, $lopHocPhanId)) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không có quyền truy cập lớp học phần này'
                ], 403);
            }

            // Find document
            $document = $this->repository->findDocument($docId, $lopHocPhanId);

            if (!$document) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Tài liệu không tồn tại hoặc không thuộc lớp học phần này'
                ], 404);
            }

            // Check S3 configuration
            if (!config('filesystems.disks.s3.key')) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Dịch vụ lưu trữ file chưa được cấu hình'
                ], 503);
            }

            // Download from S3
            $filePath = $document->file_path ?? $document->s3_key;
            $filename = basename($filePath);

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
}
