<?php

namespace App\Application\Pdt\DTOs;

/**
 * DTO cho việc tạo Giảng viên mới
 */
class CreateGiangVienDTO
{
    public function __construct(
        public readonly string $tenDangNhap,
        public readonly string $hoTen,
        public readonly string $matKhau = 'password123',
        public readonly ?string $khoaId = null,
        public readonly ?string $trinhDo = null,
        public readonly ?string $chuyenMon = null,
        public readonly int $kinhNghiemGiangDay = 0,
        public readonly ?string $email = null,
    ) {
    }

    public static function fromRequest(array $data): self
    {
        $tenDangNhap = $data['ten_dang_nhap'] ?? $data['tenDangNhap'] ?? $data['maGiangVien'] ?? $data['ma_giang_vien'] ?? null;
        $hoTen = $data['ho_ten'] ?? $data['hoTen'] ?? null;

        if (!$tenDangNhap || !$hoTen) {
            throw new \InvalidArgumentException('Thiếu thông tin bắt buộc (ten_dang_nhap/tên đăng nhập, ho_ten/họ tên)');
        }

        return new self(
            tenDangNhap: $tenDangNhap,
            hoTen: $hoTen,
            matKhau: $data['mat_khau'] ?? $data['matKhau'] ?? $data['password'] ?? 'password123',
            khoaId: $data['khoa_id'] ?? $data['khoaId'] ?? null,
            trinhDo: $data['trinh_do'] ?? $data['trinhDo'] ?? null,
            chuyenMon: $data['chuyen_mon'] ?? $data['chuyenMon'] ?? null,
            kinhNghiemGiangDay: (int) ($data['kinh_nghiem_giang_day'] ?? $data['kinhNghiemGiangDay'] ?? 0),
            email: $data['email'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'ten_dang_nhap' => $this->tenDangNhap,
            'ho_ten' => $this->hoTen,
            'mat_khau' => $this->matKhau,
            'khoa_id' => $this->khoaId,
            'trinh_do' => $this->trinhDo,
            'chuyen_mon' => $this->chuyenMon,
            'kinh_nghiem_giang_day' => $this->kinhNghiemGiangDay,
            'email' => $this->email ?? $this->tenDangNhap . '@gv.edu.vn',
        ];
    }
}
