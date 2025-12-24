<?php

namespace App\Infrastructure\Pdt\Persistence\Mappers;

use App\Domain\Pdt\Entities\HocKyEntity;
use App\Domain\Pdt\Entities\DotDangKyEntity;
use App\Domain\Pdt\Entities\MonHocEntity;
use App\Domain\Pdt\Entities\KyPhaseEntity;
use DateTimeImmutable;
use Illuminate\Support\Collection;

/**
 * Mapper for PDT Module
 * 
 * Converts Eloquent Models → Domain Entities
 */
class PdtMapper
{
    /**
     * Convert DotDangKy Model to Entity
     */
    public static function toDotDangKyEntity(object $model): DotDangKyEntity
    {
        return new DotDangKyEntity(
            id: $model->id,
            hocKyId: $model->hoc_ky_id,
            tenDot: $model->loai_dot ?? 'Đợt đăng ký',
            ngayBatDau: $model->thoi_gian_bat_dau 
                ? new DateTimeImmutable($model->thoi_gian_bat_dau) 
                : null,
            ngayKetThuc: $model->thoi_gian_ket_thuc 
                ? new DateTimeImmutable($model->thoi_gian_ket_thuc) 
                : null,
            moTa: $model->loai_dot,
            daKetThuc: !$model->isActive(),
        );
    }

    /**
     * Convert Collection of DotDangKy Models to Entities
     */
    public static function toDotDangKyEntities(Collection $models): array
    {
        return $models->map(fn($m) => self::toDotDangKyEntity($m))->toArray();
    }

    /**
     * Convert MonHoc Model to Entity
     */
    public static function toMonHocEntity(object $model): MonHocEntity
    {
        return new MonHocEntity(
            id: $model->id,
            maMonHoc: $model->ma_mon ?? '',
            tenMonHoc: $model->ten_mon ?? '',
            soTinChi: $model->so_tin_chi ?? 0,
            moTa: null,
            khoaId: $model->khoa_id,
            coThucHanh: false, // Can be derived from loai_mon
            isActive: true,
        );
    }

    /**
     * Convert Collection of MonHoc Models to Entities
     */
    public static function toMonHocEntities(Collection $models): array
    {
        return $models->map(fn($m) => self::toMonHocEntity($m))->toArray();
    }

    /**
     * Convert KyPhase Model to Entity
     */
    public static function toKyPhaseEntity(object $model): KyPhaseEntity
    {
        return new KyPhaseEntity(
            id: $model->id,
            hocKyId: $model->hoc_ky_id,
            tenPhase: ucfirst(str_replace('_', ' ', $model->phase ?? '')),
            loaiPhase: $model->phase,
            ngayBatDau: $model->start_at 
                ? new DateTimeImmutable($model->start_at) 
                : null,
            ngayKetThuc: $model->end_at 
                ? new DateTimeImmutable($model->end_at) 
                : null,
            thuTu: 0,
        );
    }

    /**
     * Convert Collection of KyPhase Models to Entities
     */
    public static function toKyPhaseEntities(Collection $models): array
    {
        return $models->map(fn($m) => self::toKyPhaseEntity($m))->toArray();
    }

    /**
     * Format DotDangKyEntity for API response (FE-compatible format)
     */
    public static function formatDotDangKyForApi(DotDangKyEntity $entity, ?object $model = null): array
    {
        $data = [
            'id' => $entity->id,
            'hocKyId' => $entity->hocKyId,
            'tenDot' => $entity->tenDot,
            'ngayBatDau' => $entity->ngayBatDau?->format('c'),
            'ngayKetThuc' => $entity->ngayKetThuc?->format('c'),
            'isOpen' => $entity->isOpen(),
            'status' => $entity->getStatus(),
        ];

        // Add model-specific fields if available
        if ($model) {
            $data['tenHocKy'] = $model->hocKy?->ten_hoc_ky ?? '';
            $data['loaiDot'] = $model->loai_dot;
            $data['gioiHanTinChi'] = $model->gioi_han_tin_chi ?? 0;
            $data['isCheckToanTruong'] = $model->is_check_toan_truong ?? false;
            $data['khoaId'] = $model->khoa_id;
            $data['tenKhoa'] = $model->khoa?->ten_khoa ?? '';
        }

        return $data;
    }

    /**
     * Format MonHocEntity for API response (FE-compatible format)
     */
    public static function formatMonHocForApi(MonHocEntity $entity, ?object $model = null): array
    {
        $data = [
            'id' => $entity->id,
            'ma_mon' => $entity->maMonHoc,
            'ten_mon' => $entity->tenMonHoc,
            'so_tin_chi' => $entity->soTinChi,
            'khoa_id' => $entity->khoaId,
            'la_mon_chung' => false,
            'creditDisplay' => $entity->getCreditDisplay(),
        ];

        // Add model-specific fields
        if ($model) {
            $data['loai_mon'] = $model->loai_mon ?? null;
            $data['la_mon_chung'] = $model->la_mon_chung ?? false;
            $data['thu_tu_hoc'] = $model->thu_tu_hoc ?? null;
            $data['khoa'] = $model->khoa ? [
                'id' => $model->khoa->id,
                'ten_khoa' => $model->khoa->ten_khoa ?? '',
            ] : null;
        }

        return $data;
    }
}
