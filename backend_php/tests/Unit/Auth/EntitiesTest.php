<?php

namespace Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use App\Domain\Auth\Entities\TaiKhoanEntity;
use App\Domain\Auth\Entities\UserProfileEntity;
use App\Domain\Auth\ValueObjects\Email;
use App\Domain\Auth\ValueObjects\Username;

/**
 * Unit Tests for Auth Domain Entities
 */
class EntitiesTest extends TestCase
{
    // ==================== TAIKHOAN ENTITY ====================

    public function test_taikhoan_entity_creation(): void
    {
        $entity = new TaiKhoanEntity(
            id: 'uuid-123',
            tenDangNhap: new Username('sinhvien001'),
            loaiTaiKhoan: 'sinh_vien',
            trangThaiHoatDong: true,
        );

        $this->assertEquals('uuid-123', $entity->id);
        $this->assertEquals('sinhvien001', $entity->tenDangNhap->value());
        $this->assertEquals('sinh_vien', $entity->loaiTaiKhoan);
        $this->assertTrue($entity->trangThaiHoatDong);
    }

    public function test_taikhoan_is_active(): void
    {
        $active = new TaiKhoanEntity(
            id: '1',
            tenDangNhap: new Username('user1'),
            loaiTaiKhoan: 'sinh_vien',
            trangThaiHoatDong: true,
        );

        $inactive = new TaiKhoanEntity(
            id: '2',
            tenDangNhap: new Username('user2'),
            loaiTaiKhoan: 'sinh_vien',
            trangThaiHoatDong: false,
        );

        $this->assertTrue($active->isActive());
        $this->assertFalse($inactive->isActive());
    }

    public function test_taikhoan_has_role(): void
    {
        $sinhVien = new TaiKhoanEntity(
            id: '1',
            tenDangNhap: new Username('sv001'),
            loaiTaiKhoan: 'sinh_vien',
            trangThaiHoatDong: true,
        );

        $this->assertTrue($sinhVien->hasRole('sinh_vien'));
        $this->assertFalse($sinhVien->hasRole('giang_vien'));
    }

    public function test_taikhoan_is_sinh_vien(): void
    {
        $sv = new TaiKhoanEntity(
            id: '1',
            tenDangNhap: new Username('sv001'),
            loaiTaiKhoan: 'sinh_vien',
            trangThaiHoatDong: true,
        );

        $gv = new TaiKhoanEntity(
            id: '2',
            tenDangNhap: new Username('gv001'),
            loaiTaiKhoan: 'giang_vien',
            trangThaiHoatDong: true,
        );

        $this->assertTrue($sv->isSinhVien());
        $this->assertFalse($gv->isSinhVien());
    }

    public function test_taikhoan_is_giang_vien(): void
    {
        $gv = new TaiKhoanEntity(
            id: '1',
            tenDangNhap: new Username('gv001'),
            loaiTaiKhoan: 'giang_vien',
            trangThaiHoatDong: true,
        );

        $this->assertTrue($gv->isGiangVien());
        $this->assertFalse($gv->isSinhVien());
    }

    public function test_taikhoan_can_access_pdt(): void
    {
        $pdt = new TaiKhoanEntity(
            id: '1',
            tenDangNhap: new Username('pdt001'),
            loaiTaiKhoan: 'pdt',
            trangThaiHoatDong: true,
        );

        $sv = new TaiKhoanEntity(
            id: '2',
            tenDangNhap: new Username('sv001'),
            loaiTaiKhoan: 'sinh_vien',
            trangThaiHoatDong: true,
        );

        $this->assertTrue($pdt->canAccessPDT());
        $this->assertFalse($sv->canAccessPDT());
    }

    public function test_taikhoan_to_array(): void
    {
        $entity = new TaiKhoanEntity(
            id: 'uuid-123',
            tenDangNhap: new Username('testuser'),
            loaiTaiKhoan: 'sinh_vien',
            trangThaiHoatDong: true,
        );

        $array = $entity->toArray();

        $this->assertEquals('uuid-123', $array['id']);
        $this->assertEquals('testuser', $array['tenDangNhap']);
        $this->assertEquals('sinh_vien', $array['loaiTaiKhoan']);
        $this->assertTrue($array['trangThaiHoatDong']);
    }

    // ==================== USER PROFILE ENTITY ====================

    public function test_user_profile_entity_creation(): void
    {
        $entity = new UserProfileEntity(
            id: 'profile-123',
            taiKhoanId: 'account-456',
            maNhanVien: 'NV001',
            hoTen: 'Nguyễn Văn A',
            email: new Email('nva@example.com'),
        );

        $this->assertEquals('profile-123', $entity->id);
        $this->assertEquals('account-456', $entity->taiKhoanId);
        $this->assertEquals('NV001', $entity->maNhanVien);
        $this->assertEquals('Nguyễn Văn A', $entity->hoTen);
        $this->assertEquals('nva@example.com', $entity->email->value());
    }

    public function test_user_profile_get_display_name(): void
    {
        $withName = new UserProfileEntity(
            id: '1',
            taiKhoanId: '1',
            maNhanVien: 'NV001',
            hoTen: 'Nguyễn Văn A',
            email: null,
        );

        $withoutName = new UserProfileEntity(
            id: '2',
            taiKhoanId: '2',
            maNhanVien: 'NV002',
            hoTen: null,
            email: null,
        );

        $this->assertEquals('Nguyễn Văn A', $withName->getDisplayName());
        $this->assertEquals('NV002', $withoutName->getDisplayName());
    }

    public function test_user_profile_has_email(): void
    {
        $withEmail = new UserProfileEntity(
            id: '1',
            taiKhoanId: '1',
            maNhanVien: null,
            hoTen: null,
            email: new Email('test@example.com'),
        );

        $withoutEmail = new UserProfileEntity(
            id: '2',
            taiKhoanId: '2',
            maNhanVien: null,
            hoTen: null,
            email: null,
        );

        $this->assertTrue($withEmail->hasEmail());
        $this->assertFalse($withoutEmail->hasEmail());
    }

    public function test_user_profile_to_array(): void
    {
        $entity = new UserProfileEntity(
            id: 'profile-123',
            taiKhoanId: 'account-456',
            maNhanVien: 'NV001',
            hoTen: 'Test User',
            email: new Email('test@example.com'),
        );

        $array = $entity->toArray();

        $this->assertEquals('profile-123', $array['id']);
        $this->assertEquals('account-456', $array['taiKhoanId']);
        $this->assertEquals('NV001', $array['maNhanVien']);
        $this->assertEquals('Test User', $array['hoTen']);
        $this->assertEquals('test@example.com', $array['email']);
    }
}
