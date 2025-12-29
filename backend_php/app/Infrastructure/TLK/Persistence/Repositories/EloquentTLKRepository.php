<?php

namespace App\Infrastructure\TLK\Persistence\Repositories;

use App\Domain\TLK\Repositories\TLKRepositoryInterface;
use App\Infrastructure\TLK\Persistence\Models\DeXuatHocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\MonHoc;
use App\Infrastructure\SinhVien\Persistence\Models\HocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\LopHocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\LichHocDinhKy;
use App\Infrastructure\SinhVien\Persistence\Models\Phong;
use App\Infrastructure\SinhVien\Persistence\Models\GhiDanhHocPhan;
use App\Infrastructure\GiangVien\Persistence\Models\GiangVien;
use App\Infrastructure\Common\Persistence\Models\HocKy;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Eloquent implementation của TLKRepositoryInterface
 */
class EloquentTLKRepository implements TLKRepositoryInterface
{
    public function getMonHocByKhoa(string $khoaId): Collection
    {
        return MonHoc::where('khoa_id', $khoaId)->get();
    }

    public function getGiangVienByKhoa(string $khoaId): Collection
    {
        return GiangVien::with('user')->where('khoa_id', $khoaId)->get();
    }

    public function getPhongHocByKhoa(string $khoaId): Collection
    {
        return Phong::with('coSo')->where('khoa_id', $khoaId)->orderBy('ma_phong', 'asc')->get();
    }

    public function getAvailablePhongHocByKhoa(string $khoaId): Collection
    {
        return Phong::with('coSo')->where('khoa_id', $khoaId)
            ->where(function ($q) {
                $q->whereNull('da_dc_su_dung')
                    ->orWhere('da_dc_su_dung', false);
            })
            ->get();
    }

    public function getDeXuatByKhoa(string $khoaId, ?string $hocKyId = null): Collection
    {
        $query = DeXuatHocPhan::with(['monHoc', 'giangVienDeXuat.user', 'nguoiTao'])
            ->where('khoa_id', $khoaId);

        if ($hocKyId) {
            $query->where('hoc_ky_id', $hocKyId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function findMonHocByKhoa(string $monHocId, string $khoaId): ?object
    {
        return MonHoc::where('id', $monHocId)->where('khoa_id', $khoaId)->first();
    }

    public function existsDeXuat(string $monHocId, string $hocKyId, string $khoaId): bool
    {
        return DeXuatHocPhan::where('mon_hoc_id', $monHocId)
            ->where('hoc_ky_id', $hocKyId)
            ->where('khoa_id', $khoaId)
            ->exists();
    }

    public function createDeXuat(array $data): object
    {
        return DeXuatHocPhan::create([
            'id' => Str::uuid()->toString(),
            'mon_hoc_id' => $data['mon_hoc_id'],
            'hoc_ky_id' => $data['hoc_ky_id'],
            'khoa_id' => $data['khoa_id'],
            'nguoi_tao_id' => $data['nguoi_tao_id'],
            'giang_vien_de_xuat' => $data['giang_vien_de_xuat'] ?? null,
            'trang_thai' => 'cho_duyet',
            'cap_duyet_hien_tai' => 'truong_khoa',
            'so_lop_du_kien' => $data['so_lop_du_kien'] ?? 1,
        ]);
    }

    public function getCurrentHocKy(): ?object
    {
        return HocKy::where('trang_thai_hien_tai', true)->first();
    }

    public function getHocPhanForSemester(string $hocKyId, string $khoaId): Collection
    {
        return HocPhan::with('monHoc')
            ->where('id_hoc_ky', $hocKyId)
            ->whereHas('monHoc', function ($q) use ($khoaId) {
                $q->where('khoa_id', $khoaId);
            })
            ->get();
    }

    public function findHocPhanByMaMon(string $maMon, string $hocKyId): ?object
    {
        return HocPhan::whereHas('monHoc', function ($q) use ($maMon) {
            $q->where('ma_mon', $maMon);
        })->where('id_hoc_ky', $hocKyId)->first();
    }

    public function createLopHocPhan(array $data): object
    {
        return LopHocPhan::create([
            'id' => Str::uuid()->toString(),
            'ma_lop' => $data['ma_lop'],
            'hoc_phan_id' => $data['hoc_phan_id'],
            'giang_vien_id' => $data['giang_vien_id'] ?? null,
            'so_luong_toi_da' => $data['so_luong_toi_da'] ?? 50,
            'so_luong_hien_tai' => 0,
            'phong_mac_dinh_id' => $data['phong_mac_dinh_id'] ?? null,
            'ngay_bat_dau' => $data['ngay_bat_dau'] ?? null,
            'ngay_ket_thuc' => $data['ngay_ket_thuc'] ?? null,
        ]);
    }

    public function createLichHocDinhKy(array $data): object
    {
        return LichHocDinhKy::create([
            'id' => Str::uuid()->toString(),
            'lop_hoc_phan_id' => $data['lop_hoc_phan_id'],
            'thu' => $data['thu'],
            'tiet_bat_dau' => $data['tiet_bat_dau'],
            'tiet_ket_thuc' => $data['tiet_ket_thuc'],
            'phong_id' => $data['phong_id'] ?? null,
        ]);
    }
}
