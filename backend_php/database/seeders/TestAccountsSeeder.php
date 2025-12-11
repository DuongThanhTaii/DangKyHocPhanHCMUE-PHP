<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Infrastructure\Auth\Persistence\Models\TaiKhoan;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;

class TestAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('123456');

        $accounts = [
            [
                'username' => 'test_sv',
                'role' => 'sinh_vien',
                'name' => 'Nguyen Van Sinh Vien',
                'email' => 'test_sv@example.com',
            ],
            [
                'username' => 'test_gv',
                'role' => 'giang_vien',
                'name' => 'Tran Thi Giang Vien',
                'email' => 'test_gv@example.com',
            ],
            [
                'username' => 'test_admin',
                'role' => 'phong_dao_tao',
                'name' => 'Phong Dao Tao',
                'email' => 'admin@example.com',
            ],
            [
                'username' => '49.01.104.145', // Duplicated to ensure it works if missing or fix existing
                'role' => 'sinh_vien',
                'name' => 'Nguyen Van A',
                'email' => 'student@hcmue.edu.vn',
            ]
        ];

        foreach ($accounts as $acc) {
            // Check if exists to avoid dupes if re-run
            $taiKhoan = TaiKhoan::where('ten_dang_nhap', $acc['username'])->first();

            if (!$taiKhoan) {
                $taiKhoan = TaiKhoan::create([
                    'id' => (string) Str::uuid(),
                    'ten_dang_nhap' => $acc['username'],
                    'mat_khau' => $password,
                    'loai_tai_khoan' => $acc['role'],
                    'trang_thai_hoat_dong' => true,
                    'ngay_tao' => now(),
                    'updated_at' => now(),
                ]);
                $this->command->info("Created Account: {$acc['username']}");
            } else {
                // Update password to ensure we can login
                $taiKhoan->update(['mat_khau' => $password]);
                $this->command->info("Updated Account Password: {$acc['username']}");
            }

            // Create or Update Profile
            $profile = UserProfile::where('tai_khoan_id', $taiKhoan->id)->first();
            if (!$profile) {
                UserProfile::create([
                    'id' => (string) Str::uuid(),
                    'tai_khoan_id' => $taiKhoan->id,
                    'ho_ten' => $acc['name'],
                    'email' => $acc['email'],
                    'ma_nhan_vien' => strtoupper($acc['username']), // Just for mock
                ]);
                $this->command->info("Created Profile for: {$acc['username']}");
            }
        }
    }
}
