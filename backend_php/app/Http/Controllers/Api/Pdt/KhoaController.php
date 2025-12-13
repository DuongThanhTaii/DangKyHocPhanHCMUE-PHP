<?php

namespace App\Http\Controllers\Api\Pdt;

use App\Http\Controllers\Controller;
use App\Infrastructure\Common\Persistence\Models\Khoa;
use Illuminate\Http\Request;

class KhoaController extends Controller
{
    /**
     * GET /api/pdt/khoa
     * Get departments list
     */
    public function index(Request $request)
    {
        try {
            $khoas = Khoa::orderBy('ten_khoa', 'asc')->get();

            $data = $khoas->map(function ($k) {
                return [
                    'id' => $k->id,
                    'maKhoa' => $k->ma_khoa ?? '',
                    'tenKhoa' => $k->ten_khoa ?? '',
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
}
