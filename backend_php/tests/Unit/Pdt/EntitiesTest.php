<?php

namespace Tests\Unit\Pdt;

use PHPUnit\Framework\TestCase;
use App\Domain\Pdt\Entities\HocKyEntity;
use App\Domain\Pdt\Entities\DotDangKyEntity;
use App\Domain\Pdt\Entities\MonHocEntity;
use App\Domain\Pdt\Entities\KyPhaseEntity;
use DateTimeImmutable;

/**
 * Unit Tests for PDT Domain Entities
 */
class EntitiesTest extends TestCase
{
    // ==================== HOC KY ENTITY ====================

    public function test_hoc_ky_entity_creation(): void
    {
        $entity = new HocKyEntity(
            id: 'hk-001',
            maHocKy: 'HK1_2024',
            tenHocKy: 'Học kỳ 1 2024-2025',
            namHoc: 2024,
            hocKySo: 1,
            isHienHanh: true,
        );

        $this->assertEquals('hk-001', $entity->id);
        $this->assertEquals('HK1_2024', $entity->maHocKy);
        $this->assertEquals(2024, $entity->namHoc);
        $this->assertEquals(1, $entity->hocKySo);
        $this->assertTrue($entity->isHienHanh);
    }

    public function test_hoc_ky_is_current(): void
    {
        $current = new HocKyEntity('1', 'HK1', 'HK1', isHienHanh: true);
        $notCurrent = new HocKyEntity('2', 'HK2', 'HK2', isHienHanh: false);

        $this->assertTrue($current->isCurrent());
        $this->assertFalse($notCurrent->isCurrent());
    }

    public function test_hoc_ky_is_active(): void
    {
        $now = new DateTimeImmutable('2025-01-15');
        
        $active = new HocKyEntity(
            '1', 'HK1', 'HK1',
            ngayBatDau: new DateTimeImmutable('2025-01-01'),
            ngayKetThuc: new DateTimeImmutable('2025-06-01'),
        );
        
        $notStarted = new HocKyEntity(
            '2', 'HK2', 'HK2',
            ngayBatDau: new DateTimeImmutable('2025-02-01'),
        );

        $this->assertTrue($active->isActive($now));
        $this->assertFalse($notStarted->isActive($now));
    }

    public function test_hoc_ky_get_display_name(): void
    {
        $hk = new HocKyEntity('1', 'HK1_2024', 'Test', namHoc: 2024, hocKySo: 1);
        $this->assertEquals('HK1 2024-2025', $hk->getDisplayName());
    }

    // ==================== DOT DANG KY ENTITY ====================

    public function test_dot_dang_ky_entity_creation(): void
    {
        $entity = new DotDangKyEntity(
            id: 'ddk-001',
            hocKyId: 'hk-001',
            tenDot: 'Đợt 1',
            ngayBatDau: new DateTimeImmutable('2025-01-01'),
            ngayKetThuc: new DateTimeImmutable('2025-01-15'),
        );

        $this->assertEquals('ddk-001', $entity->id);
        $this->assertEquals('Đợt 1', $entity->tenDot);
    }

    public function test_dot_dang_ky_is_open(): void
    {
        $now = new DateTimeImmutable('2025-01-10');
        
        $open = new DotDangKyEntity(
            '1', 'hk-1', 'Đợt 1',
            ngayBatDau: new DateTimeImmutable('2025-01-01'),
            ngayKetThuc: new DateTimeImmutable('2025-01-15'),
        );
        
        $closed = new DotDangKyEntity(
            '2', 'hk-1', 'Đợt 2',
            daKetThuc: true,
        );

        $this->assertTrue($open->isOpen($now));
        $this->assertFalse($closed->isOpen($now));
    }

    public function test_dot_dang_ky_is_upcoming(): void
    {
        $now = new DateTimeImmutable('2025-01-01');
        
        $upcoming = new DotDangKyEntity(
            '1', 'hk-1', 'Đợt tương lai',
            ngayBatDau: new DateTimeImmutable('2025-02-01'),
        );

        $this->assertTrue($upcoming->isUpcoming($now));
    }

