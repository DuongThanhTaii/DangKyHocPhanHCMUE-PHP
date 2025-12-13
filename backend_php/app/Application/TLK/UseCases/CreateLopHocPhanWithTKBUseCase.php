<?php

namespace App\Application\TLK\UseCases;

use App\Domain\TLK\Repositories\TLKRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * UseCase: Xếp thời khóa biểu (tạo lớp học phần)
 */
class CreateLopHocPhanWithTKBUseCase
{
    public function __construct(
        private TLKRepositoryInterface $repository
    ) {
    }

    public function execute(string $maHocPhan, string $hocKyId, ?string $giangVienId, array $danhSachLop): array
    {
        if (!$maHocPhan) {
            throw new \InvalidArgumentException('Mã học phần không được rỗng');
        }

        if (!$hocKyId) {
            throw new \InvalidArgumentException('Học kỳ ID không được rỗng');
        }

        if (empty($danhSachLop)) {
            throw new \InvalidArgumentException('Danh sách lớp không được rỗng');
        }

        // Find the HocPhan
        $hocPhan = $this->repository->findHocPhanByMaMon($maHocPhan, $hocKyId);

        if (!$hocPhan) {
            throw new \RuntimeException('Không tìm thấy học phần');
        }

        $createdCount = 0;

        DB::transaction(function () use ($hocPhan, $giangVienId, $danhSachLop, &$createdCount) {
            foreach ($danhSachLop as $lop) {
                $maLop = $lop['maLop'] ?? $lop['tenLop'] ?? null;
                $siSoToiDa = $lop['siSoToiDa'] ?? 50;
                $lichHocs = $lop['lichHoc'] ?? [];

                if (!$maLop)
                    continue;

                // Extract dates
                $ngayBatDau = null;
                $ngayKetThuc = null;

                if (!empty($lop['ngayBatDau'])) {
                    $ngayBatDau = date('Y-m-d', strtotime($lop['ngayBatDau']));
                }
                if (!empty($lop['ngayKetThuc'])) {
                    $ngayKetThuc = date('Y-m-d', strtotime($lop['ngayKetThuc']));
                }

                // Create LopHocPhan
                $lhp = $this->repository->createLopHocPhan([
                    'ma_lop' => $maLop,
                    'hoc_phan_id' => $hocPhan->id,
                    'giang_vien_id' => $giangVienId,
                    'so_luong_toi_da' => $siSoToiDa,
                    'phong_mac_dinh_id' => $lop['phongHocId'] ?? null,
                    'ngay_bat_dau' => $ngayBatDau,
                    'ngay_ket_thuc' => $ngayKetThuc,
                ]);

                // Check if TKB info is directly on lop object (new frontend format)
                if (!empty($lop['tietBatDau']) && !empty($lop['tietKetThuc']) && !empty($lop['thuTrongTuan'])) {
                    $this->repository->createLichHocDinhKy([
                        'lop_hoc_phan_id' => $lhp->id,
                        'thu' => $lop['thuTrongTuan'],
                        'tiet_bat_dau' => $lop['tietBatDau'],
                        'tiet_ket_thuc' => $lop['tietKetThuc'],
                        'phong_id' => $lop['phongHocId'] ?? null,
                    ]);
                }

                // Also create LichHocDinhKy from lichHoc array if provided (old format)
                foreach ($lichHocs as $lich) {
                    $this->repository->createLichHocDinhKy([
                        'lop_hoc_phan_id' => $lhp->id,
                        'thu' => $lich['thu'] ?? 2,
                        'tiet_bat_dau' => $lich['tietBatDau'] ?? 1,
                        'tiet_ket_thuc' => $lich['tietKetThuc'] ?? 3,
                        'phong_id' => $lich['phongId'] ?? null,
                    ]);
                }

                $createdCount++;
            }
        });

        return [
            'isSuccess' => true,
            'data' => ['createdCount' => $createdCount],
            'message' => "Xếp thời khóa biểu thành công ({$createdCount} lớp)"
        ];
    }
}
