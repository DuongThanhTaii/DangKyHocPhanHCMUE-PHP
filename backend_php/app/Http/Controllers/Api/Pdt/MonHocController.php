<?php

namespace App\Http\Controllers\Api\Pdt;

use App\Http\Controllers\Controller;
use App\Infrastructure\SinhVien\Persistence\Models\MonHoc;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MonHocController extends Controller
{
    /**
     * GET /api/pdt/mon-hoc
     * Get all courses
     */
    public function index(Request $request)
    {
        try {
            $page = (int) $request->query('page', 1);
            $pageSize = (int) $request->query('pageSize', 10000);

            $monHocs = MonHoc::with('khoa')
                ->orderBy('ma_mon', 'asc')
                ->skip(($page - 1) * $pageSize)
                ->take($pageSize)
                ->get();

            $data = $monHocs->map(function ($m) {
                return [
                    'id' => $m->id,
                    'ma_mon' => $m->ma_mon ?? '',
                    'ten_mon' => $m->ten_mon ?? '',
                    'so_tin_chi' => $m->so_tin_chi ?? 0,
                    'khoa_id' => $m->khoa_id,
                    'loai_mon' => $m->loai_mon ?? null,
                    'la_mon_chung' => $m->la_mon_chung ?? false,
                    'thu_tu_hoc' => $m->thu_tu_hoc ?? null,
                    'khoa' => $m->khoa ? [
                        'id' => $m->khoa->id,
                        'ten_khoa' => $m->khoa->ten_khoa ?? '',
                    ] : null,
                    'mon_hoc_nganh' => [],
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => [
                    'items' => $data,
                    'total' => $data->count(),
                ],
                'message' => "Lấy thành công {$data->count()} môn học"
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
     * POST /api/pdt/mon-hoc
     * Create course
     */
    public function store(Request $request)
    {
        try {
            $maMon = $request->input('maMon') ?? $request->input('ma_mon');
            $tenMon = $request->input('tenMon') ?? $request->input('ten_mon');

            if (!$maMon || !$tenMon) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thiếu thông tin bắt buộc (maMon, tenMon)'
                ], 400);
            }

            $monHoc = MonHoc::create([
                'id' => Str::uuid()->toString(),
                'ma_mon' => $maMon,
                'ten_mon' => $tenMon,
                'so_tin_chi' => $request->input('soTinChi') ?? $request->input('so_tin_chi') ?? 0,
                'khoa_id' => $request->input('khoaId') ?? $request->input('khoa_id'),
                'loai_mon' => $request->input('loaiMon') ?? $request->input('loai_mon'),
                'la_mon_chung' => filter_var($request->input('laMonChung') ?? $request->input('la_mon_chung') ?? false, FILTER_VALIDATE_BOOLEAN),
                'thu_tu_hoc' => $request->input('thuTuHoc') ?? $request->input('thu_tu_hoc') ?? 1,
            ]);

            return response()->json([
                'isSuccess' => true,
                'data' => ['id' => $monHoc->id],
                'message' => 'Tạo môn học thành công'
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
     * PUT /api/pdt/mon-hoc/{id}
     * Update course
     */
    public function update(Request $request, $id)
    {
        try {
            $monHoc = MonHoc::find($id);

            if (!$monHoc) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy môn học'
                ], 404);
            }

            if ($request->has('maMon') || $request->has('ma_mon')) {
                $monHoc->ma_mon = $request->input('maMon') ?? $request->input('ma_mon');
            }
            if ($request->has('tenMon') || $request->has('ten_mon')) {
                $monHoc->ten_mon = $request->input('tenMon') ?? $request->input('ten_mon');
            }
            if ($request->has('soTinChi') || $request->has('so_tin_chi')) {
                $monHoc->so_tin_chi = $request->input('soTinChi') ?? $request->input('so_tin_chi');
            }
            if ($request->has('khoaId') || $request->has('khoa_id')) {
                $monHoc->khoa_id = $request->input('khoaId') ?? $request->input('khoa_id');
            }

            $monHoc->save();

            return response()->json([
                'isSuccess' => true,
                'data' => ['id' => $monHoc->id],
                'message' => 'Cập nhật môn học thành công'
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
     * DELETE /api/pdt/mon-hoc/{id}
     * Delete course
     */
    public function destroy($id)
    {
        try {
            $monHoc = MonHoc::find($id);

            if (!$monHoc) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy môn học'
                ], 404);
            }

            $monHoc->delete();

            return response()->json([
                'isSuccess' => true,
                'data' => null,
                'message' => 'Xóa môn học thành công'
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
