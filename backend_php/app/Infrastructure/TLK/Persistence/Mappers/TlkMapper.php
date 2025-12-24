<?php

namespace App\Infrastructure\TLK\Persistence\Mappers;

use App\Domain\TLK\Entities\DeXuatEntity;
use App\Domain\TLK\Entities\PhongHocEntity;
use DateTimeImmutable;
use Illuminate\Support\Collection;

/**
 * Mapper for TLK Module
 * 
 * Converts Eloquent Models → Domain Entities
 */
class TlkMapper
{
    /**
     * Convert DeXuat Model to Entity
     */
    public static function toDeXuatEntity(object $model): DeXuatEntity
    {
        return new DeXuatEntity(
            id: $model->id,
            monHocId: $model->mon_hoc_id,
            hocKyId: $model->hoc_ky_id,
            khoaId: $model->khoa_id,
            createdById: $model->nguoi_de_xuat_id ?? null,
            trangThai: $model->trang_thai ?? DeXuatEntity::STATUS_CHO_DUYET_TK,
            soLopDeXuat: $model->so_lop ?? 1,
            soSinhVienDuKien: $model->so_sinh_vien_du_kien ?? null,
            ghiChu: $model->ghi_chu ?? null,
            lyDoTuChoi: $model->ly_do_tu_choi ?? null,
            ngayTao: $model->created_at 
                ? new DateTimeImmutable($model->created_at) 
                : null,
            tenMonHoc: $model->monHoc?->ten_mon ?? null,
            maMonHoc: $model->monHoc?->ma_mon ?? null,
        );
    }

    /**
     * Convert Collection of DeXuat Models to Entities
     */
    public static function toDeXuatEntities(Collection $models): array
    {
        return $models->map(fn($m) => self::toDeXuatEntity($m))->toArray();
    }

    /**
     * Convert PhongHoc Model to Entity
     */
    public static function toPhongHocEntity(object $model): PhongHocEntity
    {
        return new PhongHocEntity(
            id: $model->id,
            maPhong: $model->ma_phong ?? '',
            tenPhong: $model->ten_phong ?? $model->ma_phong ?? '',
            khoaId: $model->khoa_id ?? null,
            sucChua: $model->suc_chua ?? 50,
            loaiPhong: $model->loai_phong ?? null,
            toaNha: $model->toa_nha ?? null,
            isAvailable: !$model->khoa_id, // Available if not assigned to a department
        );
    }

    /**
     * Convert Collection of PhongHoc Models to Entities
     */
    public static function toPhongHocEntities(Collection $models): array
    {
        return $models->map(fn($m) => self::toPhongHocEntity($m))->toArray();
    }

    /**
     * Format DeXuatEntity for API response (FE-compatible format)
     */
    public static function formatDeXuatForApi(DeXuatEntity $entity, ?object $model = null): array
    {
        $data = [
            'id' => $entity->id,
            'monHocId' => $entity->monHocId,
            'hocKyId' => $entity->hocKyId,
            'khoaId' => $entity->khoaId,
            'trangThai' => $entity->trangThai,
            'trangThaiLabel' => $entity->getStatusLabel(),
            'soLopDeXuat' => $entity->soLopDeXuat,
            'soSinhVienDuKien' => $entity->soSinhVienDuKien,
            'ghiChu' => $entity->ghiChu,
            'lyDoTuChoi' => $entity->lyDoTuChoi,
            'ngayTao' => $entity->ngayTao?->format('c'),
            'tenMonHoc' => $entity->tenMonHoc,
            'maMonHoc' => $entity->maMonHoc,
            'isPendingTK' => $entity->isPendingTK(),
            'isPendingPDT' => $entity->isPendingPDT(),
            'isApproved' => $entity->isApproved(),
            'canEdit' => $entity->canEdit(),
        ];

        // Add model-specific fields
        if ($model) {
            $data['nguoiDeXuat'] = $model->nguoiDeXuat?->ho_ten ?? '';
            $data['tenKhoa'] = $model->khoa?->ten_khoa ?? '';
            $data['tenHocKy'] = $model->hocKy?->ten_hoc_ky ?? '';
        }

        return $data;
    }

    /**
     * Format PhongHocEntity for API response (FE-compatible format)
     */
    public static function formatPhongHocForApi(PhongHocEntity $entity, ?object $model = null): array
    {
        return [
            'id' => $entity->id,
            'maPhong' => $entity->maPhong,
            'tenPhong' => $entity->tenPhong,
            'sucChua' => $entity->sucChua,
            'loaiPhong' => $entity->loaiPhong,
            'toaNha' => $entity->toaNha,
            'isAvailable' => $entity->isAvailable(),
            'isLab' => $entity->isLab(),
            'displayName' => $entity->getDisplayName(),
            'khoaId' => $entity->khoaId,
            'tenKhoa' => $model?->khoa?->ten_khoa ?? '',
        ];
    }
}
