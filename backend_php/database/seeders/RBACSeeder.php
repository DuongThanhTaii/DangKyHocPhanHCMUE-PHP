<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\RBAC\Persistence\Models\Role;
use App\Infrastructure\RBAC\Persistence\Models\ApiPermission;
use App\Infrastructure\RBAC\Persistence\Models\ApiRolePermission;
use App\Infrastructure\RBAC\Persistence\Repositories\EloquentRBACRepository;
use Illuminate\Support\Str;

class RBACSeeder extends Seeder
{
    /**
     * Seed RBAC tables with:
     * 1. System roles
     * 2. Sync API permissions from routes
     * 3. Default role-permission mappings based on current middleware
     */
    public function run(): void
    {
        $this->command->info('🔐 Seeding RBAC tables...');

        // 1. Create system roles
        $this->seedRoles();

        // 2. Sync API permissions from Laravel routes
        $this->syncApiPermissions();

        // 3. Seed default role-permission mappings
        $this->seedDefaultMappings();

        $this->command->info('✅ RBAC seeding completed!');
    }

    private function seedRoles(): void
    {
        $this->command->info('  📋 Creating system roles...');

        $roles = [
            ['code' => 'admin_system', 'name' => 'Admin Hệ Thống', 'description' => 'Phòng CNTT - Full quyền', 'is_system' => true],
            ['code' => 'phong_dao_tao', 'name' => 'Phòng Đào Tạo', 'description' => 'Quản lý đào tạo', 'is_system' => true],
            ['code' => 'sinh_vien', 'name' => 'Sinh Viên', 'description' => 'Sinh viên đại học', 'is_system' => true],
            ['code' => 'giang_vien', 'name' => 'Giảng Viên', 'description' => 'Giảng viên', 'is_system' => true],
            ['code' => 'truong_khoa', 'name' => 'Trưởng Khoa', 'description' => 'Trưởng khoa', 'is_system' => true],
            ['code' => 'tro_ly_khoa', 'name' => 'Trợ Lý Khoa', 'description' => 'Trợ lý khoa', 'is_system' => true],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['code' => $roleData['code']],
                array_merge($roleData, ['id' => (string) Str::uuid()])
            );
        }

        $this->command->info('    Created ' . count($roles) . ' roles');
    }

    private function syncApiPermissions(): void
    {
        $this->command->info('  🔄 Syncing API permissions from routes...');

        $repo = new EloquentRBACRepository();
        $count = $repo->syncRoutesFromRouter();

        $this->command->info("    Synced {$count} API permissions");
    }

    private function seedDefaultMappings(): void
    {
        $this->command->info('  🔗 Creating default role-permission mappings...');

        // Define default mappings based on current middleware configuration
        $mappings = [
            // SinhVien routes -> sinh_vien role
            'sinh_vien' => ['/api/sv/%'],
            
            // GiangVien routes -> giang_vien role  
            'giang_vien' => ['/api/gv/%'],
            
            // PDT routes -> phong_dao_tao role
            'phong_dao_tao' => ['/api/pdt/%', '/api/bao-cao/%'],
            
            // TLK routes -> tro_ly_khoa role
            'tro_ly_khoa' => ['/api/tlk/%'],
            
            // TK routes -> truong_khoa role
            'truong_khoa' => ['/api/tk/%'],
            
            // Common routes (authenticated users) -> all roles
            '*' => ['/api/hoc-ky-hien-hanh', '/api/hien-hanh', '/api/dm/%', '/api/hoc-ky/%'],
        ];

        $count = 0;

        foreach ($mappings as $roleCode => $patterns) {
            if ($roleCode === '*') {
                // Apply to all roles
                $roles = Role::all();
            } else {
                $roles = Role::where('code', $roleCode)->get();
            }

            foreach ($roles as $role) {
                foreach ($patterns as $pattern) {
                    // Convert % wildcard to SQL LIKE
                    $permissions = ApiPermission::where('route_path', 'LIKE', str_replace('%', '%', $pattern))->get();
                    
                    foreach ($permissions as $permission) {
                        ApiRolePermission::firstOrCreate(
                            ['role_id' => $role->id, 'api_permission_id' => $permission->id],
                            [
                                'id' => (string) Str::uuid(),
                                'is_enabled' => true,
                                'granted_at' => now(),
                            ]
                        );
                        $count++;
                    }
                }
            }
        }

        $this->command->info("    Created {$count} role-permission mappings");
    }
}
