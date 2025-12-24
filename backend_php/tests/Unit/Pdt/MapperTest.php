<?php

namespace Tests\Unit\Pdt;

use PHPUnit\Framework\TestCase;
use App\Infrastructure\Pdt\Persistence\Mappers\PdtMapper;
use App\Domain\Pdt\Entities\DotDangKyEntity;
use App\Domain\Pdt\Entities\MonHocEntity;
use App\Domain\Pdt\Entities\KyPhaseEntity;

/**
 * Unit Tests for PdtMapper
 */
class MapperTest extends TestCase
{
    public function test_to_dot_dang_ky_entity_converts_model_correctly(): void
    {
        $model = new class {
            public $id = 'uuid-dot-1';
            public $hoc_ky_id = 'uuid-hk-1';
            public $loai_dot = 'dot_dang_ky_1';
            public $thoi_gian_bat_dau = '2024-01-01 08:00:00';
            public $thoi_gian_ket_thuc = '2024-01-15 23:59:59';
            public function isActive() { return true; }
        };

        $entity = PdtMapper::toDotDangKyEntity($model);

        $this->assertInstanceOf(DotDangKyEntity::class, $entity);
        $this->assertEquals('uuid-dot-1', $entity->id);
        $this->assertEquals('uuid-hk-1', $entity->hocKyId);
        $this->assertEquals('dot_dang_ky_1', $entity->tenDot);
    }

    public function test_to_mon_hoc_entity_converts_model_correctly(): void
    {
        $model = (object) [
            'id' => 'uuid-mh-1',
            'ma_mon' => 'CS101',
            'ten_mon' => 'Nhập môn lập trình',
            'so_tin_chi' => 3,
            'khoa_id' => 'uuid-khoa-1',
        ];

        $entity = PdtMapper::toMonHocEntity($model);

        $this->assertInstanceOf(MonHocEntity::class, $entity);
        $this->assertEquals('uuid-mh-1', $entity->id);
        $this->assertEquals('CS101', $entity->maMonHoc);
        $this->assertEquals('Nhập môn lập trình', $entity->tenMonHoc);
        $this->assertEquals(3, $entity->soTinChi);
    }

    public function test_to_ky_phase_entity_converts_model_correctly(): void
    {
        $model = (object) [
            'id' => 'uuid-phase-1',
            'hoc_ky_id' => 'uuid-hk-1',
            'phase' => 'dang_ky_hoc_phan',
            'start_at' => '2024-01-01 08:00:00',
            'end_at' => '2024-01-15 23:59:59',
        ];

        $entity = PdtMapper::toKyPhaseEntity($model);

        $this->assertInstanceOf(KyPhaseEntity::class, $entity);
        $this->assertEquals('uuid-phase-1', $entity->id);
        $this->assertEquals('dang_ky_hoc_phan', $entity->loaiPhase);
    }

    public function test_format_mon_hoc_for_api_returns_fe_compatible_format(): void
    {
        $entity = new MonHocEntity(
            id: 'uuid-1',
            maMonHoc: 'CS101',
            tenMonHoc: 'Nhập môn lập trình',
            soTinChi: 3,
            khoaId: 'uuid-khoa-1',
        );

        $result = PdtMapper::formatMonHocForApi($entity);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('ma_mon', $result);
        $this->assertArrayHasKey('ten_mon', $result);
        $this->assertArrayHasKey('so_tin_chi', $result);
        $this->assertArrayHasKey('creditDisplay', $result);
        $this->assertEquals('CS101', $result['ma_mon']);
        $this->assertEquals(3, $result['so_tin_chi']);
    }
}
