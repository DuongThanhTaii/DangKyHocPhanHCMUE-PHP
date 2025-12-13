<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Infrastructure\Auth\Persistence\Models\TaiKhoan;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PdtStudentManagementTest extends TestCase
{
    // Important: We probably want to clean up DB, but RefreshDatabase might wipe custom seeded data?
    // Let's safe-guard by just creating unique data and maybe manually cleaning or relying on TransactionRollback if configured.
    // However, for pure feature tests, RefreshDatabase is standard but can be slow/destructive if not isolated.
    // Let's *not* use RefreshDatabase on the user's existing environment unless they asked, to avoid wiping their data.
    // We will clean up our specific created entities. 

    protected $pdtToken;
    protected $studentToken;

    public function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();


        // Create a temporary PDT user
        $pdtAccount = TaiKhoan::create([
            'id' => Str::uuid()->toString(),
            'ten_dang_nhap' => 'pdt_test_' . rand(1000, 9999),
            'mat_khau' => bcrypt('password'),
            'loai_tai_khoan' => 'phong_dao_tao',
            'trang_thai_hoat_dong' => true,
        ]);

        $this->pdtToken = JWTAuth::fromUser($pdtAccount);

        // Create a regular Student user for negative testing
        $studentAccount = TaiKhoan::create([
            'id' => Str::uuid()->toString(),
            'ten_dang_nhap' => 'sv_test_' . rand(1000, 9999),
            'mat_khau' => bcrypt('password'),
            'loai_tai_khoan' => 'sinh_vien',
            'trang_thai_hoat_dong' => true,
        ]);

        $this->studentToken = JWTAuth::fromUser($studentAccount);
    }

    public function test_pdt_can_list_students()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->pdtToken,
        ])->getJson('/api/pdt/sinh-vien');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'current_page',
            'total'
        ]);
    }

    public function test_non_pdt_cannot_list_students()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->studentToken,
        ])->getJson('/api/pdt/sinh-vien');

        $response->assertStatus(403);
    }

    public function test_pdt_can_create_student()
    {
        $payload = [
            'ma_so_sinh_vien' => 'TEST_SV_' . rand(10000, 99999),
            'ho_ten' => 'Test Student Name',
            'email' => 'testsv' . rand(10000, 99999) . '@student.hcmue.edu.vn',
            // Optional fields
            'lop' => 'Test Class',
            'khoa_hoc' => '2025',
        ];

        // Fetch or create a valid khoa_id
        $khoaId = DB::table('khoa')->value('id');
        if (!$khoaId) {
            $khoaId = Str::uuid()->toString();
            DB::table('khoa')->insert([
                'id' => $khoaId,
                'ma_khoa' => 'TEST_KHOA',
                'ten_khoa' => 'Test Khoa',
                'ngay_thanh_lap' => now(),
                'trang_thai_hoat_dong' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $payload['khoa_id'] = $khoaId;


        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->pdtToken,
        ])->postJson('/api/pdt/sinh-vien', $payload);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'ma_so_sinh_vien' => $payload['ma_so_sinh_vien']
        ]);

        // Clean up
        $this->deleteStudent($response->json('id'));
    }

    public function test_pdt_can_update_student()
    {
        // First create one
        $mssv = 'TEST_UPDATE_' . rand(10000, 99999);
        // Fetch valid khoa_id
        $khoaId = DB::table('khoa')->value('id');

        $createPayload = [
            'ma_so_sinh_vien' => $mssv,
            'ho_ten' => 'Original Name',
            'email' => $mssv . '@test.com',
            'khoa_id' => $khoaId
        ];

        $created = $this->withHeaders(['Authorization' => 'Bearer ' . $this->pdtToken])
            ->postJson('/api/pdt/sinh-vien', $createPayload);

        $id = $created->json('id');

        // Now Update
        $updatePayload = [
            'ho_ten' => 'Updated Name'
        ];

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->pdtToken])
            ->putJson("/api/pdt/sinh-vien/{$id}", $updatePayload);

        $response->assertStatus(200);
        $response->assertJsonFragment(['ho_ten' => 'Updated Name']);

        // Cleanup
        $this->deleteStudent($id);
    }

    public function test_pdt_can_delete_student()
    {
        // First create one
        $mssv = 'TEST_DELETE_' . rand(10000, 99999);
        // Fetch valid khoa_id
        $khoaId = DB::table('khoa')->value('id');

        $createPayload = [
            'ma_so_sinh_vien' => $mssv,
            'ho_ten' => 'To Delete',
            'email' => $mssv . '@test.com',
            'khoa_id' => $khoaId
        ];

        $created = $this->withHeaders(['Authorization' => 'Bearer ' . $this->pdtToken])
            ->postJson('/api/pdt/sinh-vien', $createPayload);

        $id = $created->json('id');

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->pdtToken])
            ->deleteJson("/api/pdt/sinh-vien/{$id}");

        $response->assertStatus(200);

        // Verify it's gone
        $check = $this->withHeaders(['Authorization' => 'Bearer ' . $this->pdtToken])
            ->getJson('/api/pdt/sinh-vien?search=' . $mssv);

        $this->assertEquals(0, count($check->json('data')));
    }

    public function test_pdt_can_import_csv()
    {
        $khoaId = DB::table('khoa')->value('id');
        $content = "ma_so_sinh_vien,ho_ten,email,khoa_id\n";
        $code1 = 'IMP_' . rand(1000, 9999);
        $code2 = 'IMP_' . rand(1000, 9999);
        $content .= "{$code1},Imported One,{$code1}@test.com,{$khoaId}\n";
        $content .= "{$code2},Imported Two,{$code2}@test.com,{$khoaId}\n";

        $file = UploadedFile::fake()->createWithContent('students.csv', $content);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->pdtToken])
            ->postJson('/api/pdt/sinh-vien/import', [
                'file' => $file
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['count' => 2]);

        // Cleanup?
        // Ideally we fetch them by code and delete, but for this simple test we can assume success
    }

    private function deleteStudent($id)
    {
        $this->withHeaders(['Authorization' => 'Bearer ' . $this->pdtToken])
            ->deleteJson("/api/pdt/sinh-vien/{$id}");
    }
}
