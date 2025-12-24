<?php

namespace Tests\Unit\GiangVien;

use PHPUnit\Framework\TestCase;
use App\Domain\GiangVien\Entities\GiangVienEntity;
use App\Domain\GiangVien\Entities\DiemSinhVienEntity;
use App\Domain\GiangVien\Entities\TaiLieuEntity;
use DateTimeImmutable;

/**
 * Unit Tests for GiangVien Domain Entities
 */
class EntitiesTest extends TestCase
{
    // ==================== GIANG VIEN ENTITY ====================

    public function test_giang_vien_entity_creation(): void
    {
        $entity = new GiangVienEntity(
            id: 'gv-001',
            maGiangVien: 'GV001',
            hoTen: 'Nguyễn Văn A',
            email: 'nva@hcmue.edu.vn',
            chucDanh: 'ThS',
        );

        $this->assertEquals('gv-001', $entity->id);
        $this->assertEquals('GV001', $entity->maGiangVien);
        $this->assertEquals('Nguyễn Văn A', $entity->hoTen);
        $this->assertEquals('ThS', $entity->chucDanh);
    }

    public function test_giang_vien_get_display_name(): void
    {
        $withName = new GiangVienEntity('1', 'GV001', hoTen: 'Nguyễn Văn A');
        $withoutName = new GiangVienEntity('2', 'GV002');

        $this->assertEquals('Nguyễn Văn A', $withName->getDisplayName());
        $this->assertEquals('GV002', $withoutName->getDisplayName());
    }

    public function test_giang_vien_get_full_title(): void
    {
        $withTitle = new GiangVienEntity('1', 'GV001', hoTen: 'Nguyễn Văn A', chucDanh: 'TS');
        $withoutTitle = new GiangVienEntity('2', 'GV002', hoTen: 'Trần Văn B');

        $this->assertEquals('TS. Nguyễn Văn A', $withTitle->getFullTitle());
        $this->assertEquals('Trần Văn B', $withoutTitle->getFullTitle());
    }

    public function test_giang_vien_belongs_to_khoa(): void
    {
        $gv = new GiangVienEntity('1', 'GV001', khoaId: 'khoa-001');

        $this->assertTrue($gv->belongsToKhoa('khoa-001'));
        $this->assertFalse($gv->belongsToKhoa('khoa-002'));
    }

    // ==================== DIEM SINH VIEN ENTITY ====================

    public function test_diem_sinh_vien_entity_creation(): void
    {
        $entity = new DiemSinhVienEntity(
            id: 'diem-001',
            sinhVienId: 'sv-001',
            lopHocPhanId: 'lhp-001',
            diemQuaTrinh: 8.0,
            diemCuoiKy: 7.5,
            diemTongKet: 7.7,
        );

        $this->assertEquals('diem-001', $entity->id);
        $this->assertEquals(8.0, $entity->diemQuaTrinh);
        $this->assertEquals(7.7, $entity->diemTongKet);
    }

    public function test_diem_is_passing(): void
    {
        $passing = new DiemSinhVienEntity('1', 'sv-1', 'lhp-1', diemTongKet: 6.5);
        $failing = new DiemSinhVienEntity('2', 'sv-2', 'lhp-2', diemTongKet: 4.5);
        $noGrade = new DiemSinhVienEntity('3', 'sv-3', 'lhp-3');

        $this->assertTrue($passing->isPassing());
        $this->assertFalse($failing->isPassing());
        $this->assertFalse($noGrade->isPassing());
    }

    public function test_diem_is_complete(): void
    {
        $complete = new DiemSinhVienEntity('1', 'sv-1', 'lhp-1', diemTongKet: 7.0);
        $incomplete = new DiemSinhVienEntity('2', 'sv-2', 'lhp-2');

        $this->assertTrue($complete->isComplete());
        $this->assertFalse($incomplete->isComplete());
    }