    public function test_dot_dang_ky_get_status(): void
    {
        $now = new DateTimeImmutable('2025-01-10');
        
        $open = new DotDangKyEntity('1', 'hk-1', 'Đợt 1',
            ngayBatDau: new DateTimeImmutable('2025-01-01'),
            ngayKetThuc: new DateTimeImmutable('2025-01-15'),
        );
        
        $upcoming = new DotDangKyEntity('2', 'hk-1', 'Đợt 2',
            ngayBatDau: new DateTimeImmutable('2025-02-01'),
        );
        
        $closed = new DotDangKyEntity('3', 'hk-1', 'Đợt 3', daKetThuc: true);

        $this->assertEquals('Đang mở', $open->getStatus($now));
        $this->assertEquals('Sắp mở', $upcoming->getStatus($now));
        $this->assertEquals('Đã đóng', $closed->getStatus($now));
    }

    // ==================== MON HOC ENTITY ====================

    public function test_mon_hoc_entity_creation(): void
    {
        $entity = new MonHocEntity(
            id: 'mh-001',
            maMonHoc: 'CNTT001',
            tenMonHoc: 'Lập trình Web',
            soTinChi: 3,
            coThucHanh: true,
        );

        $this->assertEquals('mh-001', $entity->id);
        $this->assertEquals('CNTT001', $entity->maMonHoc);
        $this->assertEquals(3, $entity->soTinChi);
        $this->assertTrue($entity->coThucHanh);
    }

    public function test_mon_hoc_has_practical(): void
    {
        $withPractical = new MonHocEntity('1', 'MH1', 'Test', coThucHanh: true);
        $withoutPractical = new MonHocEntity('2', 'MH2', 'Test', coThucHanh: false);

        $this->assertTrue($withPractical->hasPractical());
        $this->assertFalse($withoutPractical->hasPractical());
    }

    public function test_mon_hoc_get_credit_display(): void
    {
        $entity = new MonHocEntity('1', 'MH1', 'Test', soTinChi: 3);
        $this->assertEquals('3 TC', $entity->getCreditDisplay());
    }

    public function test_mon_hoc_get_full_name(): void
    {
        $entity = new MonHocEntity('1', 'CNTT001', 'Lập trình Web');
        $this->assertEquals('CNTT001 - Lập trình Web', $entity->getFullName());
    }

    // ==================== KY PHASE ENTITY ====================

    public function test_ky_phase_entity_creation(): void
    {
        $entity = new KyPhaseEntity(
            id: 'phase-001',
            hocKyId: 'hk-001',
            tenPhase: 'Đăng ký học phần',
            loaiPhase: KyPhaseEntity::PHASE_DANG_KY,
            thuTu: 1,
        );

        $this->assertEquals('phase-001', $entity->id);
        $this->assertEquals('Đăng ký học phần', $entity->tenPhase);
        $this->assertEquals(1, $entity->thuTu);
    }

    public function test_ky_phase_is_active(): void
    {
        $now = new DateTimeImmutable('2025-01-15');
        
        $active = new KyPhaseEntity(
            '1', 'hk-1', 'Phase 1',
            ngayBatDau: new DateTimeImmutable('2025-01-01'),
            ngayKetThuc: new DateTimeImmutable('2025-02-01'),
        );
        
        $inactive = new KyPhaseEntity(
            '2', 'hk-1', 'Phase 2',
            ngayBatDau: new DateTimeImmutable('2025-03-01'),
        );

        $this->assertTrue($active->isActive($now));
        $this->assertFalse($inactive->isActive($now));
    }

    public function test_ky_phase_types(): void
    {
        $dangKy = new KyPhaseEntity('1', 'hk-1', 'Đăng ký', loaiPhase: KyPhaseEntity::PHASE_DANG_KY);
        $giuaKy = new KyPhaseEntity('2', 'hk-1', 'Giữa kỳ', loaiPhase: KyPhaseEntity::PHASE_GIUA_KY);
        $cuoiKy = new KyPhaseEntity('3', 'hk-1', 'Cuối kỳ', loaiPhase: KyPhaseEntity::PHASE_CUOI_KY);

        $this->assertTrue($dangKy->isRegistrationPhase());
        $this->assertTrue($giuaKy->isMidterm());
        $this->assertTrue($cuoiKy->isFinal());
    }
}
