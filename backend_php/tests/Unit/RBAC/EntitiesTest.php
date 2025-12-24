<?php

namespace Tests\Unit\RBAC;

use PHPUnit\Framework\TestCase;
use App\Domain\RBAC\Entities\RoleEntity;
use App\Domain\RBAC\Entities\ApiPermissionEntity;
use App\Domain\RBAC\Entities\ApiRolePermissionEntity;
use DateTimeImmutable;

/**
 * Unit Tests for RBAC Domain Entities
 */
class EntitiesTest extends TestCase
{
    // ==================== ROLE ENTITY ====================

    public function test_role_entity_creation(): void
    {
        $entity = new RoleEntity(
            id: 'uuid-123',
            code: 'sinh_vien',
            name: 'Sinh Viên',
            description: 'Sinh viên đại học',
            isSystem: true,
        );

        $this->assertEquals('uuid-123', $entity->id);
        $this->assertEquals('sinh_vien', $entity->code);
        $this->assertEquals('Sinh Viên', $entity->name);
        $this->assertTrue($entity->isSystem);
    }

    public function test_role_is_system_role(): void
    {
        $system = new RoleEntity('1', 'admin', 'Admin', null, true);
        $custom = new RoleEntity('2', 'viewer', 'Viewer', null, false);

        $this->assertTrue($system->isSystemRole());
        $this->assertFalse($custom->isSystemRole());
    }

    public function test_role_has_code(): void
    {
        $role = new RoleEntity('1', 'sinh_vien', 'SV');

        $this->assertTrue($role->hasCode('sinh_vien'));
        $this->assertFalse($role->hasCode('giang_vien'));
    }

    public function test_role_is_admin_system(): void
    {
        $admin = new RoleEntity('1', 'admin_system', 'Admin System');
        $pdt = new RoleEntity('2', 'phong_dao_tao', 'PDT');

        $this->assertTrue($admin->isAdminSystem());
        $this->assertFalse($pdt->isAdminSystem());
    }

    public function test_role_to_array(): void
    {
        $role = new RoleEntity(
            id: 'uuid-123',
            code: 'test_role',
            name: 'Test Role',
            description: 'For testing',
            isSystem: false,
        );

        $array = $role->toArray();

        $this->assertEquals('uuid-123', $array['id']);
        $this->assertEquals('test_role', $array['code']);
        $this->assertEquals('Test Role', $array['name']);
        $this->assertFalse($array['isSystem']);
    }

    // ==================== API PERMISSION ENTITY ====================

    public function test_api_permission_entity_creation(): void
    {
        $entity = new ApiPermissionEntity(
            id: 'perm-123',
            routePath: '/api/sv/dang-ky-hoc-phan',
            method: 'POST',
            routeName: 'sv.dang-ky',
            description: 'Đăng ký học phần',
            module: 'SinhVien',
            isPublic: false,
        );

        $this->assertEquals('perm-123', $entity->id);
        $this->assertEquals('/api/sv/dang-ky-hoc-phan', $entity->routePath);
        $this->assertEquals('POST', $entity->method);
        $this->assertEquals('SinhVien', $entity->module);
        $this->assertFalse($entity->isPublic);
    }

    public function test_api_permission_is_public(): void
    {
        $public = new ApiPermissionEntity('1', '/api/config', 'GET', null, null, null, true);
        $private = new ApiPermissionEntity('2', '/api/sv/profile', 'GET', null, null, null, false);

        $this->assertTrue($public->isPublicEndpoint());
        $this->assertFalse($private->isPublicEndpoint());
    }

    public function test_api_permission_matches_route(): void
    {
        $permission = new ApiPermissionEntity('1', '/api/sv/profile', 'GET');

        $this->assertTrue($permission->matchesRoute('/api/sv/profile', 'GET'));
        $this->assertTrue($permission->matchesRoute('/api/sv/profile', 'get')); // case insensitive
        $this->assertFalse($permission->matchesRoute('/api/sv/profile', 'POST'));
        $this->assertFalse($permission->matchesRoute('/api/gv/profile', 'GET'));
    }

    public function test_api_permission_get_endpoint_key(): void
    {
        $permission = new ApiPermissionEntity('1', '/api/sv/dang-ky', 'post');

        $this->assertEquals('POST:/api/sv/dang-ky', $permission->getEndpointKey());
    }

    // ==================== API ROLE PERMISSION ENTITY ====================

    public function test_api_role_permission_entity_creation(): void
    {
        $now = new DateTimeImmutable();
        $entity = new ApiRolePermissionEntity(
            id: 'mapping-123',
            apiPermissionId: 'perm-1',
            roleId: 'role-1',
            isEnabled: true,
            grantedBy: 'admin-user-id',
            grantedAt: $now,
        );

        $this->assertEquals('mapping-123', $entity->id);
        $this->assertEquals('perm-1', $entity->apiPermissionId);
        $this->assertEquals('role-1', $entity->roleId);
        $this->assertTrue($entity->isEnabled);
        $this->assertEquals('admin-user-id', $entity->grantedBy);
    }

    public function test_api_role_permission_is_enabled(): void
    {
        $enabled = new ApiRolePermissionEntity('1', 'p1', 'r1', true);
        $disabled = new ApiRolePermissionEntity('2', 'p2', 'r2', false);

        $this->assertTrue($enabled->isEnabled());
        $this->assertFalse($enabled->isDisabled());
        
        $this->assertFalse($disabled->isEnabled());
        $this->assertTrue($disabled->isDisabled());
    }
}
