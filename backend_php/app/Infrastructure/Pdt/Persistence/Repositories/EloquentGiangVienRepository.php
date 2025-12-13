<?php

namespace App\Infrastructure\Pdt\Persistence\Repositories;

use App\Domain\Pdt\Repositories\GiangVienRepositoryInterface;
use App\Infrastructure\GiangVien\Persistence\Models\GiangVien;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use App\Infrastructure\Auth\Persistence\Models\TaiKhoan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Eloquent implementation của GiangVienRepositoryInterface
 */
class EloquentGiangVienRepository implements GiangVienRepositoryInterface
{
    /**
     * Lấy danh sách giảng viên có phân trang
     */
    public function getAll(int $page = 1, int $pageSize = 10000): Collection
    {
        return GiangVien::with(['user', 'khoa'])
            ->skip(($page - 1) * $pageSize)
            ->take($pageSize)
            ->get();
    }

    /**
     * Tìm giảng viên theo ID
     */
    public function findById(string $id): ?object
    {
        return GiangVien::with('user')->find($id);
    }

    /**
     * Tạo giảng viên mới (bao gồm TaiKhoan, UserProfile, GiangVien)
     */
    public function create(array $data): object
    {
        // Create TaiKhoan
        $taiKhoan = TaiKhoan::create([
            'id' => Str::uuid()->toString(),
            'ten_dang_nhap' => $data['ten_dang_nhap'],
            'mat_khau' => Hash::make($data['mat_khau'] ?? 'password123'),
            'loai_tai_khoan' => 'giang_vien',
            'trang_thai_hoat_dong' => true,
        ]);

        // Create UserProfile
        $userProfile = UserProfile::create([
            'id' => Str::uuid()->toString(),
            'tai_khoan_id' => $taiKhoan->id,
            'ho_ten' => $data['ho_ten'],
            'email' => $data['email'] ?? $data['ten_dang_nhap'] . '@gv.edu.vn',
            'ma_nhan_vien' => $data['ten_dang_nhap'],
        ]);

        // Create GiangVien
        $giangVien = GiangVien::create([
            'id' => $userProfile->id,
            'ma_giang_vien' => $data['ten_dang_nhap'],
            'khoa_id' => $data['khoa_id'] ?? null,
            'trinh_do' => $data['trinh_do'] ?? null,
            'chuyen_mon' => $data['chuyen_mon'] ?? null,
            'kinh_nghiem_giang_day' => (int) ($data['kinh_nghiem_giang_day'] ?? 0),
        ]);

        return $giangVien;
    }

    /**
     * Cập nhật giảng viên
     */
    public function update(string $id, array $data): ?object
    {
        $giangVien = GiangVien::with('user')->find($id);
        if (!$giangVien) {
            return null;
        }

        // Update GiangVien
        if (isset($data['ma_giang_vien'])) {
            $giangVien->ma_giang_vien = $data['ma_giang_vien'];
        }
        if (isset($data['khoa_id'])) {
            $giangVien->khoa_id = $data['khoa_id'];
        }
        if (isset($data['hoc_vi'])) {
            $giangVien->hoc_vi = $data['hoc_vi'];
        }
        $giangVien->save();

        // Update UserProfile
        $user = $giangVien->user;
        if ($user) {
            if (isset($data['ho_ten'])) {
                $user->ho_ten = $data['ho_ten'];
            }
            if (isset($data['email'])) {
                $user->email = $data['email'];
            }
            $user->save();
        }

        return $giangVien;
    }

    /**
     * Xóa giảng viên
     */
    public function delete(string $id): bool
    {
        $giangVien = GiangVien::find($id);
        if (!$giangVien) {
            return false;
        }
        $giangVien->delete();
        return true;
    }
}
