<?php

namespace Tests\Unit\TLK;

use PHPUnit\Framework\TestCase;
use App\Infrastructure\TLK\Persistence\Mappers\TlkMapper;
use App\Domain\TLK\Entities\DeXuatEntity;
use App\Domain\TLK\Entities\PhongHocEntity;

/**
 * Unit Tests for TlkMapper
 */
class MapperTest extends TestCase
{
    public function test_to_de_xuat_entity_converts_model_correctly(): void
    {
        $model = (object) [
            'id' => 'uuid-dx-1',
            'mon_hoc_id' => 'uuid-mh-1',
            'hoc_ky_id' => 'uuid-hk-1',
            'khoa_id' => 'uuid-khoa-1',
            'nguoi_de_xuat_id' => 'uuid-user-1',
            'trang_thai' => DeXuatEntity::STATUS_CHO_DUYET_TK,
            'so_lop' => 2,
            'so_sinh_vien_du_kien' => 60,
            'ghi_chu' => 'Test ghi chú',
            'ly_do_tu_choi' => null,
            'created_at' => '2024-01-10 10:00:00',
        ];
        $model->monHoc = (object) ['ten_mon' => 'Test Subject', 'ma_mon' => 'CS101'];

        $entity = TlkMapper::toDeXuatEntity($model);

        $this->assertInstanceOf(DeXuatEntity::class, $entity);
        $this->assertEquals('uuid-dx-1', $entity->id);
        $this->assertEquals(DeXuatEntity::STATUS_CHO_DUYET_TK, $entity->trangThai);
        $this->assertEquals(2, $entity->soLopDeXuat);
        $this->assertTrue($entity->isPendingTK());
    }

    public function test_to_phong_hoc_entity_converts_model_correctly(): void
    {
        $model = (object) [
            'id' => 'uuid-ph-1',
            'ma_phong' => 'A101',
            'ten_phong' => 'Phòng máy A101',
            'khoa_id' => null,
            'suc_chua' => 40,
            'loai_phong' => 'phong_may',
            'toa_nha' => 'A',
        ];

        $entity = TlkMapper::toPhongHocEntity($model);

        $this->assertInstanceOf(PhongHocEntity::class, $entity);
        $this->assertEquals('uuid-ph-1', $entity->id);
        $this->assertEquals('A101', $entity->maPhong);
        $this->assertEquals(40, $entity->sucChua);
        $this->assertTrue($entity->isAvailable());
    }

    public function test_format_de_xuat_for_api_returns_fe_compatible_format(): void
    {
        $entity = new DeXuatEntity(
            id: 'uuid-dx-1',
            monHocId: 'uuid-mh-1',
            hocKyId: 'uuid-hk-1',
            khoaId: 'uuid-khoa-1',
            trangThai: DeXuatEntity::STATUS_PDT_DUYET,
            soLopDeXuat: 3,
        );

        $result = TlkMapper::formatDeXuatForApi($entity);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('trangThai', $result);
        $this->assertArrayHasKey('trangThaiLabel', $result);
        $this->assertArrayHasKey('isApproved', $result);
        $this->assertTrue($result['isApproved']);
        $this->assertEquals('PDT đã duyệt', $result['trangThaiLabel']);
    }

    public function test_format_phong_hoc_for_api_returns_fe_compatible_format(): void
    {
        $entity = new PhongHocEntity(
            id: 'uuid-ph-1',
            maPhong: 'LAB-01',
            tenPhong: 'Phòng thực hành 01',
            sucChua: 30,
            loaiPhong: 'thuc_hanh',
        );

        $result = TlkMapper::formatPhongHocForApi($entity);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('maPhong', $result);
        $this->assertArrayHasKey('displayName', $result);
        $this->assertArrayHasKey('isLab', $result);
        $this->assertTrue($result['isLab']);
    }
}
