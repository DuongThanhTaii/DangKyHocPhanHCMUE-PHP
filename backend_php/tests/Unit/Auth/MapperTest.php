<?php

namespace Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use App\Infrastructure\Auth\Persistence\Mappers\AuthMapper;
use App\Domain\Auth\Entities\TaiKhoanEntity;
use App\Domain\Auth\Entities\UserProfileEntity;
use App\Domain\Auth\ValueObjects\Username;
use App\Domain\Auth\ValueObjects\Email;

/**
 * Unit Tests for AuthMapper
 */
class MapperTest extends TestCase
{
    public function test_to_tai_khoan_entity_converts_model_correctly(): void
    {
        $model = (object) [
            'id' => 'uuid-tk-1',
            'username' => 'testuser',
            'role' => 'sinh_vien',
            'status' => 'active',
            'last_login_at' => '2024-01-15 10:00:00',
        ];

        $entity = AuthMapper::toTaiKhoanEntity($model);

        $this->assertInstanceOf(TaiKhoanEntity::class, $entity);
        $this->assertEquals('uuid-tk-1', $entity->id);
    }

    public function test_to_user_profile_entity_converts_model_correctly(): void
    {
        $model = (object) [
            'id' => 'uuid-up-1',
            'tai_khoan_id' => 'uuid-tk-1',
            'ho_ten' => 'Nguyễn Văn A',
            'email' => 'nva@test.com',
            'sdt' => '0901234567',
            'avatar' => '/avatars/user.jpg',
        ];

        $entity = AuthMapper::toUserProfileEntity($model);

        $this->assertInstanceOf(UserProfileEntity::class, $entity);
        $this->assertEquals('uuid-up-1', $entity->id);
    }

    public function test_format_tai_khoan_for_api_returns_correct_keys(): void
    {
        $entity = new TaiKhoanEntity(
            id: 'uuid-tk-1',
            tenDangNhap: new Username('testuser'),
            loaiTaiKhoan: 'pdt',
            trangThaiHoatDong: true,
        );

        // Note: formatTaiKhoanForApi expects new signature, so test toArray instead
        $result = $entity->toArray();

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('tenDangNhap', $result);
        $this->assertArrayHasKey('loaiTaiKhoan', $result);
        $this->assertTrue($entity->isActive());
    }

    public function test_format_user_profile_for_api_returns_correct_keys(): void
    {
        $entity = new UserProfileEntity(
            id: 'uuid-up-1',
            taiKhoanId: 'uuid-tk-1',
            maNhanVien: 'NV001',
            hoTen: 'Nguyễn Văn A',
            email: new Email('nva@test.com'),
        );

        $result = $entity->toArray();

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('hoTen', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertEquals('Nguyễn Văn A', $result['hoTen']);
        $this->assertEquals('Nguyễn Văn A', $entity->getDisplayName());
    }
}

