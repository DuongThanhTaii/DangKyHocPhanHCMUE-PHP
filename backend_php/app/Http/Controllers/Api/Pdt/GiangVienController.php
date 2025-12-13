<?php

namespace App\Http\Controllers\Api\Pdt;

use App\Http\Controllers\Controller;
use App\Infrastructure\GiangVien\Persistence\Models\GiangVien;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use App\Infrastructure\Auth\Persistence\Models\TaiKhoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GiangVienController extends Controller
{
    /**
     * GET /api/pdt/giang-vien
     * Get all instructors
     */
    public function index(Request $request)
    {
        try {
            $page = (int) $request->query('page', 1);
            $pageSize = (int) $request->query('pageSize', 10000);

            $giangViens = GiangVien::with(['user', 'khoa'])
                ->skip(($page - 1) * $pageSize)
                ->take($pageSize)
                ->get();

            $data = $giangViens->map(function ($gv) {
                return [
                    'id' => $gv->id,
                    'khoa_id' => $gv->khoa_id,
                    'trinh_do' => $gv->trinh_do ?? '',
                    'chuyen_mon' => $gv->chuyen_mon ?? '',
                    'kinh_nghiem_giang_day' => $gv->kinh_nghiem_giang_day ?? 0,
                    'users' => [
                        'id' => $gv->user?->id ?? '',
                        'ho_ten' => $gv->user?->ho_ten ?? '',
                        'ma_nhan_vien' => $gv->user?->ma_nhan_vien ?: ($gv->ma_giang_vien ?? ''),
                        'tai_khoan' => null,
                    ],
                    'khoa' => [
                        'id' => $gv->khoa?->id ?? '',
                        'ten_khoa' => $gv->khoa?->ten_khoa ?? '',
                    ],
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => [
                    'items' => $data,
                    'total' => $data->count(),
                ],
                'message' => "Lấy thành công {$data->count()} giảng viên"
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
     * POST /api/pdt/giang-vien
     * Create instructor
     */
    public function store(Request $request)
    {
        try {
            // Map field names: frontend sends snake_case
            $tenDangNhap = $request->input('ten_dang_nhap') ?? $request->input('tenDangNhap') ?? $request->input('maGiangVien') ?? $request->input('ma_giang_vien');
            $hoTen = $request->input('ho_ten') ?? $request->input('hoTen');
            $matKhau = $request->input('mat_khau') ?? $request->input('matKhau') ?? $request->input('password') ?? 'password123';
            $khoaId = $request->input('khoa_id') ?? $request->input('khoaId');
            $trinhDo = $request->input('trinh_do') ?? $request->input('trinhDo');
            $chuyenMon = $request->input('chuyen_mon') ?? $request->input('chuyenMon');
            $kinhNghiem = $request->input('kinh_nghiem_giang_day') ?? $request->input('kinhNghiemGiangDay') ?? 0;

            // Auto-generate email if not provided
            $email = $request->input('email') ?? $tenDangNhap . '@gv.edu.vn';

            if (!$tenDangNhap || !$hoTen) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Thiếu thông tin bắt buộc (ten_dang_nhap/tên đăng nhập, ho_ten/họ tên)'
                ], 400);
            }

            // Create TaiKhoan
            $taiKhoan = TaiKhoan::create([
                'id' => Str::uuid()->toString(),
                'ten_dang_nhap' => $tenDangNhap,
                'mat_khau' => Hash::make($matKhau),
                'loai_tai_khoan' => 'giang_vien',
                'trang_thai_hoat_dong' => true,
            ]);

            // Create UserProfile
            $userProfile = UserProfile::create([
                'id' => Str::uuid()->toString(),
                'tai_khoan_id' => $taiKhoan->id,
                'ho_ten' => $hoTen,
                'email' => $email,
                'ma_nhan_vien' => $tenDangNhap,  // Set MGV from ten_dang_nhap
            ]);

            // Create GiangVien
            $giangVien = GiangVien::create([
                'id' => $userProfile->id,
                'ma_giang_vien' => $tenDangNhap,
                'khoa_id' => $khoaId,
                'trinh_do' => $trinhDo,
                'chuyen_mon' => $chuyenMon,
                'kinh_nghiem_giang_day' => (int) $kinhNghiem,
            ]);

            return response()->json([
                'isSuccess' => true,
                'data' => ['id' => $giangVien->id],
                'message' => 'Tạo giảng viên thành công'
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
     * PUT /api/pdt/giang-vien/{id}
     * Update instructor
     */
    public function update(Request $request, $id)
    {
        try {
            $giangVien = GiangVien::with('user')->find($id);

            if (!$giangVien) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy giảng viên'
                ], 404);
            }

            // Update GiangVien
            if ($request->has('maGiangVien') || $request->has('ma_giang_vien')) {
                $giangVien->ma_giang_vien = $request->input('maGiangVien') ?? $request->input('ma_giang_vien');
            }
            if ($request->has('khoaId') || $request->has('khoa_id')) {
                $giangVien->khoa_id = $request->input('khoaId') ?? $request->input('khoa_id');
            }
            if ($request->has('hocVi') || $request->has('hoc_vi')) {
                $giangVien->hoc_vi = $request->input('hocVi') ?? $request->input('hoc_vi');
            }
            $giangVien->save();

            // Update UserProfile
            $user = $giangVien->user;
            if ($user) {
                if ($request->has('hoTen') || $request->has('ho_ten')) {
                    $user->ho_ten = $request->input('hoTen') ?? $request->input('ho_ten');
                }
                if ($request->has('email')) {
                    $user->email = $request->input('email');
                }
                $user->save();
            }

            return response()->json([
                'isSuccess' => true,
                'data' => ['id' => $giangVien->id],
                'message' => 'Cập nhật giảng viên thành công'
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
     * DELETE /api/pdt/giang-vien/{id}
     * Delete instructor
     */
    public function destroy($id)
    {
        try {
            $giangVien = GiangVien::find($id);

            if (!$giangVien) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy giảng viên'
                ], 404);
            }

            // Delete giangVien (soft delete if implemented, otherwise hard delete)
            $giangVien->delete();

            return response()->json([
                'isSuccess' => true,
                'data' => null,
                'message' => 'Xóa giảng viên thành công'
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
