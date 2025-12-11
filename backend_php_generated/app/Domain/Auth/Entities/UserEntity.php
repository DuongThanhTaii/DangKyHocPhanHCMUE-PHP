<?php

namespace App\Domain\Auth\Entities;

class UserEntity
{
    public function __construct(
        public string $id,
        public string $hoTen,
        public string $loaiTaiKhoan,
        public ?string $maNhanVien = null,
        public ?string $mssv = null,
        public ?string $lop = null,
        public ?string $nganh = null
    ) {}

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'ho_ten' => $this->hoTen,
            'loai_tai_khoan' => $this->loaiTaiKhoan,
            'ma_nhan_vien' => $this->maNhanVien,
        ];

        if ($this->loaiTaiKhoan === 'sinh_vien') {
            $data['mssv'] = $this->mssv;
            $data['lop'] = $this->lop;
            $data['nganh'] = $this->nganh;
        }

        return $data;
    }
}
