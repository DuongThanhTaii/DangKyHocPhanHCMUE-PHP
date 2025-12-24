<?php

namespace Tests\Unit\GiangVien;

use PHPUnit\Framework\TestCase;
use App\Infrastructure\GiangVien\Persistence\Mappers\GiangVienMapper;
use App\Domain\GiangVien\Entities\GiangVienEntity;
use App\Domain\GiangVien\Entities\DiemSinhVienEntity;

/**
 * Unit Tests for GiangVienMapper
 */
class MapperTest extends TestCase
{
    public function test_to_giang_vien_entity_converts_model_correctly(): void
    {
        $model = (object) [
            'id' => 'uuid-gv-1',
            'ma_giang_vien' => 'GV001',
            'ho_ten' => 'Nguyễn Văn A',
            'email' => 'nva@hcmue.edu.vn',
            'khoa_id' => 'uuid-khoa-1',
            'chuc_danh' => 'Giảng viên',
            'hoc_vi' => 'Thạc sĩ',
            'is_active' => true,
        ];

        $entity = GiangVienMapper::toGiangVienEntity($model);

        $this->assertInstanceOf(GiangVienEntity::class, $entity);
        $this->assertEquals('uuid-gv-1', $entity->id);
        $this->assertEquals('GV001', $entity->maGiangVien);
        $this->assertEquals('Nguyễn Văn A', $entity->hoTen);
        $this->assertEquals('Thạc sĩ', $entity->hocVi);
    }

    public function test_to_diem_sinh_vien_entity_converts_model_correctly(): void
    {
        $model = (object) [
            'id' => 'uuid-diem-1',
            'sinh_vien_id' => 'uuid-sv-1',
            'lop_hoc_phan_id' => 'uuid-lhp-1',
            'diem_qua_trinh' => 8.5,
            'diem_thuc_hanh' => null,
            'diem_cuoi_ky' => 7.0,
            'diem_tong_ket' => 7.75,
            'diem_chu' => 'B+',
            'is_locked' => false,
        ];
        $model->sinhVien = (object) [
            'ma_so_sinh_vien' => '21S123456',
            'user' => (object) ['ho_ten' => 'Trần Thị B'],
        ];

        $entity = GiangVienMapper::toDiemSinhVienEntity($model);

        $this->assertInstanceOf(DiemSinhVienEntity::class, $entity);
        $this->assertEquals('uuid-diem-1', $entity->id);
        $this->assertEquals(8.5, $entity->diemQuaTrinh);
        $this->assertEquals(7.0, $entity->diemCuoiKy);
        $this->assertEquals('21S123456', $entity->maSoSinhVien);
    }

    public function test_format_giang_vien_for_api_returns_fe_compatible_format(): void
    {
        $entity = new GiangVienEntity(
            id: 'uuid-gv-1',
            maGiangVien: 'GV001',
            hoTen: 'Nguyễn Văn A',
            email: 'nva@hcmue.edu.vn',
            chucDanh: 'Giảng viên',
            hocVi: 'Tiến sĩ',
        );

        $result = GiangVienMapper::formatGiangVienForApi($entity);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('maGiangVien', $result);
        $this->assertArrayHasKey('hoTen', $result);
        $this->assertArrayHasKey('displayName', $result);
        $this->assertArrayHasKey('fullTitle', $result);
        $this->assertEquals('GV001', $result['maGiangVien']);
    }

    public function test_format_diem_for_api_returns_fe_compatible_format(): void
    {
        $entity = new DiemSinhVienEntity(
            id: 'uuid-diem-1',
            sinhVienId: 'uuid-sv-1',
            lopHocPhanId: 'uuid-lhp-1',
            diemQuaTrinh: 8.0,
            diemCuoiKy: 7.5,
            diemTongKet: 7.75,
            maSoSinhVien: '21S123456',
            hoTen: 'Trần Thị B',
        );

        $result = GiangVienMapper::formatDiemForApi($entity);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('diemQuaTrinh', $result);
        $this->assertArrayHasKey('diemTongKet', $result);
        $this->assertArrayHasKey('isPassing', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertTrue($result['isPassing']);
    }
}
