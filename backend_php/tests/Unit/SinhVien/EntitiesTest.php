<?php

namespace Tests\Unit\SinhVien;

use PHPUnit\Framework\TestCase;
use App\Domain\SinhVien\Entities\SinhVienEntity;
use App\Domain\SinhVien\Entities\LopHocPhanEntity;
use App\Domain\SinhVien\Entities\DangKyHocPhanEntity;
use App\Domain\SinhVien\ValueObjects\TrangThaiDangKy;
use DateTimeImmutable;

/**
 * Unit Tests for SinhVien Domain Entities
 */
class EntitiesTest extends TestCase
{
    // ==================== SINH VIEN ENTITY ====================

    public function test_sinh_vien_entity_creation(): void
    {
        $entity = new SinhVienEntity(
            id: 'uuid-123',
            maSoSinhVien: '49.01.104.123',
            lop: 'CNTT01',
            khoaId: 'khoa-001',
            hoTen: 'Nguyễn Văn A',
        );

        $this->assertEquals('uuid-123', $entity->id);
        $this->assertEquals('49.01.104.123', $entity->maSoSinhVien);
        $this->assertEquals('CNTT01', $entity->lop);
        $this->assertEquals('Nguyễn Văn A', $entity->hoTen);
    }

    public function test_sinh_vien_get_display_name(): void
    {
        $withName = new SinhVienEntity('1', '49.01.104.123', hoTen: 'Nguyễn Văn A');
        $withoutName = new SinhVienEntity('2', '49.01.104.456');

        $this->assertEquals('Nguyễn Văn A', $withName->getDisplayName());
        $this->assertEquals('49.01.104.456', $withoutName->getDisplayName());
    }

    public function test_sinh_vien_get_khoa_hoc_from_mssv(): void
    {
        $entity = new SinhVienEntity('1', '49.01.104.123');
        $this->assertEquals('49', $entity->getKhoaHocFromMSSV());
    }

    public function test_sinh_vien_belongs_to_khoa(): void
    {
        $entity = new SinhVienEntity('1', '49.01.104.123', khoaId: 'khoa-001');

        $this->assertTrue($entity->belongsToKhoa('khoa-001'));
        $this->assertFalse($entity->belongsToKhoa('khoa-002'));
    }

    // ==================== LOP HOC PHAN ENTITY ====================

    public function test_lop_hoc_phan_entity_creation(): void
    {
        $entity = new LopHocPhanEntity(
            id: 'lhp-001',
            hocPhanId: 'hp-001',
            maLop: 'CNTT01.01',
            soLuongToiDa: 50,
            soLuongHienTai: 30,
            tenMonHoc: 'Lập trình Web',
        );

        $this->assertEquals('lhp-001', $entity->id);
        $this->assertEquals('CNTT01.01', $entity->maLop);
        $this->assertEquals(50, $entity->soLuongToiDa);
        $this->assertEquals(30, $entity->soLuongHienTai);
    }

    public function test_lop_hoc_phan_is_full(): void
    {
        $full = new LopHocPhanEntity('1', 'hp-1', 'LHP01', soLuongToiDa: 50, soLuongHienTai: 50);
        $notFull = new LopHocPhanEntity('2', 'hp-2', 'LHP02', soLuongToiDa: 50, soLuongHienTai: 30);

        $this->assertTrue($full->isFull());
        $this->assertFalse($notFull->isFull());
    }

    public function test_lop_hoc_phan_available_slots(): void
    {
        $entity = new LopHocPhanEntity('1', 'hp-1', 'LHP01', soLuongToiDa: 50, soLuongHienTai: 30);
        $this->assertEquals(20, $entity->getAvailableSlots());
    }

    public function test_lop_hoc_phan_capacity_percentage(): void
    {
        $entity = new LopHocPhanEntity('1', 'hp-1', 'LHP01', soLuongToiDa: 50, soLuongHienTai: 25);
        $this->assertEquals(50.0, $entity->getCapacityPercentage());
    }

