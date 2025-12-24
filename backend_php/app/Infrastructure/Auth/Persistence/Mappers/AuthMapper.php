<?php

namespace App\Infrastructure\Auth\Persistence\Mappers;

use App\Domain\Auth\Entities\TaiKhoanEntity;
use App\Domain\Auth\Entities\UserProfileEntity;
use App\Domain\Auth\ValueObjects\Email;
use App\Domain\Auth\ValueObjects\Username;
use App\Infrastructure\Auth\Persistence\Models\TaiKhoan;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use DateTimeImmutable;

/**
 * Mapper for converting between Auth Eloquent Models and Domain Entities
 * 
 * This class is responsible for:
 * - Converting Eloquent Models → Domain Entities (for use in business logic)
 * - Converting Domain Entities → Eloquent Models (for persistence)
 */
class AuthMapper
{
    /**
     * Static method: Convert any model object to TaiKhoanEntity
     */
    public static function toTaiKhoanEntity(object $model): TaiKhoanEntity
    {
        $usernameStr = $model->username ?? $model->ten_dang_nhap ?? '';
        $role = $model->role ?? $model->loai_tai_khoan ?? '';
        $isActive = $model->status === 'active' 
            || ($model->trang_thai_hoat_dong ?? false);
        
        return new TaiKhoanEntity(
            id: (string) $model->id,
            tenDangNhap: new Username($usernameStr),
            loaiTaiKhoan: $role,
            trangThaiHoatDong: $isActive,
            ngayTao: isset($model->last_login_at) || isset($model->ngay_tao)
                ? new DateTimeImmutable($model->last_login_at ?? $model->ngay_tao) 
                : null,
        );
    }

    /**
     * Static method: Convert any model object to UserProfileEntity
     */
    public static function toUserProfileEntity(object $model): UserProfileEntity
    {
        $email = null;
        $emailString = $model->email ?? null;
        if ($emailString) {
            try {
                $email = new Email($emailString);
            } catch (\InvalidArgumentException) {
                $email = null;
            }
        }

        return new UserProfileEntity(
            id: (string) $model->id,
            taiKhoanId: (string) ($model->tai_khoan_id ?? ''),
            maNhanVien: $model->ma_nhan_vien ?? null,
            hoTen: $model->ho_ten ?? null,
            email: $email,
        );
    }

    /**
     * Format TaiKhoanEntity for API response (FE-compatible format)
     */
    public static function formatTaiKhoanForApi(TaiKhoanEntity $entity): array
    {
        return [
            'id' => $entity->id,
            'username' => $entity->username,
            'role' => $entity->role,
            'roleLabel' => $entity->getRoleLabel(),
            'status' => $entity->status,
            'isActive' => $entity->isActive(),
            'lastLoginAt' => $entity->lastLoginAt?->format('c'),
        ];
    }

    /**
     * Format UserProfileEntity for API response (FE-compatible format)
     */
    public static function formatUserProfileForApi(UserProfileEntity $entity): array
    {
        return [
            'id' => $entity->id,
            'taiKhoanId' => $entity->taiKhoanId,
            'hoTen' => $entity->hoTen,
            'email' => $entity->email,
            'sdt' => $entity->sdt,
            'avatar' => $entity->avatar,
            'displayName' => $entity->getDisplayName(),
        ];
    }

    /**
     * Legacy instance method - kept for backward compatibility
     */
    public function toTaiKhoanEntityFromEloquent(TaiKhoan $model): TaiKhoanEntity
    {
        return self::toTaiKhoanEntity($model);
    }

    /**
     * Legacy instance method - kept for backward compatibility
     */
    public function toUserProfileEntityFromEloquent(UserProfile $model): UserProfileEntity
    {
        return self::toUserProfileEntity($model);
    }

    /**
     * Convert TaiKhoanEntity to array for Eloquent Model creation/update
     * Note: Password is handled separately for security
     */
    public function fromTaiKhoanEntity(TaiKhoanEntity $entity): array
    {
        return [
            'id' => $entity->id,
            'ten_dang_nhap' => $entity->username,
            'loai_tai_khoan' => $entity->role,
            'trang_thai_hoat_dong' => $entity->isActive(),
        ];
    }

    /**
     * Convert UserProfileEntity to array for Eloquent Model creation/update
     */
    public function fromUserProfileEntity(UserProfileEntity $entity): array
    {
        return [
            'id' => $entity->id,
            'tai_khoan_id' => $entity->taiKhoanId,
            'ho_ten' => $entity->hoTen,
            'email' => $entity->email,
        ];
    }
}

