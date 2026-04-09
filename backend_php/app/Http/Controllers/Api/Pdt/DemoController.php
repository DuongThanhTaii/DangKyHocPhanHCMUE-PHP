<?php

namespace App\Http\Controllers\Api\Pdt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Application\Pdt\DTOs\TogglePhaseDTO;
use App\Application\Pdt\UseCases\TogglePhaseUseCase;

/**
 * DemoController - Quản lý demo/test (Refactored - Clean Architecture)
 * 
 * Thin controller - delegates business logic to UseCases
 */
class DemoController extends Controller
{
    public function __construct(
        private TogglePhaseUseCase $togglePhaseUseCase,
    ) {
    }

    /**
     * POST /api/pdt/demo/toggle-phase
     * POST /api/pdt/ky-phase/toggle (alias)
     * Toggle phase enabled status
     */
    public function togglePhase(Request $request): JsonResponse
    {
        try {
            $dto = TogglePhaseDTO::fromRequest($request->all());
            $result = $this->togglePhaseUseCase->execute($dto);
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
     * POST /api/pdt/demo/reset-data
     * Reset demo data - dangerous operation
     */
    public function resetData(Request $request): JsonResponse
    {
        try {
            $confirmReset = (bool) $request->input('confirmReset', false);

            if (! $confirmReset) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Phải xác nhận reset (confirmReset=true)'
                ], 400);
            }

            // Keep master data (users, mon_hoc, khoa, phong, hoc_ky, nien_khoa...)
            // Clear demo/test operational data only.
            $tablesToClear = [
                // Payments & tuition
                'payment_ipn_logs',
                'payment_transactions',
                'chi_tiet_hoc_phi',
                'mien_giam_hoc_phi',
                'hoc_phi',
                'chinh_sach_tin_chi',

                // Registration history
                'chi_tiet_lich_su_dang_ky',
                'lich_su_dang_ky',
                'dang_ky_tkb',
                'dang_ky_hoc_phan',

                // Enrollment
                'ghi_danh_hoc_phan',

                // Documents & notifications
                'thong_bao_nguoi_nhan',
                'thong_bao',
                'tai_lieu',

                // Class instances & schedules
                'ket_qua_hoc_phan',
                'lich_day_lop_hoc_phan',
                'lich_hoc_dinh_ky',
                'lich_su_xoa_lop_hoc_phan',
                'lop_hoc_phan',
                'hoc_phan',

                // Proposals
                'de_xuat_hoc_phan_log',
                'de_xuat_hoc_phan_gv',
                'de_xuat_hoc_phan',

                // Phase & registration windows
                'dot_dang_ky',
                'ky_phase',
            ];

            $driver = DB::connection()->getDriverName();
            $clearedTables = [];
            $errors = [];

            foreach ($tablesToClear as $table) {
                try {
                    if ($driver === 'pgsql') {
                        DB::statement("TRUNCATE TABLE \"{$table}\" RESTART IDENTITY CASCADE");
                    } else {
                        DB::table($table)->delete();
                    }
                    $clearedTables[] = $table;
                } catch (\Throwable $tableError) {
                    $errors[] = "Failed to clear {$table}: {$tableError->getMessage()}";
                }
            }

            return response()->json([
                'isSuccess' => true,
                'data' => [
                    'clearedTables' => $clearedTables,
                    'errors' => $errors,
                    'mongoCleared' => false,
                    'totalCleared' => count($clearedTables),
                ],
                'message' => 'Reset thành công ' . count($clearedTables) . ' bảng'
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
