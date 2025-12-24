<?php

namespace App\Infrastructure\SinhVien\Persistence\Mappers;

use App\Domain\SinhVien\Entities\SinhVienEntity;
use App\Domain\SinhVien\Entities\LopHocPhanEntity;
use App\Domain\SinhVien\Entities\DangKyHocPhanEntity;
use App\Domain\SinhVien\ValueObjects\TrangThaiDangKy;
use App\Infrastructure\SinhVien\Persistence\Models\SinhVien;
use App\Infrastructure\SinhVien\Persistence\Models\LopHocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\DangKyHocPhan;
use DateTimeImmutable;

/**
 * Mapper for SinhVien module
 * 
 * Converts between Eloquent Models and Domain Entities
 */
class SinhVienMapper
{
    /**
     * Convert SinhVien model to SinhVienEntity
     */
    public static function toSinhVienEntity(SinhVien $model): SinhVienEntity
    {
        $user = $model->user;
        
        return new SinhVienEntity(
            id: $model->id,
            maSoSinhVien: $model->ma_so_sinh_vien,
            lop: $model->lop,
            khoaId: $model->khoa_id,
            khoaHoc: $model->khoa_hoc,
            nganhId: $model->nganh_id,
            ngayNhapHoc: $model->ngay_nhap_hoc 
                ? new DateTimeImmutable($model->ngay_nhap_hoc) 
                : null,
            hoTen: $user?->ho_ten,
            email: $user?->email,
        );
    }

    /**
     * Convert LopHocPhan model to LopHocPhanEntity
     */
    public static function toLopHocPhanEntity(LopHocPhan $model): LopHocPhanEntity
    {
        $hocPhan = $model->hocPhan;
        $giangVien = $model->giangVien;
        $monHoc = $hocPhan?->monHoc;

        return new LopHocPhanEntity(
            id: $model->id,
            hocPhanId: $model->hoc_phan_id,
            maLop: $model->ma_lop,
            giangVienId: $model->giang_vien_id,
            soLuongToiDa: $model->so_luong_toi_da ?? 50,
            soLuongHienTai: $model->so_luong_hien_tai ?? 0,
            phongMacDinhId: $model->phong_mac_dinh_id,
            ngayBatDau: $model->ngay_bat_dau 
                ? new DateTimeImmutable($model->ngay_bat_dau) 
                : null,
            ngayKetThuc: $model->ngay_ket_thuc 
                ? new DateTimeImmutable($model->ngay_ket_thuc) 
                : null,
            tenMonHoc: $monHoc?->ten_mon_hoc,
            maMonHoc: $monHoc?->ma_mon_hoc,
            soTinChi: $monHoc?->so_tin_chi,
            tenGiangVien: $giangVien?->ho_ten,
        );
    }

    /**
     * Convert DangKyHocPhan model to DangKyHocPhanEntity
     */
    public static function toDangKyHocPhanEntity(DangKyHocPhan $model): DangKyHocPhanEntity
    {
        $lopHocPhan = $model->lopHocPhan;
        $hocPhan = $lopHocPhan?->hocPhan;
        $monHoc = $hocPhan?->monHoc;

        return new DangKyHocPhanEntity(
            id: $model->id,
            sinhVienId: $model->sinh_vien_id,
            lopHocPhanId: $model->lop_hoc_phan_id,
            trangThai: new TrangThaiDangKy($model->trang_thai ?? 'da_dang_ky'),
            ngayDangKy: $model->ngay_dang_ky 
                ? new DateTimeImmutable($model->ngay_dang_ky) 
                : null,
            coXungDot: $model->co_xung_dot ?? false,
            maLop: $lopHocPhan?->ma_lop,
            tenMonHoc: $monHoc?->ten_mon_hoc,
            soTinChi: $monHoc?->so_tin_chi,
        );
    }

    /**
     * Convert multiple DangKyHocPhan models to entities
     * @return DangKyHocPhanEntity[]
     */
    public static function toDangKyHocPhanEntities(iterable $models): array
    {
        $entities = [];
        foreach ($models as $model) {
            $entities[] = self::toDangKyHocPhanEntity($model);
        }
        return $entities;
    }

    /**
     * Convert multiple LopHocPhan models to entities
     * @return LopHocPhanEntity[]
     */
    public static function toLopHocPhanEntities(iterable $models): array
    {
        $entities = [];
        foreach ($models as $model) {
            $entities[] = self::toLopHocPhanEntity($model);
        }
        return $entities;
    }
}
