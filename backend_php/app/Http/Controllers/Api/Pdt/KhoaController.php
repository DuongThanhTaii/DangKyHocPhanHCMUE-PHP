<?php

namespace App\Http\Controllers\Api\Pdt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Application\Pdt\UseCases\GetKhoaListUseCase;

/**
 * KhoaController - Quản lý khoa (Refactored - Clean Architecture)
 * 
 * Thin controller - delegates business logic to UseCases
 */
class KhoaController extends Controller
{
    public function __construct(
        private GetKhoaListUseCase $getListUseCase,
    ) {
    }

    /**
     * GET /api/pdt/khoa
     * Get departments list
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $result = $this->getListUseCase->execute();
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
