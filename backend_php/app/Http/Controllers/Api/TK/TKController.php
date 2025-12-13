<?php

namespace App\Http\Controllers\Api\TK;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Application\TK\UseCases\GetDeXuatForTKUseCase;
use App\Application\TK\UseCases\ApproveDeXuatByTKUseCase;
use App\Application\TK\UseCases\RejectDeXuatByTKUseCase;
use App\Infrastructure\TK\Persistence\Models\TruongKhoa;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * TKController - Endpoints cho Trưởng Khoa (Refactored - Clean Architecture)
 * 
 * Thin controller - delegates business logic to UseCases
 */
class TKController extends Controller
{
    public function __construct(
        private GetDeXuatForTKUseCase $getDeXuatUseCase,
        private ApproveDeXuatByTKUseCase $approveUseCase,
        private RejectDeXuatByTKUseCase $rejectUseCase,
    ) {
    }

    /**
     * Get TruongKhoa from JWT token
     */
    private function getTKFromToken(): ?TruongKhoa
    {
        $taiKhoan = JWTAuth::parseToken()->authenticate();
        $userProfile = UserProfile::where('tai_khoan_id', $taiKhoan->id)->first();

        if (!$userProfile) {
            return null;
        }

        return TruongKhoa::with('khoa')->find($userProfile->id);
    }

    /**
     * GET /api/tk/de-xuat-hoc-phan?hocKyId={id}
     * Get course proposals pending TK approval
     */
    public function getDeXuatHocPhan(Request $request): JsonResponse
    {
        try {
            $hocKyId = $request->query('hocKyId') ?? $request->query('hoc_ky_id');

            $tk = $this->getTKFromToken();

            if (!$tk) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin trưởng khoa'
                ], 404);
            }

            $result = $this->getDeXuatUseCase->execute($tk->khoa_id, $hocKyId);
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
     * POST /api/tk/de-xuat-hoc-phan/duyet
     * Approve course proposal
     * Body: { "id": "uuid" }
     */
    public function duyetDeXuat(Request $request): JsonResponse
    {
        try {
            $deXuatId = $request->input('id') ?? '';

            $tk = $this->getTKFromToken();

            if (!$tk) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin trưởng khoa'
                ], 404);
            }

            $result = $this->approveUseCase->execute($deXuatId, $tk->khoa_id);
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
     * POST /api/tk/de-xuat-hoc-phan/tu-choi
     * Reject course proposal
     * Body: { "id": "uuid", "lyDo": "reason" }
     */
    public function tuChoiDeXuat(Request $request): JsonResponse
    {
        try {
            $deXuatId = $request->input('id') ?? '';
            $lyDo = $request->input('lyDo') ?? $request->input('ly_do') ?? '';

            $tk = $this->getTKFromToken();

            if (!$tk) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin trưởng khoa'
                ], 404);
            }

            $result = $this->rejectUseCase->execute($deXuatId, $tk->khoa_id, $lyDo);
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
}
