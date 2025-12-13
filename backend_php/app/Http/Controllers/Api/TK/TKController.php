<?php

namespace App\Http\Controllers\Api\TK;

use App\Http\Controllers\Controller;
use App\Infrastructure\TK\Persistence\Models\TruongKhoa;
use App\Infrastructure\TLK\Persistence\Models\DeXuatHocPhan;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class TKController extends Controller
{
    /**
     * Get TruongKhoa from JWT token
     */
    private function getTKFromToken()
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
     * Get course proposals for TruongKhoa's department (view proposals needing approval)
     */
    public function getDeXuatHocPhan(Request $request)
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

            // Get DeXuatHocPhan for TK's department that need approval
            // cap_duyet_hien_tai = 'truong_khoa' means waiting for TK approval
            $query = DeXuatHocPhan::with(['monHoc', 'giangVienDeXuat.user', 'nguoiTao'])
                ->where('khoa_id', $tk->khoa_id)
                ->where('cap_duyet_hien_tai', 'truong_khoa');

            if ($hocKyId) {
                $query->where('hoc_ky_id', $hocKyId);
            }

            $deXuats = $query->orderBy('created_at', 'desc')->get();

            $data = $deXuats->map(function ($dx) {
                $monHoc = $dx->monHoc;
                $gvDeXuat = $dx->giangVienDeXuat;

                return [
                    'id' => $dx->id,
                    'trangThai' => $dx->trang_thai ?? 'cho_duyet',
                    'capDuyetHienTai' => $dx->cap_duyet_hien_tai ?? '',
                    'soLopDuKien' => $dx->so_lop_du_kien ?? 0,
                    'ngayTao' => $dx->created_at?->toISOString(),
                    'ghiChu' => $dx->ghi_chu ?? '',
                    'monHoc' => [
                        'id' => $monHoc?->id ?? '',
                        'maMon' => $monHoc?->ma_mon ?? '',
                        'tenMon' => $monHoc?->ten_mon ?? '',
                        'soTinChi' => $monHoc?->so_tin_chi ?? 0,
                    ],
                    'giangVienDeXuat' => [
                        'id' => $dx->giang_vien_de_xuat ?? '',
                        'hoTen' => $gvDeXuat?->user?->ho_ten ?? '',
                    ],
                    'nguoiTao' => [
                        'id' => $dx->nguoi_tao ?? '',
                        'hoTen' => $dx->nguoiTao?->ho_ten ?? '',
                    ],
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => "Lấy thành công {$data->count()} đề xuất chờ duyệt"
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
     * POST /api/tk/de-xuat-hoc-phan/duyet
     * Approve course proposal
     * Body: { "id": "uuid" }
     */
    public function duyetDeXuat(Request $request)
    {
        try {
            $deXuatId = $request->input('id');

            if (!$deXuatId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'ID đề xuất học phần không được rỗng'
                ], 400);
            }

            $tk = $this->getTKFromToken();

            if (!$tk) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin trưởng khoa'
                ], 404);
            }

            // Find the DeXuatHocPhan
            $deXuat = DeXuatHocPhan::where('khoa_id', $tk->khoa_id)
                ->where('cap_duyet_hien_tai', 'truong_khoa')
                ->find($deXuatId);

            if (!$deXuat) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy đề xuất hoặc đề xuất không thuộc quyền duyệt của bạn'
                ], 404);
            }

            // Update status - approved by TK, move to next approval level (pdt) or final
            $deXuat->trang_thai = 'da_duyet_tk';
            $deXuat->cap_duyet_hien_tai = 'pdt'; // Move to PDT for final approval
            $deXuat->updated_at = now();
            $deXuat->save();

            return response()->json([
                'isSuccess' => true,
                'data' => [
                    'id' => $deXuat->id,
                    'trangThai' => $deXuat->trang_thai,
                    'capDuyetHienTai' => $deXuat->cap_duyet_hien_tai,
                ],
                'message' => 'Duyệt đề xuất thành công'
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
     * POST /api/tk/de-xuat-hoc-phan/tu-choi
     * Reject course proposal
     * Body: { "id": "uuid", "lyDo": "reason" }
     */
    public function tuChoiDeXuat(Request $request)
    {
        try {
            $deXuatId = $request->input('id');
            $lyDo = $request->input('lyDo') ?? $request->input('ly_do') ?? '';

            if (!$deXuatId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'ID đề xuất học phần không được rỗng'
                ], 400);
            }

            $tk = $this->getTKFromToken();

            if (!$tk) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin trưởng khoa'
                ], 404);
            }

            // Find the DeXuatHocPhan
            $deXuat = DeXuatHocPhan::where('khoa_id', $tk->khoa_id)
                ->where('cap_duyet_hien_tai', 'truong_khoa')
                ->find($deXuatId);

            if (!$deXuat) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy đề xuất hoặc đề xuất không thuộc quyền duyệt của bạn'
                ], 404);
            }

            // Update status - rejected by TK
            $deXuat->trang_thai = 'tu_choi';
            $deXuat->cap_duyet_hien_tai = 'truong_khoa'; // Keep as 'truong_khoa' - constraint allows only 'truong_khoa' or 'pdt'
            $deXuat->ghi_chu = $lyDo ? "Lý do từ chối: {$lyDo}" : 'Bị từ chối bởi Trưởng Khoa';
            $deXuat->updated_at = now();
            $deXuat->save();

            return response()->json([
                'isSuccess' => true,
                'data' => [
                    'id' => $deXuat->id,
                    'trangThai' => $deXuat->trang_thai,
                    'capDuyetHienTai' => $deXuat->cap_duyet_hien_tai,
                ],
                'message' => 'Từ chối đề xuất thành công'
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
