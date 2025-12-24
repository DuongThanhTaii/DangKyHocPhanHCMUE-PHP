<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates tables for Dynamic RBAC (Role-Based Access Control):
     * - roles: System roles (sinh_vien, admin_system, etc.)
     * - api_permissions: API endpoints that can be protected
     * - api_role_permissions: Mapping roles to API permissions (many-to-many)
     */
    public function up(): void
    {
        // 1. Roles table
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();     // 'sinh_vien', 'admin_system'
            $table->string('name', 100);               // 'Sinh Viên', 'Admin Hệ Thống'
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false); // TRUE = built-in, không xóa được
            $table->timestamps();
        });

        // 2. API Permissions table
        Schema::create('api_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('route_name', 100)->nullable()->unique(); // 'sv.dang-ky-hoc-phan'
            $table->string('route_path', 255);         // '/api/sv/dang-ky-hoc-phan'
            $table->string('method', 10);              // 'GET', 'POST', 'PUT', 'DELETE'
            $table->string('description', 255)->nullable();
            $table->string('module', 50)->nullable();  // 'SinhVien', 'PDT', 'Auth'
            $table->boolean('is_public')->default(false); // TRUE = không cần auth
            $table->timestamps();
            
            // Composite unique: same path + method = same endpoint
            $table->unique(['route_path', 'method']);
        });

        // 3. Role-Permission mapping (many-to-many with extra fields)
        Schema::create('api_role_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('api_permission_id');
            $table->uuid('role_id');
            $table->boolean('is_enabled')->default(true); // Toggle bật/tắt động
            $table->uuid('granted_by')->nullable();       // Admin đã gán
            $table->timestamp('granted_at')->nullable();
            $table->timestamps();
            
            $table->foreign('api_permission_id')
                ->references('id')
                ->on('api_permissions')
                ->onDelete('cascade');
                
            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');
                
            // One role can only have one entry per API
            $table->unique(['api_permission_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_role_permissions');
        Schema::dropIfExists('api_permissions');
        Schema::dropIfExists('roles');
    }
};
