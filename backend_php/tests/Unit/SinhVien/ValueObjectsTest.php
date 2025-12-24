<?php

namespace Tests\Unit\SinhVien;

use PHPUnit\Framework\TestCase;
use App\Domain\SinhVien\ValueObjects\TrangThaiDangKy;
use App\Domain\SinhVien\ValueObjects\MaSoSinhVien;
use InvalidArgumentException;

/**
 * Unit Tests for SinhVien Value Objects
 */
class ValueObjectsTest extends TestCase
{
    // ==================== TRANG THAI DANG KY ====================

    public function test_trang_thai_accepts_valid_status(): void
    {
        $status = new TrangThaiDangKy('da_dang_ky');
        $this->assertEquals('da_dang_ky', $status->value());
        $this->assertEquals('Đã đăng ký', $status->label());
    }

    public function test_trang_thai_normalizes_case(): void
    {
        $status = new TrangThaiDangKy('DA_DANG_KY');
        $this->assertEquals('da_dang_ky', $status->value());
    }

    public function test_trang_thai_rejects_invalid_status(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TrangThaiDangKy('invalid_status');
    }

    public function test_trang_thai_is_active_for_registered(): void
    {
        $statuses = ['da_dang_ky', 'cho_duyet', 'da_duyet', 'cho_thanh_toan', 'da_thanh_toan', 'completed'];
        foreach ($statuses as $s) {
            $status = new TrangThaiDangKy($s);
            $this->assertTrue($status->isActive(), "Status {$s} should be active");
        }
    }

    public function test_trang_thai_is_not_active_for_cancelled(): void
    {
        $status = new TrangThaiDangKy('da_huy');
        $this->assertFalse($status->isActive());
        $this->assertTrue($status->isCancelled());
    }

    public function test_trang_thai_is_pending_payment(): void
    {
        $status = new TrangThaiDangKy('cho_thanh_toan');
        $this->assertTrue($status->isPendingPayment());
    }

    public function test_trang_thai_is_completed(): void
    {
        $paid = new TrangThaiDangKy('da_thanh_toan');
        $completed = new TrangThaiDangKy('completed');
        
        $this->assertTrue($paid->isCompleted());
        $this->assertTrue($completed->isCompleted());
    }

    public function test_trang_thai_static_factories(): void
    {
        $this->assertEquals('da_dang_ky', TrangThaiDangKy::daDangKy()->value());
        $this->assertEquals('da_huy', TrangThaiDangKy::daHuy()->value());
        $this->assertEquals('da_duyet', TrangThaiDangKy::daDuyet()->value());
    }

    public function test_trang_thai_equals(): void
    {
        $s1 = new TrangThaiDangKy('da_dang_ky');
        $s2 = new TrangThaiDangKy('da_dang_ky');
        $s3 = new TrangThaiDangKy('da_huy');

        $this->assertTrue($s1->equals($s2));
        $this->assertFalse($s1->equals($s3));
    }

    // ==================== MA SO SINH VIEN ====================

    public function test_mssv_accepts_valid_id(): void
    {
        $mssv = new MaSoSinhVien('49.01.104.123');
        $this->assertEquals('49.01.104.123', $mssv->value());
    }

    public function test_mssv_trims_whitespace(): void
    {
        $mssv = new MaSoSinhVien('  49.01.104.123  ');
        $this->assertEquals('49.01.104.123', $mssv->value());
    }

    public function test_mssv_rejects_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MaSoSinhVien('');
    }

    public function test_mssv_rejects_too_short(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MaSoSinhVien('12');
    }

    public function test_mssv_get_khoa_hoc(): void
    {
        $mssv = new MaSoSinhVien('49.01.104.123');
        $this->assertEquals('49', $mssv->getKhoaHoc());
    }

    public function test_mssv_get_ma_khoa(): void
    {
        $mssv = new MaSoSinhVien('49.01.104.123');
        $this->assertEquals('01', $mssv->getMaKhoa());
    }

    public function test_mssv_get_ma_nganh(): void
    {
        $mssv = new MaSoSinhVien('49.01.104.123');
        $this->assertEquals('104', $mssv->getMaNganh());
    }

    public function test_mssv_is_structured_format(): void
    {
        $valid = new MaSoSinhVien('49.01.104.123');
        $invalid = new MaSoSinhVien('SV001');

        $this->assertTrue($valid->isStructuredFormat());
        $this->assertFalse($invalid->isStructuredFormat());
    }

    public function test_mssv_equals(): void
    {
        $m1 = new MaSoSinhVien('49.01.104.123');
        $m2 = new MaSoSinhVien('49.01.104.123');
        $m3 = new MaSoSinhVien('49.01.104.456');

        $this->assertTrue($m1->equals($m2));
        $this->assertFalse($m1->equals($m3));
    }
}
