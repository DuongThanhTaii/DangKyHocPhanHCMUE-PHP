<?php

namespace Tests\Unit\SinhVien;

use PHPUnit\Framework\TestCase;
use App\Domain\SinhVien\Entities\SinhVienEntity;
use App\Domain\SinhVien\Entities\LopHocPhanEntity;
use App\Domain\SinhVien\Entities\DangKyHocPhanEntity;
use App\Domain\SinhVien\ValueObjects\TrangThaiDangKy;

/**
 * Unit Tests for SinhVienMapper
 * 
 * Note: SinhVienMapper uses typed Eloquent Models, so we test the Entity
 * conversion logic and formatForApi methods separately.
 */
class MapperTest extends TestCase
{
    public function test_sinh_vien_entity_can_be_created_correctly(): void
    {
        $entity = new SinhVienEntity(
            id: 'uuid-sv-1',
            maSoSinhVien: '21S101234',
            lop: 'CNTT-K21',
            khoaId: 'uuid-khoa-1',
            khoaHoc: '2021-2025',
            nganhId: 'uuid-nganh-1',
            hoTen: 'Nguyễn Văn Test',
            email: 'nvt@test.com',
        );

        $this->assertEquals('uuid-sv-1', $entity->id);
        $this->assertEquals('21S101234', $entity->maSoSinhVien);
        $this->assertEquals('CNTT-K21', $entity->lop);
        $this->assertEquals('Nguyễn Văn Test', $entity->hoTen);
    }

    public function test_sinh_vien_entity_get_display_name(): void
    {
        $entity = new SinhVienEntity(
            id: 'uuid-1',
            maSoSinhVien: '21S101234',
            lop: 'CNTT-K21',
            hoTen: 'Nguyễn Văn A',
        );

        $this->assertEquals('Nguyễn Văn A', $entity->getDisplayName());
    }

    public function test_lop_hoc_phan_entity_can_be_created_correctly(): void
    {
        $entity = new LopHocPhanEntity(
            id: 'uuid-lhp-1',
            hocPhanId: 'uuid-hp-1',
            maLop: 'CS101.01',
            soLuongToiDa: 50,
            soLuongHienTai: 35,
            tenMonHoc: 'Nhập môn lập trình',
            maMonHoc: 'CS101',
            soTinChi: 3,
        );

        $this->assertEquals('uuid-lhp-1', $entity->id);
        $this->assertEquals('CS101.01', $entity->maLop);
        $this->assertEquals(50, $entity->soLuongToiDa);
        $this->assertEquals(35, $entity->soLuongHienTai);
        $this->assertFalse($entity->isFull());
        $this->assertEquals(15, $entity->getAvailableSlots());
    }

    public function test_lop_hoc_phan_entity_is_full_when_capacity_reached(): void
    {
        $entity = new LopHocPhanEntity(
            id: 'uuid-lhp-1',
            hocPhanId: 'uuid-hp-1',
            maLop: 'CS101.01',
            soLuongToiDa: 50,
            soLuongHienTai: 50,
        );

        $this->assertTrue($entity->isFull());
        $this->assertEquals(0, $entity->getAvailableSlots());
    }

    public function test_dang_ky_hoc_phan_entity_can_be_created_correctly(): void
    {
        $entity = new DangKyHocPhanEntity(
            id: 'uuid-dkhp-1',
            sinhVienId: 'uuid-sv-1',
            lopHocPhanId: 'uuid-lhp-1',
            trangThai: new TrangThaiDangKy('da_dang_ky'),
            maLop: 'CS101.01',
            tenMonHoc: 'Nhập môn lập trình',
            soTinChi: 3,
        );

        $this->assertEquals('uuid-dkhp-1', $entity->id);
        $this->assertEquals('CS101.01', $entity->maLop);
        $this->assertTrue($entity->isActive());
    }

    public function test_dang_ky_hoc_phan_entity_can_be_cancelled(): void
    {
        $entity = new DangKyHocPhanEntity(
            id: 'uuid-dkhp-1',
            sinhVienId: 'uuid-sv-1',
            lopHocPhanId: 'uuid-lhp-1',
            trangThai: new TrangThaiDangKy('da_dang_ky'),
        );

        $this->assertTrue($entity->canBeCancelled());
        $this->assertFalse($entity->isCancelled());
    }

    public function test_sinh_vien_entity_to_array_returns_correct_format(): void
    {
        $entity = new SinhVienEntity(
            id: 'uuid-sv-1',
            maSoSinhVien: '21S101234',
            lop: 'CNTT-K21',
            khoaId: 'uuid-khoa-1',
            khoaHoc: '2021-2025',
            hoTen: 'Nguyễn Văn A',
            email: 'nva@test.com',
        );

        $result = $entity->toArray();

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('maSoSinhVien', $result);
        $this->assertArrayHasKey('lop', $result);
        $this->assertArrayHasKey('hoTen', $result);
        $this->assertEquals('21S101234', $result['maSoSinhVien']);
    }

    public function test_lop_hoc_phan_entity_to_array_returns_correct_format(): void
    {
        $entity = new LopHocPhanEntity(
            id: 'uuid-lhp-1',
            hocPhanId: 'uuid-hp-1',
            maLop: 'CS101.01',
            soLuongToiDa: 50,
            soLuongHienTai: 35,
            tenMonHoc: 'Nhập môn',
            soTinChi: 3,
        );

        $result = $entity->toArray();

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('maLop', $result);
        $this->assertArrayHasKey('isFull', $result);
        $this->assertArrayHasKey('availableSlots', $result);
        $this->assertFalse($result['isFull']);
        $this->assertEquals(15, $result['availableSlots']);
    }
}
