<?php

namespace Tests\Unit\RBAC;

use PHPUnit\Framework\TestCase;
use App\Domain\RBAC\Entities\RoleEntity;
use App\Domain\RBAC\Entities\ApiPermissionEntity;
use App\Domain\RBAC\Entities\ApiRolePermissionEntity;

/**
 * Unit Tests for RBAC Mapper entities
 */
class MapperTest extends TestCase
{
    public function test_role_entity_to_array_returns_correct_format(): void
    {
        $entity = new RoleEntity(
            id: 'uuid-role-1',
            code: 'admin',
            name: 'Administrator',
            description: 'Full access',
            isSystem: true,
        );

        $result = $entity->toArray();

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('isSystem', $result);
        $this->assertEquals('admin', $result['code']);
        $this->assertTrue($result['isSystem']);
    }

    public function test_api_permission_entity_to_array_returns_correct_format(): void
    {
        $entity = new ApiPermissionEntity(
            id: 'uuid-perm-1',
            routePath: '/api/pdt/mon-hoc',
            method: 'GET',
            isPublic: false,
        );

        $result = $entity->toArray();

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('routePath', $result);
        $this->assertArrayHasKey('method', $result);
        $this->assertArrayHasKey('isPublic', $result);
        $this->assertEquals('/api/pdt/mon-hoc', $result['routePath']);
        $this->assertEquals('GET', $result['method']);
    }

    public function test_api_role_permission_entity_to_array_returns_correct_format(): void
    {
        $entity = new ApiRolePermissionEntity(
            id: 'uuid-rp-1',
            apiPermissionId: 'uuid-perm-1',
            roleId: 'uuid-role-1',
            isEnabled: true,
        );

        $result = $entity->toArray();

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('roleId', $result);
        $this->assertArrayHasKey('apiPermissionId', $result);
        $this->assertArrayHasKey('isEnabled', $result);
        $this->assertTrue($result['isEnabled']);
    }

    public function test_role_entity_is_admin_system(): void
    {
        $adminRole = new RoleEntity(
            id: 'uuid-1',
            code: 'admin_system',
            name: 'Admin System',
            isSystem: true,
        );

        $userRole = new RoleEntity(
            id: 'uuid-2',
            code: 'sinh_vien',
            name: 'Sinh viên',
            isSystem: false,
        );

        $this->assertTrue($adminRole->isSystemRole());
        $this->assertTrue($adminRole->isAdminSystem());
        $this->assertFalse($userRole->isSystemRole());
        $this->assertFalse($userRole->isAdminSystem());
    }

    public function test_api_permission_entity_matches_route(): void
    {
        $entity = new ApiPermissionEntity(
            id: 'uuid-perm-1',
            routePath: '/api/pdt/mon-hoc',
            method: 'GET',
            isPublic: false,
        );

        $this->assertTrue($entity->matchesRoute('/api/pdt/mon-hoc', 'GET'));
        $this->assertFalse($entity->matchesRoute('/api/pdt/mon-hoc', 'POST'));
        $this->assertFalse($entity->matchesRoute('/api/pdt/giang-vien', 'GET'));
    }
}
