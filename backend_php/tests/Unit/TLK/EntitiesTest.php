<?php

namespace Tests\Unit\TLK;

use PHPUnit\Framework\TestCase;
use App\Domain\TLK\Entities\DeXuatEntity;
use App\Domain\TLK\Entities\PhongHocEntity;
use DateTimeImmutable;

/**
 * Unit Tests for TLK Domain Entities
 */
class EntitiesTest extends TestCase
{
    // ==================== DE XUAT ENTITY ====================

    public function test_de_xuat_entity_creation(): void
    {
        $entity = new DeXuatEntity(
            id: 'dx-001',
            monHocId: 'mh-001',
            hocKyId: 'hk-001',
            khoaId: 'khoa-001',
            soLopDeXuat: 2,
            tenMonHoc: 'Lập trình Web',
        );

        $this->assertEquals('dx-001', $entity->id);
        $this->assertEquals(2, $entity->soLopDeXuat);
        $this->assertEquals('Lập trình Web', $entity->tenMonHoc);
    }

    public function test_de_xuat_is_pending_tk(): void
    {
        $pending = new DeXuatEntity('1', 'mh-1', 'hk-1', 'khoa-1', trangThai: DeXuatEntity::STATUS_CHO_DUYET_TK);
        $approved = new DeXuatEntity('2', 'mh-2', 'hk-2', 'khoa-2', trangThai: DeXuatEntity::STATUS_TK_DUYET);

        $this->assertTrue($pending->isPendingTK());
        $this->assertFalse($approved->isPendingTK());
    }

    public function test_de_xuat_is_pending_pdt(): void
    {
        $tkApproved = new DeXuatEntity('1', 'mh-1', 'hk-1', 'khoa-1', trangThai: DeXuatEntity::STATUS_TK_DUYET);
        $choPdt = new DeXuatEntity('2', 'mh-2', 'hk-2', 'khoa-2', trangThai: DeXuatEntity::STATUS_CHO_DUYET_PDT);

        $this->assertTrue($tkApproved->isPendingPDT());
        $this->assertTrue($choPdt->isPendingPDT());
    }

    public function test_de_xuat_is_approved(): void
    {
        $approved = new DeXuatEntity('1', 'mh-1', 'hk-1', 'khoa-1', trangThai: DeXuatEntity::STATUS_PDT_DUYET);
        $pending = new DeXuatEntity('2', 'mh-2', 'hk-2', 'khoa-2', trangThai: DeXuatEntity::STATUS_CHO_DUYET_TK);

        $this->assertTrue($approved->isApproved());
        $this->assertFalse($pending->isApproved());
    }

    public function test_de_xuat_is_rejected(): void
    {
        $rejected = new DeXuatEntity('1', 'mh-1', 'hk-1', 'khoa-1', trangThai: DeXuatEntity::STATUS_TU_CHOI);
        $this->assertTrue($rejected->isRejected());
    }

    public function test_de_xuat_can_edit(): void
    {
        $pending = new DeXuatEntity('1', 'mh-1', 'hk-1', 'khoa-1', trangThai: DeXuatEntity::STATUS_CHO_DUYET_TK);
        $approved = new DeXuatEntity('2', 'mh-2', 'hk-2', 'khoa-2', trangThai: DeXuatEntity::STATUS_PDT_DUYET);

        $this->assertTrue($pending->canEdit());
        $this->assertFalse($approved->canEdit());
    }

    public function test_de_xuat_get_status_label(): void
    {
        $pending = new DeXuatEntity('1', 'mh-1', 'hk-1', 'khoa-1', trangThai: DeXuatEntity::STATUS_CHO_DUYET_TK);
        $this->assertEquals('Chờ TK duyệt', $pending->getStatusLabel());
    }

    // ==================== PHONG HOC ENTITY ====================

    public function test_phong_hoc_entity_creation(): void
    {
        $entity = new PhongHocEntity(
            id: 'ph-001',
            maPhong: 'A101',
            tenPhong: 'Phòng học 101',
            sucChua: 50,
            toaNha: 'Tòa A',
        );

        $this->assertEquals('ph-001', $entity->id);
        $this->assertEquals('A101', $entity->maPhong);
        $this->assertEquals(50, $entity->sucChua);
    }

    public function test_phong_hoc_can_accommodate(): void
    {
        $entity = new PhongHocEntity('1', 'A101', sucChua: 50);

        $this->assertTrue($entity->canAccommodate(40));
        $this->assertTrue($entity->canAccommodate(50));
        $this->assertFalse($entity->canAccommodate(60));
    }

    public function test_phong_hoc_is_lab(): void
    {
        $lab = new PhongHocEntity('1', 'LAB01', loaiPhong: 'Lab');
        $normal = new PhongHocEntity('2', 'A101', loaiPhong: 'Ly_thuyet');

        $this->assertTrue($lab->isLab());
        $this->assertFalse($normal->isLab());
    }

    public function test_phong_hoc_get_display_name(): void
    {
        $withBuilding = new PhongHocEntity('1', 'A101', tenPhong: 'Phòng 101', toaNha: 'Tòa A');
        $withoutBuilding = new PhongHocEntity('2', 'B201', tenPhong: 'Phòng 201');

        $this->assertEquals('Tòa A - Phòng 101', $withBuilding->getDisplayName());
        $this->assertEquals('Phòng 201', $withoutBuilding->getDisplayName());
    }
}
