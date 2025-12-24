<?php

namespace App\Infrastructure\GiangVien\Persistence\Mappers;

use App\Domain\GiangVien\Entities\GiangVienEntity;
use App\Domain\GiangVien\Entities\DiemSinhVienEntity;
use App\Domain\GiangVien\Entities\TaiLieuEntity;
use DateTimeImmutable;
use Illuminate\Support\Collection;

/**
 * Mapper for GiangVien Module
 * 
 * Converts Eloquent Models → Domain Entities
 */
class GiangVienMapper
{
    /**
     * Convert GiangVien Model to Entity
     */
    public static function toGiangVienEntity(object $model): GiangVienEntity
    {
        return new GiangVienEntity(
            id: $model->id,
            maGiangVien: $model->ma_giang_vien ?? '',
            hoTen: $model->ho_ten ?? null,
            email: $model->email ?? null,
            khoaId: $model->khoa_id ?? null,
            chucDanh: $model->chuc_danh ?? null,
            hocVi: $model->hoc_vi ?? null,
            isActive: $model->is_active ?? true,
        );
    }

    /**
     * Convert Collection of GiangVien Models to Entities
     */
    public static function toGiangVienEntities(Collection $models): array
    {
        return $models->map(fn($m) => self::toGiangVienEntity($m))->toArray();
    }

    /**
     * Convert DiemSinhVien Model to Entity
     */
    public static function toDiemSinhVienEntity(object $model): DiemSinhVienEntity
    {
        return new DiemSinhVienEntity(
            id: $model->id,
            sinhVienId: $model->sinh_vien_id,
            lopHocPhanId: $model->lop_hoc_phan_id,
            diemQuaTrinh: $model->diem_qua_trinh ?? null,
            diemThucHanh: $model->diem_thuc_hanh ?? null,
            diemCuoiKy: $model->diem_cuoi_ky ?? null,
            diemTongKet: $model->diem_tong_ket ?? null,
            diemChu: $model->diem_chu ?? null,
            isLocked: $model->is_locked ?? false,
            maSoSinhVien: $model->sinhVien?->ma_so_sinh_vien ?? null,
            hoTen: $model->sinhVien?->user?->ho_ten ?? null,
        );
    }

    /**
     * Convert Collection of DiemSinhVien Models to Entities
     */
    public static function toDiemSinhVienEntities(Collection $models): array
    {
        return $models->map(fn($m) => self::toDiemSinhVienEntity($m))->toArray();
    }

    /**
     * Convert TaiLieu Model to Entity
     */
    public static function toTaiLieuEntity(object $model): TaiLieuEntity
    {
        return new TaiLieuEntity(
            id: $model->id,
            lopHocPhanId: $model->lop_hoc_phan_id,
            tenTaiLieu: $model->ten_tai_lieu ?? $model->file_name ?? '',
            moTa: $model->mo_ta ?? null,
            filePath: $model->file_path ?? null,
            fileUrl: $model->file_url ?? null,
            mimeType: $model->mime_type ?? null,
            fileSize: $model->file_size ?? null,
            uploadedAt: $model->created_at 
                ? new DateTimeImmutable($model->created_at) 
                : null,
            uploadedBy: $model->uploaded_by ?? null,
        );
    }

    /**
     * Convert Collection of TaiLieu Models to Entities
     */
    public static function toTaiLieuEntities(Collection $models): array
    {
        return $models->map(fn($m) => self::toTaiLieuEntity($m))->toArray();
    }

    /**
     * Format GiangVienEntity for API response (FE-compatible format)
     */
    public static function formatGiangVienForApi(GiangVienEntity $entity, ?object $model = null): array
    {
        $data = [
            'id' => $entity->id,
            'maGiangVien' => $entity->maGiangVien,
            'hoTen' => $entity->hoTen,
            'email' => $entity->email,
            'chucDanh' => $entity->chucDanh,
            'hocVi' => $entity->hocVi,
            'isActive' => $entity->isActive(),
            'displayName' => $entity->getDisplayName(),
            'fullTitle' => $entity->getFullTitle(),
        ];

        if ($model) {
            $data['tenKhoa'] = $model->khoa?->ten_khoa ?? '';
            $data['khoaId'] = $entity->khoaId;
        }

        return $data;
    }

    /**
     * Format DiemSinhVienEntity for API response (FE-compatible format)
     */
    public static function formatDiemForApi(DiemSinhVienEntity $entity): array
    {
        return [
            'id' => $entity->id,
            'sinhVienId' => $entity->sinhVienId,
            'maSoSinhVien' => $entity->maSoSinhVien,
            'hoTen' => $entity->hoTen,
            'diemQuaTrinh' => $entity->diemQuaTrinh,
            'diemThucHanh' => $entity->diemThucHanh,
            'diemCuoiKy' => $entity->diemCuoiKy,
            'diemTongKet' => $entity->diemTongKet,
            'diemChu' => $entity->diemChu,
            'isLocked' => $entity->isLocked,
            'isPassing' => $entity->isPassing(),
            'isComplete' => $entity->isComplete(),
            'status' => $entity->getStatus(),
            'canEdit' => $entity->canEdit(),
        ];
    }
}
