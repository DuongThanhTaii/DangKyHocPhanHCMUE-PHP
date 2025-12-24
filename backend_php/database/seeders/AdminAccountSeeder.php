<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminAccountSeeder extends Seeder
{
    /**
     * Seed admin account for Phòng CNTT
     */
    public function run(): void
    {
        $this->command->info('🔐 Creating Phòng CNTT admin account...');

        // Check if account already exists
        $existing = DB::table('tai_khoan')->where('ten_dang_nhap', 'p.cntt')->first();
        
        if ($existing) {
            $this->command->warn('  Account p.cntt already exists, skipping...');
            return;
        }

        DB::table('tai_khoan')->insert([
            'id' => (string) Str::uuid(),
            'ten_dang_nhap' => 'p.cntt',
            'mat_khau' => Hash::make('123456'),
            'loai_tai_khoan' => 'admin_system',
        ]);

        $this->command->info('  ✅ Created account: p.cntt (admin_system)');
    }
}