    public function test_lop_hoc_phan_registration_open(): void
    {
        $open = new LopHocPhanEntity('1', 'hp-1', 'LHP01', soLuongToiDa: 50, soLuongHienTai: 30);
        $closed = new LopHocPhanEntity('2', 'hp-2', 'LHP02', soLuongToiDa: 50, soLuongHienTai: 50);

        $this->assertTrue($open->isRegistrationOpen());
        $this->assertFalse($closed->isRegistrationOpen());
    }

    public function test_lop_hoc_phan_is_active(): void
    {
        $now = new DateTimeImmutable('2025-01-15');
        
        $active = new LopHocPhanEntity(
            '1', 'hp-1', 'LHP01',
            ngayBatDau: new DateTimeImmutable('2025-01-01'),
            ngayKetThuc: new DateTimeImmutable('2025-06-01'),
        );
        
        $notStarted = new LopHocPhanEntity(
            '2', 'hp-2', 'LHP02',
            ngayBatDau: new DateTimeImmutable('2025-02-01'),
        );
        
        $ended = new LopHocPhanEntity(
            '3', 'hp-3', 'LHP03',
            ngayKetThuc: new DateTimeImmutable('2025-01-01'),
        );

        $this->assertTrue($active->isActive($now));
        $this->assertFalse($notStarted->isActive($now));
        $this->assertFalse($ended->isActive($now));
    }

    // ==================== DANG KY HOC PHAN ENTITY ====================

    public function test_dang_ky_hoc_phan_entity_creation(): void
    {
        $entity = new DangKyHocPhanEntity(
            id: 'dkhp-001',
            sinhVienId: 'sv-001',
            lopHocPhanId: 'lhp-001',
            trangThai: TrangThaiDangKy::daDangKy(),
            maLop: 'CNTT01.01',
            tenMonHoc: 'Lập trình Web',
        );

        $this->assertEquals('dkhp-001', $entity->id);
        $this->assertEquals('sv-001', $entity->sinhVienId);
        $this->assertEquals('da_dang_ky', $entity->trangThai->value());
    }

    public function test_dang_ky_is_active(): void
    {
        $active = new DangKyHocPhanEntity('1', 'sv-1', 'lhp-1', TrangThaiDangKy::daDangKy());
        $cancelled = new DangKyHocPhanEntity('2', 'sv-2', 'lhp-2', TrangThaiDangKy::daHuy());

        $this->assertTrue($active->isActive());
        $this->assertFalse($cancelled->isActive());
    }

    public function test_dang_ky_is_cancelled(): void
    {
        $entity = new DangKyHocPhanEntity('1', 'sv-1', 'lhp-1', TrangThaiDangKy::daHuy());
        $this->assertTrue($entity->isCancelled());
    }

    public function test_dang_ky_can_be_cancelled(): void
    {
        $pending = new DangKyHocPhanEntity('1', 'sv-1', 'lhp-1', TrangThaiDangKy::daDangKy());
        $completed = new DangKyHocPhanEntity('2', 'sv-2', 'lhp-2', TrangThaiDangKy::daThanhToan());
        $cancelled = new DangKyHocPhanEntity('3', 'sv-3', 'lhp-3', TrangThaiDangKy::daHuy());

        $this->assertTrue($pending->canBeCancelled());
        $this->assertFalse($completed->canBeCancelled());
        $this->assertFalse($cancelled->canBeCancelled());
    }

    public function test_dang_ky_has_conflict(): void
    {
        $withConflict = new DangKyHocPhanEntity('1', 'sv-1', 'lhp-1', TrangThaiDangKy::daDangKy(), coXungDot: true);
        $noConflict = new DangKyHocPhanEntity('2', 'sv-2', 'lhp-2', TrangThaiDangKy::daDangKy(), coXungDot: false);

        $this->assertTrue($withConflict->hasConflict());
        $this->assertFalse($noConflict->hasConflict());
    }
}
