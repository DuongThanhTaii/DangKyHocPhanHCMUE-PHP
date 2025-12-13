<?php

namespace App\Infrastructure\Pdt\Persistence\Repositories;

use App\Infrastructure\Pdt\Persistence\Models\SinhVien;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use App\Infrastructure\Auth\Persistence\Models\TaiKhoan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentRepository
{
    private function clean(?string $v): ?string
    {
        if ($v === null)
            return null;
        $v = trim($v);
        return $v === '' ? null : $v;
    }

    public function paginate($limit = 10, $search = null)
    {
        $query = SinhVien::with(['user', 'khoa', 'nganh']);

        if ($search) {
            $search = trim($search);

            $query->where(function ($q) use ($search) {
                $q->where('ma_so_sinh_vien', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('ho_ten', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        return $query->paginate($limit);
    }

    public function findById($id)
    {
        return SinhVien::with('user')->find($id);
    }

    public function findByStudentCode($code)
    {
        return SinhVien::where('ma_so_sinh_vien', $code)->first();
    }

    public function create(array $data)
    {
        // DB sinh_vien.khoa_id NOT NULL => bắt buộc
        if (empty($data['khoa_id'])) {
            throw ValidationException::withMessages(['khoa_id' => 'khoa_id is required']);
        }

        return DB::transaction(function () use ($data) {
            $mssv = $this->clean($data['ma_so_sinh_vien'] ?? null);
            $hoTen = $this->clean($data['ho_ten'] ?? null);
            $email = $this->clean($data['email'] ?? null);

            if (!$mssv) {
                throw ValidationException::withMessages(['ma_so_sinh_vien' => 'ma_so_sinh_vien is required']);
            }
            if (!$hoTen) {
                throw ValidationException::withMessages(['ho_ten' => 'ho_ten is required']);
            }
            if (!$email) {
                throw ValidationException::withMessages(['email' => 'email is required']);
            }

            // 1) TaiKhoan
            $taiKhoan = new TaiKhoan();
            $taiKhoan->id = Str::uuid()->toString();
            $taiKhoan->ten_dang_nhap = $mssv;
            $taiKhoan->mat_khau = Hash::make($data['password'] ?? $mssv);
            $taiKhoan->loai_tai_khoan = 'sinh_vien';
            $taiKhoan->trang_thai_hoat_dong = true;
            $taiKhoan->ngay_tao = now();
            $taiKhoan->updated_at = now();
            $taiKhoan->save();

            // 2) UserProfile (users)
            $user = new UserProfile();
            $user->id = Str::uuid()->toString();
            $user->ma_nhan_vien = null; // schema cho null OK
            $user->ho_ten = $hoTen;
            $user->email = $email; // đã trim để không fail CHECK regex
            $user->tai_khoan_id = $taiKhoan->id;
            $user->created_at = now();
            $user->updated_at = now();
            $user->save();

            // 3) SinhVien
            $sinhVien = new SinhVien();
            $sinhVien->id = $user->id;
            $sinhVien->ma_so_sinh_vien = $mssv;
            $sinhVien->lop = $this->clean($data['lop'] ?? null);
            $sinhVien->khoa_id = $data['khoa_id']; // ✅ KHÔNG cho null
            $sinhVien->khoa_hoc = $this->clean($data['khoa_hoc'] ?? null);
            $sinhVien->ngay_nhap_hoc = $data['ngay_nhap_hoc'] ?? null;
            $sinhVien->nganh_id = $data['nganh_id'] ?? null;
            $sinhVien->save();

            return $sinhVien;
        });
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $sinhVien = SinhVien::findOrFail($id);
            $user = UserProfile::findOrFail($id);

            // --- Update SinhVien ---
            if (array_key_exists('ma_so_sinh_vien', $data)) {
                $sinhVien->ma_so_sinh_vien = $this->clean($data['ma_so_sinh_vien']);
            }
            if (array_key_exists('lop', $data)) {
                $sinhVien->lop = $this->clean($data['lop']);
            }
            if (array_key_exists('khoa_id', $data)) {
                if (empty($data['khoa_id'])) {
                    throw ValidationException::withMessages(['khoa_id' => 'khoa_id cannot be null']);
                }
                $sinhVien->khoa_id = $data['khoa_id']; // ✅ NOT NULL
            }
            if (array_key_exists('khoa_hoc', $data)) {
                $sinhVien->khoa_hoc = $this->clean($data['khoa_hoc']);
            }
            if (array_key_exists('ngay_nhap_hoc', $data)) {
                $sinhVien->ngay_nhap_hoc = $data['ngay_nhap_hoc'];
            }
            if (array_key_exists('nganh_id', $data)) {
                $sinhVien->nganh_id = $data['nganh_id'];
            }

            $sinhVien->save();

            // --- Update UserProfile ---
            if (array_key_exists('ho_ten', $data)) {
                $user->ho_ten = $this->clean($data['ho_ten']);
            }
            if (array_key_exists('email', $data)) {
                $user->email = $this->clean($data['email']); // ✅ trim để không fail CHECK
            }

            $user->save();

            return $sinhVien;
        });
    }

    public function delete($id)
    {
        return DB::transaction(function () use ($id) {
            $sinhVien = SinhVien::findOrFail($id);

            // UserProfile might not exist for some students
            $user = UserProfile::find($id);
            $taiKhoanId = $user?->tai_khoan_id;

            $sinhVien->delete();

            if ($user) {
                $user->delete();
            }

            if ($taiKhoanId) {
                TaiKhoan::destroy($taiKhoanId);
            }

            return true;
        });
    }
}
