<?php

namespace Tests\Unit\Common;

use PHPUnit\Framework\TestCase;
use App\Domain\Common\Entities\KhoaEntity;
use App\Domain\Common\Entities\NganhEntity;

/**
 * Unit Tests for Common Entities
 */
class EntitiesTest extends TestCase
{
    public function test_khoa_entity_can_be_created(): void
    {
        $entity = new KhoaEntity(
            id: 'uuid-khoa-1',
            maKhoa: 'CNTT',
            tenKhoa: 'Công nghệ thông tin',
            moTa: 'Khoa CNTT',
            isActive: true,
        );

        $this->assertEquals('uuid-khoa-1', $entity->id);
        $this->assertEquals('CNTT', $entity->maKhoa);
        $this->assertEquals('Công nghệ thông tin', $entity->tenKhoa);
        $this->assertTrue($entity->isActive());
    }

    public function test_khoa_entity_get_display_name(): void
    {
        $entity = new KhoaEntity(
            id: 'uuid-1',
            maKhoa: 'CNTT',
            tenKhoa: 'Công nghệ thông tin',
        );

        $this->assertEquals('Công nghệ thông tin', $entity->getDisplayName());
        $this->assertEquals('CNTT', $entity->getCode());
    }

    public function test_khoa_entity_to_array(): void
    {
        $entity = new KhoaEntity(
            id: 'uuid-1',
            maKhoa: 'GDTH',
            tenKhoa: 'Giáo dục tiểu học',
        );

        $result = $entity->toArray();

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('maKhoa', $result);
        $this->assertArrayHasKey('tenKhoa', $result);
        $this->assertArrayHasKey('displayName', $result);
    }

    public function test_nganh_entity_can_be_created(): void
    {
        $entity = new NganhEntity(
            id: 'uuid-nganh-1',
            maNganh: 'CNTT',
            tenNganh: 'Công nghệ thông tin',
            khoaId: 'uuid-khoa-1',
            soNamHoc: 4,
            isActive: true,
        );

        $this->assertEquals('uuid-nganh-1', $entity->id);
        $this->assertEquals('CNTT', $entity->maNganh);
        $this->assertEquals('Công nghệ thông tin', $entity->tenNganh);
        $this->assertEquals(4, $entity->getDurationYears());
    }

    public function test_nganh_entity_get_display_name_and_code(): void
    {
        $entity = new NganhEntity(
            id: 'uuid-1',
            maNganh: 'KTPM',
            tenNganh: 'Kỹ thuật phần mềm',
        );

        $this->assertEquals('Kỹ thuật phần mềm', $entity->getDisplayName());
        $this->assertEquals('KTPM', $entity->getCode());
    }

    public function test_nganh_entity_to_array(): void
    {
        $entity = new NganhEntity(
            id: 'uuid-1',
            maNganh: 'KTPM',
            tenNganh: 'Kỹ thuật phần mềm',
            soNamHoc: 5,
        );

        $result = $entity->toArray();

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('maNganh', $result);
        $this->assertArrayHasKey('tenNganh', $result);
        $this->assertArrayHasKey('soNamHoc', $result);
        $this->assertEquals(5, $result['soNamHoc']);
    }
}
