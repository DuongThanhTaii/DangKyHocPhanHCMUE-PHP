<?php

namespace App\Http\Controllers\Api\TLK;

use App\Http\Controllers\Controller;
use App\Infrastructure\TLK\Persistence\Models\TroLyKhoa;
use App\Infrastructure\TLK\Persistence\Models\DeXuatHocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\MonHoc;
use App\Infrastructure\SinhVien\Persistence\Models\HocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\LopHocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\LichHocDinhKy;
use App\Infrastructure\SinhVien\Persistence\Models\Phong;
use App\Infrastructure\GiangVien\Persistence\Models\GiangVien;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use App\Infrastructure\Common\Persistence\Models\HocKy;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class TLKController extends Controller
{
    /**
     * Get TroLyKhoa from JWT token
     */
    private function getTLKFromToken()
    {
        $taiKhoan = JWTAuth::parseToken()->authenticate();
        $userProfile = UserProfile::where('tai_khoan_id', $taiKhoan->id)->first();

        if (!$userProfile) {
            return null;
        }

        return TroLyKhoa::with('khoa')->find($userProfile->id);
    }

    /**
     * GET /api/tlk/mon-hoc
     * Get list of courses for TLK's department
     */
    public function getMonHoc(Request $request)
    {
        try {
            $tlk = $this->getTLKFromToken();

            if (!$tlk) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin trợ lý khoa'
                ], 404);
            }

            // Get MonHoc of TLK's khoa
            $monHocs = MonHoc::where('khoa_id', $tlk->khoa_id)->get();

            $data = $monHocs->map(function ($mh) {
                return [
                    'id' => $mh->id,
                    'maMon' => $mh->ma_mon,
                    'tenMon' => $mh->ten_mon,
                    'soTinChi' => $mh->so_tin_chi ?? 0,
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
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
     * GET /api/tlk/giang-vien?mon_hoc_id={id}
     * Get list of instructors for TLK's department
     */
    public function getGiangVien(Request $request)
    {
        try {
            $monHocId = $request->query('mon_hoc_id') ?? $request->query('monHocId');

            $tlk = $this->getTLKFromToken();

            if (!$tlk) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin trợ lý khoa'
                ], 404);
            }

            // Get GiangVien of TLK's khoa
            $giangViens = GiangVien::with('user')
                ->where('khoa_id', $tlk->khoa_id)
                ->get();

            $data = $giangViens->map(function ($gv) {
                $user = $gv->user;
                return [
                    'id' => $gv->id,
                    'hoTen' => $user?->ho_ten ?? '',
                    'email' => $user?->email ?? '',
                    'trinhDo' => $gv->trinh_do ?? '',
                    'chuyenMon' => $gv->chuyen_mon ?? '',
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
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
     * GET /api/tlk/lop-hoc-phan/get-hoc-phan/{hoc_ky_id}
     * Get HocPhan available for creating LopHocPhan
     */
    public function getHocPhanForSemester(Request $request, $hoc_ky_id)
    {
        try {
            $tlk = $this->getTLKFromToken();

            if (!$tlk) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin trợ lý khoa'
                ], 404);
            }

            // Get HocPhan of this semester for TLK's khoa
            $hocPhans = HocPhan::with('monHoc')
                ->where('id_hoc_ky', $hoc_ky_id)
                ->whereHas('monHoc', function ($q) use ($tlk) {
                    $q->where('khoa_id', $tlk->khoa_id);
                })
                ->get();

            $data = $hocPhans->map(function ($hp) use ($hoc_ky_id) {
                $monHoc = $hp->monHoc;

                // Count students enrolled in this hoc_phan
                $soSinhVienGhiDanh = \App\Infrastructure\SinhVien\Persistence\Models\GhiDanhHocPhan::where('hoc_phan_id', $hp->id)->count();

                // Get giangVien from DeXuatHocPhan
                $tenGiangVien = 'Chưa có giảng viên';
                $giangVienId = '';

                if ($monHoc) {
                    $deXuat = DeXuatHocPhan::with('giangVienDeXuat.user')
                        ->where('mon_hoc_id', $monHoc->id)
                        ->where('hoc_ky_id', $hoc_ky_id)
                        ->whereIn('trang_thai', ['cho_duyet', 'da_duyet_tk', 'da_duyet_pdt'])
                        ->first();

                    if ($deXuat && $deXuat->giangVienDeXuat && $deXuat->giangVienDeXuat->user) {
                        $tenGiangVien = $deXuat->giangVienDeXuat->user->ho_ten;
                        $giangVienId = $deXuat->giang_vien_de_xuat;
                    }
                }

                return [
                    'id' => $hp->id,
                    'hocPhanId' => $hp->id,
                    'maHocPhan' => $monHoc?->ma_mon ?? '',
                    'tenHocPhan' => $hp->ten_hoc_phan ?? $monHoc?->ten_mon ?? '',
                    'soTinChi' => $monHoc?->so_tin_chi ?? 0,
                    'soSinhVienGhiDanh' => $soSinhVienGhiDanh,
                    'tenGiangVien' => $tenGiangVien,
                    'giangVienId' => $giangVienId,
                    'trangThaiMo' => $hp->trang_thai_mo ?? false,
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => "Lấy thành công {$data->count()} học phần"
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
     * GET /api/tlk/phong-hoc
     * Get list of rooms for TLK's department
     */
    public function getPhongHoc(Request $request)
    {
        try {
            $tlk = $this->getTLKFromToken();

            if (!$tlk) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin trợ lý khoa'
                ], 404);
            }

            // Get Phong of TLK's khoa
            $phongs = Phong::where('khoa_id', $tlk->khoa_id)->get();

            $data = $phongs->map(function ($p) {
                return [
                    'id' => $p->id,
                    'maPhong' => $p->ma_phong,
                    'sucChua' => $p->suc_chua ?? 0,
                    'daSuDung' => $p->da_dc_su_dung ?? false,
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => "Lấy thành công {$data->count()} phòng học"
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
     * GET /api/tlk/phong-hoc/available
     * Get available (unassigned) rooms
     */
    public function getAvailablePhongHoc(Request $request)
    {
        try {
            $tlk = $this->getTLKFromToken();

            if (!$tlk) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin trợ lý khoa'
                ], 404);
            }

            // Get Phong of TLK's khoa that are not currently used
            $phongs = Phong::where('khoa_id', $tlk->khoa_id)
                ->where(function ($q) {
                    $q->whereNull('da_dc_su_dung')
                        ->orWhere('da_dc_su_dung', false);
                })
                ->get();

            $data = $phongs->map(function ($p) {
                return [
                    'id' => $p->id,
                    'maPhong' => $p->ma_phong,
                    'sucChua' => $p->suc_chua ?? 0,
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => $data,
                'message' => "Lấy thành công {$data->count()} phòng trống"
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
     * GET /api/tlk/de-xuat-hoc-phan?hocKyId={id}
     * Get course proposals for TLK's department (view only)
     */
    public function getDeXuatHocPhan(Request $request)
    {
        try {
            $hocKyId = $request->query('hocKyId') ?? $request->query('hoc_ky_id');

            $tlk = $this->getTLKFromToken();

            if (!$tlk) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin trợ lý khoa'
                ], 404);
            }

            $query = DeXuatHocPhan::with(['monHoc', 'giangVienDeXuat.user', 'nguoiTao'])
                ->where('khoa_id', $tlk->khoa_id);

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
                'message' => "Lấy thành công {$data->count()} đề xuất"
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
     * POST /api/tlk/de-xuat-hoc-phan
     * Create a course proposal
     * Body: { "maHocPhan": "mon_hoc_id", "maGiangVien": "giang_vien_id" }
     */
    public function createDeXuatHocPhan(Request $request)
    {
        try {
            $monHocId = $request->input('maHocPhan');
            $giangVienId = $request->input('maGiangVien');

            if (!$monHocId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'maHocPhan (môn học ID) là bắt buộc'
                ], 400);
            }

            $tlk = $this->getTLKFromToken();

            if (!$tlk) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin trợ lý khoa'
                ], 404);
            }

            // Get current semester
            $currentHocKy = HocKy::where('trang_thai_hien_tai', true)->first();
            if (!$currentHocKy) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy học kỳ hiện hành'
                ], 404);
            }

            // Verify MonHoc exists and belongs to TLK's khoa
            $monHoc = MonHoc::where('id', $monHocId)
                ->where('khoa_id', $tlk->khoa_id)
                ->first();

            if (!$monHoc) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy môn học hoặc môn học không thuộc khoa của bạn'
                ], 404);
            }

            // Check if already exists
            $existing = DeXuatHocPhan::where('mon_hoc_id', $monHocId)
                ->where('hoc_ky_id', $currentHocKy->id)
                ->where('khoa_id', $tlk->khoa_id)
                ->first();

            if ($existing) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Đề xuất cho môn học này trong học kỳ hiện tại đã tồn tại'
                ], 409);
            }

            // Create the proposal
            $deXuat = DeXuatHocPhan::create([
                'id' => Str::uuid()->toString(),
                'mon_hoc_id' => $monHocId,
                'hoc_ky_id' => $currentHocKy->id,
                'khoa_id' => $tlk->khoa_id,
                'nguoi_tao_id' => $tlk->id,
                'giang_vien_de_xuat' => $giangVienId ?: null,
                'trang_thai' => 'cho_duyet',
                'cap_duyet_hien_tai' => 'truong_khoa',
                'so_lop_du_kien' => 1,
            ]);

            return response()->json([
                'isSuccess' => true,
                'data' => [
                    'id' => $deXuat->id,
                    'monHocId' => $monHocId,
                    'trangThai' => 'cho_duyet',
                ],
                'message' => "Đã tạo đề xuất cho môn: {$monHoc->ten_mon}"
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
     * POST /api/tlk/thoi-khoa-bieu/batch
     * Get TKB for multiple hoc phans
     * Body: { "maHocPhans": ["HP001", "HP002"], "hocKyId": "uuid" }
     */
    public function getTKBBatch(Request $request)
    {
        try {
            $maHocPhans = $request->input('maHocPhans', []);
            $hocKyId = $request->input('hocKyId');

            if (empty($maHocPhans)) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Danh sách mã học phần không được rỗng'
                ], 400);
            }

            if (!$hocKyId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Học kỳ ID không được rỗng'
                ], 400);
            }

            // Get LopHocPhan by HocPhan ma codes
            $lopHocPhans = LopHocPhan::with(['hocPhan.monHoc', 'lichHocDinhKys.phong', 'giangVien'])
                ->whereHas('hocPhan', function ($q) use ($hocKyId) {
                    $q->where('id_hoc_ky', $hocKyId);
                })
                ->whereHas('hocPhan.monHoc', function ($q) use ($maHocPhans) {
                    $q->whereIn('ma_mon', $maHocPhans);
                })
                ->get();

            // Group by MonHoc
            $result = [];
            foreach ($lopHocPhans as $lhp) {
                $monHoc = $lhp->hocPhan?->monHoc;
                $maMon = $monHoc?->ma_mon ?? 'UNKNOWN';

                if (!isset($result[$maMon])) {
                    $result[$maMon] = [
                        'maMon' => $maMon,
                        'tenMon' => $monHoc?->ten_mon ?? '',
                        'danhSachLop' => [],
                    ];
                }

                $result[$maMon]['danhSachLop'][] = [
                    'id' => $lhp->id,
                    'maLop' => $lhp->ma_lop,
                    'soLuongHienTai' => $lhp->so_luong_hien_tai ?? 0,
                    'soLuongToiDa' => $lhp->so_luong_toi_da ?? 50,
                    'giangVien' => $lhp->giangVien?->ho_ten ?? '',
                    'tkb' => $lhp->lichHocDinhKys->map(function ($lich) {
                        return [
                            'thu' => $lich->thu,
                            'tietBatDau' => $lich->tiet_bat_dau,
                            'tietKetThuc' => $lich->tiet_ket_thuc,
                            'phong' => $lich->phong?->ma_phong ?? 'TBA',
                        ];
                    }),
                ];
            }

            return response()->json([
                'isSuccess' => true,
                'data' => array_values($result),
                'message' => "Lấy TKB thành công"
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
     * POST /api/tlk/thoi-khoa-bieu
     * Create/update schedule for a hoc phan
     * Body: { "maHocPhan": "HP001", "hocKyId": "uuid", "giangVienId": "uuid", "danhSachLop": [...] }
     */
    public function xepThoiKhoaBieu(Request $request)
    {
        try {
            $maHocPhan = $request->input('maHocPhan');
            $hocKyId = $request->input('hocKyId');
            $giangVienId = $request->input('giangVienId');
            $danhSachLop = $request->input('danhSachLop', []);

            if (!$maHocPhan) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Mã học phần không được rỗng'
                ], 400);
            }

            if (!$hocKyId) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Học kỳ ID không được rỗng'
                ], 400);
            }

            if (empty($danhSachLop)) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Danh sách lớp không được rỗng'
                ], 400);
            }

            $tlk = $this->getTLKFromToken();

            if (!$tlk) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy thông tin trợ lý khoa'
                ], 404);
            }

            // Find the HocPhan
            $hocPhan = HocPhan::whereHas('monHoc', function ($q) use ($maHocPhan) {
                $q->where('ma_mon', $maHocPhan);
            })
                ->where('id_hoc_ky', $hocKyId)
                ->first();

            if (!$hocPhan) {
                return response()->json([
                    'isSuccess' => false,
                    'data' => null,
                    'message' => 'Không tìm thấy học phần'
                ], 404);
            }

            $createdCount = 0;


            DB::transaction(function () use ($hocPhan, $giangVienId, $danhSachLop, &$createdCount) {
                foreach ($danhSachLop as $lop) {
                    // Accept both maLop (old format) and tenLop (new frontend format)
                    $maLop = $lop['maLop'] ?? $lop['tenLop'] ?? null;
                    $siSoToiDa = $lop['siSoToiDa'] ?? 50;

                    // Support nested lichHoc array (old format) OR direct TKB fields (new frontend format)
                    $lichHocs = $lop['lichHoc'] ?? [];

                    if (!$maLop)
                        continue;

                    // Extract dates from lop if provided (frontend sends ngayBatDau/ngayKetThuc)
                    $ngayBatDau = null;
                    $ngayKetThuc = null;

                    if (!empty($lop['ngayBatDau'])) {
                        $ngayBatDau = is_string($lop['ngayBatDau'])
                            ? date('Y-m-d', strtotime($lop['ngayBatDau']))
                            : date('Y-m-d', strtotime($lop['ngayBatDau']));
                    }
                    if (!empty($lop['ngayKetThuc'])) {
                        $ngayKetThuc = is_string($lop['ngayKetThuc'])
                            ? date('Y-m-d', strtotime($lop['ngayKetThuc']))
                            : date('Y-m-d', strtotime($lop['ngayKetThuc']));
                    }

                    // Create LopHocPhan
                    $lhp = LopHocPhan::create([
                        'id' => Str::uuid()->toString(),
                        'ma_lop' => $maLop,
                        'hoc_phan_id' => $hocPhan->id,
                        'giang_vien_id' => $giangVienId,
                        'so_luong_toi_da' => $siSoToiDa,
                        'so_luong_hien_tai' => 0,
                        'phong_mac_dinh_id' => $lop['phongHocId'] ?? null,
                        'ngay_bat_dau' => $ngayBatDau,
                        'ngay_ket_thuc' => $ngayKetThuc,
                    ]);

                    // Check if TKB info is directly on lop object (new frontend format)
                    if (!empty($lop['tietBatDau']) && !empty($lop['tietKetThuc']) && !empty($lop['thuTrongTuan'])) {
                        LichHocDinhKy::create([
                            'id' => Str::uuid()->toString(),
                            'lop_hoc_phan_id' => $lhp->id,
                            'thu' => $lop['thuTrongTuan'],
                            'tiet_bat_dau' => $lop['tietBatDau'],
                            'tiet_ket_thuc' => $lop['tietKetThuc'],
                            'phong_id' => $lop['phongHocId'] ?? null,
                        ]);
                    }

                    // Also create LichHocDinhKy from lichHoc array if provided (old format)
                    foreach ($lichHocs as $lich) {
                        LichHocDinhKy::create([
                            'id' => Str::uuid()->toString(),
                            'lop_hoc_phan_id' => $lhp->id,
                            'thu' => $lich['thu'] ?? 2,
                            'tiet_bat_dau' => $lich['tietBatDau'] ?? 1,
                            'tiet_ket_thuc' => $lich['tietKetThuc'] ?? 3,
                            'phong_id' => $lich['phongId'] ?? null,
                        ]);
                    }

                    $createdCount++;
                }
            });

            return response()->json([
                'isSuccess' => true,
                'data' => ['createdCount' => $createdCount],
                'message' => "Xếp thời khóa biểu thành công ({$createdCount} lớp)"
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
