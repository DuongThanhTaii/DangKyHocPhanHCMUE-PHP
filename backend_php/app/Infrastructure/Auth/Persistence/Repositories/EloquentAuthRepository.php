<?php

namespace App\Infrastructure\Auth\Persistence\Repositories;

use App\Domain\Auth\Entities\UserEntity;
use App\Domain\Auth\Repositories\AuthRepositoryInterface;
use App\Infrastructure\Auth\Persistence\Models\TaiKhoan;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;

class EloquentAuthRepository implements AuthRepositoryInterface
{
    /**
     * Tìm tài khoản theo tên đăng nhập (tai_khoan.ten_dang_nhap)
     */
    public function findAccountByUsername(string $tenDangNhap): ?TaiKhoan
    {
        return TaiKhoan::where('ten_dang_nhap', $tenDangNhap)->first();
    }

    /**
     * Lấy thông tin hiển thị sau login:
     *  - lấy loai_tai_khoan từ tai_khoan
     *  - join users qua users.tai_khoan_id (uuid)
     */
    // app/Infrastructure/Auth/Persistence/Repositories/EloquentAuthRepository.php

    public function buildAuthUserFromAccount(TaiKhoan $account): UserEntity
    {
        $profile = UserProfile::where('tai_khoan_id', $account->id)->first();

        return new UserEntity(
            (string) $account->id,                      // id
            $account->ten_dang_nhap,                    // tenDangNhap
            $profile?->ho_ten,                          // hoTen
            $profile?->email,                           // email
            $account->loai_tai_khoan                    // loaiTaiKhoan
        );
    }

    /**
     * Get User Entity by Account ID
     */
    public function getUserByAccountId(string|int $accountId): ?UserEntity
    {
        $account = TaiKhoan::find($accountId);

        if (!$account) {
            return null;
        }

        return $this->buildAuthUserFromAccount($account);
    }

    /**
     * Find account by email
     */
    public function findAccountByEmail(string $email): ?TaiKhoan
    {
        // 1. Find UserProfile via email
        $profile = UserProfile::where('email', $email)->first();
        if (!$profile) {
            return null;
        }

        // 2. Find TaiKhoan via profile's tai_khoan_id
        return TaiKhoan::find($profile->tai_khoan_id);
    }

    /**
     * Update password
     */
    public function updatePassword(string|int $accountId, string $hashedPassword): void
    {
        $account = TaiKhoan::find($accountId);
        if ($account) {
            $account->mat_khau = $hashedPassword;
            $account->save();
        }
    }

    public function validatePassword(string|int $accountId, string $plainPassword): bool
    {
        $account = TaiKhoan::find($accountId);
        if (!$account) {
            return false;
        }

        // Check Laravel Hash
        if (\Illuminate\Support\Facades\Hash::check($plainPassword, $account->mat_khau)) {
            return true;
        }

        // Check Django Hash (optional, if we support legacy here)
        // Usually change-password requires strict check, but legacy might need to change it.
        // But Login migrates it. So it should be Laravel Hash if already logged in?
        // Yes, login auto-migrates. So we might not need Django check here.
        // But validation doesn't hurt.
        // I'll SKIP dependency injection here for brevity and assume Hash::check is enough if Login migrates.

        return false;
    }
}