    public function test_diem_can_edit(): void
    {
        $editable = new DiemSinhVienEntity('1', 'sv-1', 'lhp-1', isLocked: false);
        $locked = new DiemSinhVienEntity('2', 'sv-2', 'lhp-2', isLocked: true);

        $this->assertTrue($editable->canEdit());
        $this->assertFalse($locked->canEdit());
    }

    public function test_diem_get_status(): void
    {
        $locked = new DiemSinhVienEntity('1', 'sv-1', 'lhp-1', diemTongKet: 7.0, isLocked: true);
        $complete = new DiemSinhVienEntity('2', 'sv-2', 'lhp-2', diemTongKet: 7.0);
        $incomplete = new DiemSinhVienEntity('3', 'sv-3', 'lhp-3');

        $this->assertEquals('Đã khóa', $locked->getStatus());
        $this->assertEquals('Hoàn thành', $complete->getStatus());
        $this->assertEquals('Chưa nhập', $incomplete->getStatus());
    }

    public function test_diem_calculate_total(): void
    {
        $entity = new DiemSinhVienEntity(
            '1', 'sv-1', 'lhp-1',
            diemQuaTrinh: 8.0,
            diemThucHanh: 7.0,
            diemCuoiKy: 6.0,
        );

        // Default weights: 30% process, 20% practical, 50% final
        $expected = (8.0 * 0.3) + (7.0 * 0.2) + (6.0 * 0.5); // 2.4 + 1.4 + 3.0 = 6.8
        $this->assertEquals(6.8, $entity->calculateTotal());
    }

    // ==================== TAI LIEU ENTITY ====================

    public function test_tai_lieu_entity_creation(): void
    {
        $entity = new TaiLieuEntity(
            id: 'doc-001',
            lopHocPhanId: 'lhp-001',
            tenTaiLieu: 'Bài giảng Chương 1',
            mimeType: 'application/pdf',
            fileSize: 1048576, // 1MB
        );

        $this->assertEquals('doc-001', $entity->id);
        $this->assertEquals('Bài giảng Chương 1', $entity->tenTaiLieu);
    }

    public function test_tai_lieu_get_file_size_formatted(): void
    {
        $kb = new TaiLieuEntity('1', 'lhp-1', 'Doc', fileSize: 500);
        $mb = new TaiLieuEntity('2', 'lhp-2', 'Doc', fileSize: 1048576);
        $gb = new TaiLieuEntity('3', 'lhp-3', 'Doc', fileSize: 1073741824);

        $this->assertEquals('500 B', $kb->getFileSizeFormatted());
        $this->assertEquals('1 MB', $mb->getFileSizeFormatted());
        $this->assertEquals('1 GB', $gb->getFileSizeFormatted());
    }

    public function test_tai_lieu_is_image(): void
    {
        $image = new TaiLieuEntity('1', 'lhp-1', 'Ảnh', mimeType: 'image/png');
        $pdf = new TaiLieuEntity('2', 'lhp-2', 'PDF', mimeType: 'application/pdf');

        $this->assertTrue($image->isImage());
        $this->assertFalse($pdf->isImage());
    }

    public function test_tai_lieu_is_pdf(): void
    {
        $pdf = new TaiLieuEntity('1', 'lhp-1', 'PDF', mimeType: 'application/pdf');
        $doc = new TaiLieuEntity('2', 'lhp-2', 'Doc', mimeType: 'application/msword');

        $this->assertTrue($pdf->isPdf());
        $this->assertFalse($doc->isPdf());
    }

    public function test_tai_lieu_is_downloadable(): void
    {
        $withPath = new TaiLieuEntity('1', 'lhp-1', 'Doc', filePath: '/path/to/file');
        $withUrl = new TaiLieuEntity('2', 'lhp-2', 'Doc', fileUrl: 'https://example.com/file');
        $noFile = new TaiLieuEntity('3', 'lhp-3', 'Doc');

        $this->assertTrue($withPath->isDownloadable());
        $this->assertTrue($withUrl->isDownloadable());
        $this->assertFalse($noFile->isDownloadable());
    }
}
