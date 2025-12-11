<?php

namespace App\Infrastructure\Auth\Persistence\Repositories;

use App\Domain\Auth\Repositories\IAuthRepository;
use App\Domain\Auth\Entities\UserEntity;
use App\Infrastructure\Auth\Persistence\Models\TaiKhoan;
use App\Infrastructure\Auth\Persistence\Models\User;
use Illuminate\Support\Facades\DB;

class EloquentAuthRepository implements IAuthRepository
{
    public function findAccountByUsername(string $username): ?object
    {
        return TaiKhoan::where('ten_dang_nhap', $username)->first();
    }

    public function getUserByAccountId(string|int $accountId): ?UserEntity
    {
        $userModel = User::where('tai_khoan_id', $accountId)->first();

        if (!$userModel) {
            return null;
        }

        // Fetch account to get type
        $account = $userModel->taiKhoan;

        // Basic mapping
        $mssv = null;
        $lop = null;
        $nganh = null;

        // If student, we might need to query 'sinh_vien' table which extends 'users'
        if ($account && $account->loai_tai_khoan === 'sinh_vien') {
            // Assuming there is a SinhVien model or table joined by id
            // For now using DB query for simplicity or assuming User model has relation if defined
            // defined in schema: sinh_vien (extends users)
            $svData = DB::table('sinh_vien')->where('id', $userModel->id)->first();
            if ($svData) {
                $mssv = $svData->ma_so_sinh_vien;
                $lop = $svData->lop;
                // Fetch nganh?? Assuming basic scalar or need more queries.
                // Keeping it simple as per creating structure.
            }
        }

        return new UserEntity(
            id: (string) $userModel->id,
            hoTen: $userModel->ho_ten,
            loaiTaiKhoan: $account->loai_tai_khoan ?? 'unknown',
            maNhanVien: $userModel->ma_nhan_vien,
            mssv: $mssv,
            lop: $lop,
            nganh: $nganh
        );
    }
}
