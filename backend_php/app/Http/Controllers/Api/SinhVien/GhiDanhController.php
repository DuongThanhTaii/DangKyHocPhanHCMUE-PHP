<?php

namespace App\Http\Controllers\Api\SinhVien;

use App\Http\Controllers\Controller;
use App\Application\SinhVien\UseCases\CheckGhiDanhConditionUseCase;
use App\Application\SinhVien\UseCases\GetAvailableSubjectsUseCase;
use App\Application\SinhVien\UseCases\EnrollSubjectUseCase;
use App\Application\SinhVien\UseCases\GetEnrolledSubjectsUseCase;
use App\Application\SinhVien\UseCases\CancelEnrollmentUseCase;
use App\Domain\SinhVien\Repositories\GhiDanhRepositoryInterface;
use App\Infrastructure\SinhVien\Persistence\Models\SinhVien;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * GhiDanhController - Subject Enrollment (Refactored - Clean Architecture)
 * 
 * Thin controller - delegates to UseCases
 */
class GhiDanhController extends Controller
{
    public function __construct(
        private CheckGhiDanhConditionUseCase $checkConditionUseCase,
        private GetAvailableSubjectsUseCase $getSubjectsUseCase,
        private EnrollSubjectUseCase $enrollUseCase,
        private GetEnrolledSubjectsUseCase $getEnrolledUseCase,
        private CancelEnrollmentUseCase $cancelUseCase,
        private GhiDanhRepositoryInterface $repository,
    ) {
    }

    /**
     * Get SinhVien from JWT token
     */
    private function getSinhVienFromToken(): ?SinhVien
    {
        $taiKhoan = JWTAuth::parseToken()->authenticate();
        $userProfile = UserProfile::where('tai_khoan_id', $taiKhoan->id)->first();

        if (!$userProfile) {
            return null;
        }

        return SinhVien::with(['khoa'])->find($userProfile->id);
    }

    /**
     * GET /api/sv/check-ghi-danh
     */
    public function checkGhiDanh(): JsonResponse
    {
        try {
            $sinhVien = $this->getSinhVienFromToken();

            if (!$sinhVien) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin sinh viên'
                ], 404);
            }

            $result = $this->checkConditionUseCase->execute($sinhVien->id);

            if (!$result['canEnroll']) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => $result,
                    'message' => $result['reason']
                ], 400);
            }

            return response()->json([
                'isSuccess' => true,
                'data' => $result,
                'message' => 'Có thể ghi danh'
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
     * GET /api/sv/mon-hoc-ghi-danh?hocKyId={id}
     * Nếu không truyền hocKyId, sẽ tự động lấy học kỳ hiện hành
     */
    public function getMonHocGhiDanh(Request $request): JsonResponse
    {
        try {
            $hocKyId = $request->query('hocKyId') ?? $request->query('hoc_ky_id');

            // Nếu không truyền hocKyId, tự động lấy học kỳ hiện hành
            if (!$hocKyId) {
                $hocKy = $this->repository->getCurrentHocKy();
                if (!$hocKy) {
                    return response()->json([
                        'isSuccess' => false,
                        'data' => null,
                        'message' => 'Không tìm thấy học kỳ hiện hành'
                    ], 400);
                }
                $hocKyId = $hocKy->id;
            }

            $sinhVien = $this->getSinhVienFromToken();

            if (!$sinhVien) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin sinh viên'
                ], 404);
            }

            $khoaId = $sinhVien->khoa_id ?? '';

            $result = $this->getSubjectsUseCase->execute($hocKyId, $khoaId);
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
     * POST /api/sv/ghi-danh
     * Body: { "monHocId": "uuid" }
     */
    public function ghiDanh(Request $request): JsonResponse
    {
        try {
            $monHocId = $request->input('monHocId') ?? $request->input('mon_hoc_id');

            if (!$monHocId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thiếu monHocId'
                ], 400);
            }

            $sinhVien = $this->getSinhVienFromToken();

            if (!$sinhVien) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin sinh viên'
                ], 404);
            }

            // Check conditions
            $conditionResult = $this->checkConditionUseCase->execute($sinhVien->id);

            if (!$conditionResult['canEnroll']) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => $conditionResult['reason']
                ], 400);
            }

            $hocKyId = $conditionResult['hocKyId'];

            $result = $this->enrollUseCase->execute($sinhVien->id, $monHocId, $hocKyId);
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
     * GET /api/sv/ghi-danh/my
     */
    public function getDanhSachDaGhiDanh(): JsonResponse
    {
        try {
            $sinhVien = $this->getSinhVienFromToken();

            if (!$sinhVien) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin sinh viên'
                ], 404);
            }

            $hocKy = $this->repository->getCurrentHocKy();

            if (!$hocKy) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy học kỳ hiện hành'
                ], 400);
            }

            $result = $this->getEnrolledUseCase->execute($sinhVien->id, $hocKy->id);
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
     * DELETE /api/sv/ghi-danh/{id}
     */
    public function huyGhiDanh(string $id): JsonResponse
    {
        try {
            $sinhVien = $this->getSinhVienFromToken();

            if (!$sinhVien) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin sinh viên'
                ], 404);
            }

            // Check conditions
            $conditionResult = $this->checkConditionUseCase->execute($sinhVien->id);

            if (!$conditionResult['canEnroll']) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không trong giai đoạn ghi danh'
                ], 400);
            }

            $result = $this->cancelUseCase->execute($id, $sinhVien->id);
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
     * POST /api/sv/huy-ghi-danh
     * Body: { "ghiDanhIds": ["id1", "id2", ...] }
     */
    public function huyGhiDanhBatch(Request $request): JsonResponse
    {
        try {
            $ghiDanhIds = $request->input('ghiDanhIds') ?? $request->input('ghi_danh_ids') ?? [];

            if (empty($ghiDanhIds)) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Danh sách ghi danh rỗng'
                ], 400);
            }

            $sinhVien = $this->getSinhVienFromToken();

            if (!$sinhVien) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin sinh viên'
                ], 404);
            }

            // Check conditions
            $conditionResult = $this->checkConditionUseCase->execute($sinhVien->id);

            if (!$conditionResult['canEnroll']) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không trong giai đoạn ghi danh'
                ], 400);
            }

            $result = $this->cancelUseCase->executeBatch($ghiDanhIds, $sinhVien->id);
            return response()->json($result);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }
}
