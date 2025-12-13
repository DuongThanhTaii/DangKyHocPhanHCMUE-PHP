<?php

namespace App\Http\Controllers\Api\GiangVien;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Application\GiangVien\UseCases\GetLopHocPhanForGVUseCase;
use App\Application\GiangVien\UseCases\GetStudentsInClassUseCase;
use App\Application\GiangVien\UseCases\GetGradesForClassUseCase;
use App\Application\GiangVien\UseCases\UpdateGradesUseCase;
use App\Application\GiangVien\UseCases\GetGVWeeklyScheduleUseCase;
use App\Domain\GiangVien\Repositories\GVRepositoryInterface;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use App\Infrastructure\SinhVien\Persistence\Models\TaiLieu;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;

/**
 * GVController - Endpoints cho Giảng viên (Refactored - Clean Architecture)
 * 
 * Thin controller - delegates business logic to UseCases
 */
class GVController extends Controller
{
    public function __construct(
        private GetLopHocPhanForGVUseCase $getLopUseCase,
        private GetStudentsInClassUseCase $getStudentsUseCase,
        private GetGradesForClassUseCase $getGradesUseCase,
        private UpdateGradesUseCase $updateGradesUseCase,
        private GetGVWeeklyScheduleUseCase $getScheduleUseCase,
        private GVRepositoryInterface $repository,
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
     * GET /api/gv/lop-hoc-phan?hocKyId={id}
     */
    public function getLopHocPhanList(Request $request): JsonResponse
    {
        try {
            $hocKyId = $request->query('hocKyId') ?? $request->query('hoc_ky_id');

            $userProfile = $this->getUserProfileFromToken();

            if (!$userProfile) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin giảng viên'
                ], 404);
            }

            $result = $this->getLopUseCase->execute($userProfile->id, $hocKyId);
            return response()->json($result);

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
     */
    public function getLopHocPhanDetail(Request $request, $id): JsonResponse
    {
        try {
            $userProfile = $this->getUserProfileFromToken();

            if (!$userProfile) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin giảng viên'
                ], 404);
            }

            $lhp = $this->repository->findLopHocPhanByGV($id, $userProfile->id);

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
     */
    public function getLopHocPhanStudents(Request $request, $id): JsonResponse
    {
        try {
            $userProfile = $this->getUserProfileFromToken();

            if (!$userProfile) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin giảng viên'
                ], 404);
            }

            $result = $this->getStudentsUseCase->execute($id, $userProfile->id);
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
     * GET /api/gv/lop-hoc-phan/{id}/diem
     */
    public function getGrades(Request $request, $id): JsonResponse
    {
        try {
            $userProfile = $this->getUserProfileFromToken();

            if (!$userProfile) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin giảng viên'
                ], 404);
            }

            $result = $this->getGradesUseCase->execute($id, $userProfile->id);
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
     * PUT /api/gv/lop-hoc-phan/{id}/diem
     */
    public function updateGrades(Request $request, $id): JsonResponse
    {
        try {
            $userProfile = $this->getUserProfileFromToken();

            if (!$userProfile) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin giảng viên'
                ], 404);
            }

            $items = $request->input('items') ?? $request->input('diem', []);

            $result = $this->updateGradesUseCase->execute($id, $userProfile->id, $items);
            return response()->json($result);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => $e->getMessage()
            ], 400);
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
     * GET /api/gv/lop-hoc-phan/{id}/tai-lieu
     */
    public function getTaiLieuList(Request $request, $id): JsonResponse
    {
        try {
            $userProfile = $this->getUserProfileFromToken();

            if (!$userProfile) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin giảng viên'
                ], 404);
            }

            $lhp = $this->repository->findLopHocPhanByGV($id, $userProfile->id);

            if (!$lhp) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy lớp học phần hoặc không có quyền truy cập'
                ], 404);
            }

            $taiLieus = $this->repository->getDocumentsForClass($id);

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
     */
    public function uploadTaiLieu(Request $request, $id): JsonResponse
    {
        try {
            $userProfile = $this->getUserProfileFromToken();

            if (!$userProfile) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin giảng viên'
                ], 404);
            }

            $lhp = $this->repository->findLopHocPhanByGV($id, $userProfile->id);

            if (!$lhp) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy lớp học phần hoặc không có quyền truy cập'
                ], 404);
            }

            if (!$request->hasFile('file')) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không có file được upload'
                ], 400);
            }

            $file = $request->file('file');
            $tenTaiLieu = $request->input('ten_tai_lieu', $file->getClientOriginalName());
            $s3Key = "tai-lieu/{$id}/" . Str::uuid() . '_' . $file->getClientOriginalName();

            // Upload to S3 if configured
            if (config('filesystems.disks.s3.key')) {
                Storage::disk('s3')->put($s3Key, file_get_contents($file), 'private');
            }

            $taiLieu = $this->repository->createDocument([
                'lop_hoc_phan_id' => $id,
                'uploaded_by' => $userProfile->id,
                'ten_tai_lieu' => $tenTaiLieu,
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                's3_key' => $s3Key,
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
     */
    public function getTaiLieuDetail(Request $request, $id, $doc_id): JsonResponse
    {
        try {
            $userProfile = $this->getUserProfileFromToken();

            if (!$userProfile) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin giảng viên'
                ], 404);
            }

            $lhp = $this->repository->findLopHocPhanByGV($id, $userProfile->id);

            if (!$lhp) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy lớp học phần hoặc không có quyền truy cập'
                ], 404);
            }

            $taiLieu = $this->repository->findDocument($id, $doc_id);

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
     */
    public function downloadTaiLieu(Request $request, $id, $doc_id)
    {
        try {
            $userProfile = $this->getUserProfileFromToken();

            if (!$userProfile) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin giảng viên'
                ], 404);
            }

            $lhp = $this->repository->findLopHocPhanByGV($id, $userProfile->id);

            if (!$lhp) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy lớp học phần hoặc không có quyền truy cập'
                ], 404);
            }

            $taiLieu = $this->repository->findDocument($id, $doc_id);

            if (!$taiLieu) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy tài liệu'
                ], 404);
            }

            if (!config('filesystems.disks.s3.key')) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'S3 chưa được cấu hình'
                ], 503);
            }

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
     * GET /api/gv/tkb-weekly?hocKyId={id}&dateStart={}&dateEnd={}
     */
    public function getTKBWeekly(Request $request): JsonResponse
    {
        try {
            $hocKyId = $request->query('hocKyId') ?? $request->query('hoc_ky_id');

            if (!$hocKyId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'hoc_ky_id is required'
                ], 400);
            }

            $userProfile = $this->getUserProfileFromToken();

            if (!$userProfile) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => [],
                    'message' => 'Không tìm thấy thông tin giảng viên'
                ]);
            }

            $result = $this->getScheduleUseCase->execute($userProfile->id, $hocKyId);
            return response()->json($result);

        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }
}
