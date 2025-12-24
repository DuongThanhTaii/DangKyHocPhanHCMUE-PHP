<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;

// Endpoint: POST /api/auth/login
Route::post('/auth/login', [AuthController::class, 'login']);

Route::group(['middleware' => 'api', 'prefix' => 'auth'], function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('change-password', [AuthController::class, 'changePassword']);
});

Route::group(['prefix' => 'auth'], function () {
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

Route::group(['middleware' => ['auth:api', 'pdt'], 'prefix' => 'pdt'], function () {
    // Sinh Vien CRUD
    Route::get('sinh-vien', [\App\Http\Controllers\Api\Pdt\StudentController::class, 'index']);
    Route::post('sinh-vien', [\App\Http\Controllers\Api\Pdt\StudentController::class, 'store']);
    Route::put('sinh-vien/{id}', [\App\Http\Controllers\Api\Pdt\StudentController::class, 'update']);
    Route::delete('sinh-vien/{id}', [\App\Http\Controllers\Api\Pdt\StudentController::class, 'destroy']);
    Route::post('sinh-vien/import', [\App\Http\Controllers\Api\Pdt\StudentController::class, 'import']);

    // HocKy & KyPhase Management
    Route::post('quan-ly-hoc-ky/hoc-ky-hien-hanh', [\App\Http\Controllers\Api\Pdt\HocKyController::class, 'setHocKyHienHanh']);
    Route::post('quan-ly-hoc-ky/ky-phase/bulk', [\App\Http\Controllers\Api\Pdt\HocKyController::class, 'createBulkKyPhase']);
    Route::get('quan-ly-hoc-ky/ky-phase/{hocKyId}', [\App\Http\Controllers\Api\Pdt\HocKyController::class, 'getPhasesByHocKy']);

    // DeXuatHocPhan Management
    Route::get('de-xuat-hoc-phan', [\App\Http\Controllers\Api\Pdt\DeXuatHocPhanController::class, 'index']);
    Route::post('de-xuat-hoc-phan', [\App\Http\Controllers\Api\Pdt\DeXuatHocPhanController::class, 'store']);
    Route::post('de-xuat-hoc-phan/duyet', [\App\Http\Controllers\Api\Pdt\DeXuatHocPhanController::class, 'duyet']);
    Route::post('de-xuat-hoc-phan/tu-choi', [\App\Http\Controllers\Api\Pdt\DeXuatHocPhanController::class, 'tuChoi']);

    // DotDangKy Management
    Route::get('dot-dang-ky', [\App\Http\Controllers\Api\Pdt\DotDangKyController::class, 'index']);
    Route::get('dot-dang-ky/{hocKyId}', [\App\Http\Controllers\Api\Pdt\DotDangKyController::class, 'getByHocKy']);
    Route::post('dot-ghi-danh/update', [\App\Http\Controllers\Api\Pdt\DotDangKyController::class, 'update']);

    // Khoa Management
    Route::get('khoa', [\App\Http\Controllers\Api\Pdt\KhoaController::class, 'index']);

    // PhongHoc Management
    Route::get('phong-hoc/available', [\App\Http\Controllers\Api\Pdt\PhongHocController::class, 'available']);
    Route::get('phong-hoc/khoa/{khoaId}', [\App\Http\Controllers\Api\Pdt\PhongHocController::class, 'byKhoa']);
    Route::post('phong-hoc/assign', [\App\Http\Controllers\Api\Pdt\PhongHocController::class, 'assign']);
    Route::post('phong-hoc/unassign', [\App\Http\Controllers\Api\Pdt\PhongHocController::class, 'unassign']);

    // ChinhSachTinChi CRUD
    Route::get('chinh-sach-tin-chi', [\App\Http\Controllers\Api\Pdt\ChinhSachTinChiController::class, 'index']);
    Route::get('chinh-sach-tin-chi/{id}', [\App\Http\Controllers\Api\Pdt\ChinhSachTinChiController::class, 'show']);
    Route::post('chinh-sach-tin-chi', [\App\Http\Controllers\Api\Pdt\ChinhSachTinChiController::class, 'store']);
    Route::put('chinh-sach-tin-chi/{id}', [\App\Http\Controllers\Api\Pdt\ChinhSachTinChiController::class, 'update']);
    Route::delete('chinh-sach-tin-chi/{id}', [\App\Http\Controllers\Api\Pdt\ChinhSachTinChiController::class, 'destroy']);

    // HocPhi Management
    Route::post('hoc-phi/tinh-toan-hang-loat', [\App\Http\Controllers\Api\Pdt\HocPhiController::class, 'tinhToanHangLoat']);

    // MonHoc CRUD
    Route::get('mon-hoc', [\App\Http\Controllers\Api\Pdt\MonHocController::class, 'index']);
    Route::post('mon-hoc', [\App\Http\Controllers\Api\Pdt\MonHocController::class, 'store']);
    Route::put('mon-hoc/{id}', [\App\Http\Controllers\Api\Pdt\MonHocController::class, 'update']);
    Route::delete('mon-hoc/{id}', [\App\Http\Controllers\Api\Pdt\MonHocController::class, 'destroy']);

    // GiangVien CRUD
    Route::get('giang-vien', [\App\Http\Controllers\Api\Pdt\GiangVienController::class, 'index']);
    Route::post('giang-vien', [\App\Http\Controllers\Api\Pdt\GiangVienController::class, 'store']);
    Route::put('giang-vien/{id}', [\App\Http\Controllers\Api\Pdt\GiangVienController::class, 'update']);
    Route::delete('giang-vien/{id}', [\App\Http\Controllers\Api\Pdt\GiangVienController::class, 'destroy']);

    // Demo Tools
    Route::post('demo/toggle-phase', [\App\Http\Controllers\Api\Pdt\DemoController::class, 'togglePhase']);
    Route::post('demo/reset-data', [\App\Http\Controllers\Api\Pdt\DemoController::class, 'resetData']);
    Route::post('ky-phase/toggle', [\App\Http\Controllers\Api\Pdt\DemoController::class, 'togglePhase']); // Alias
});

// Common API endpoints (require authentication, all roles)
Route::group(['middleware' => 'auth:api'], function () {
    // Current semester
    Route::get('hoc-ky-hien-hanh', [\App\Http\Controllers\Api\Common\CommonController::class, 'getHocKyHienHanh']);
    Route::get('hien-hanh', [\App\Http\Controllers\Api\Common\CommonController::class, 'getHocKyHienHanh']); // Alias
    Route::get('hoc-ky/dates', [\App\Http\Controllers\Api\Common\CommonController::class, 'getHocKyDates']);
    Route::get('hoc-ky-nien-khoa', [\App\Http\Controllers\Api\Common\CommonController::class, 'getHocKyNienKhoa']);

    // Reference data (danh mục) - Note: specific routes before generic ones
    Route::get('dm/khoa', [\App\Http\Controllers\Api\Common\CommonController::class, 'getDanhSachKhoa']);
    Route::get('dm/nganh/chua-co-chinh-sach', [\App\Http\Controllers\Api\Common\CommonController::class, 'getNganhChuaCoChinhSach']);
    Route::get('dm/nganh', [\App\Http\Controllers\Api\Common\CommonController::class, 'getDanhSachNganh']);
});

// Public config endpoint (no auth required)
Route::get('config/tiet-hoc', [\App\Http\Controllers\Api\Common\CommonController::class, 'getConfigTietHoc']);

// BaoCao (Statistics/Reports) endpoints - require authentication
Route::group(['middleware' => 'auth:api', 'prefix' => 'bao-cao'], function () {
    Route::get('overview', [\App\Http\Controllers\Api\Pdt\BaoCaoController::class, 'overview']);
    Route::get('dk-theo-khoa', [\App\Http\Controllers\Api\Pdt\BaoCaoController::class, 'dangKyTheoKhoa']);
    Route::get('dk-theo-nganh', [\App\Http\Controllers\Api\Pdt\BaoCaoController::class, 'dangKyTheoNganh']);
    Route::get('tai-giang-vien', [\App\Http\Controllers\Api\Pdt\BaoCaoController::class, 'taiGiangVien']);
});

// Student (SinhVien) endpoints - require auth + sinh_vien role
Route::group(['middleware' => ['auth:api', 'sinh_vien'], 'prefix' => 'sv'], function () {
    // Profile
    Route::get('profile', [\App\Http\Controllers\Api\SinhVien\SinhVienController::class, 'getProfile']);

    // Documents (existing - from SinhVienController)
    Route::get('lop-hoc-phan/{id}/tai-lieu', [\App\Http\Controllers\Api\SinhVien\SinhVienController::class, 'getTaiLieu']);
    Route::get('lop-hoc-phan/{id}/tai-lieu/{doc_id}/download', [\App\Http\Controllers\Api\SinhVien\SinhVienController::class, 'downloadTaiLieu']);

    // Enrollment (Ghi Danh)
    Route::get('check-ghi-danh', [\App\Http\Controllers\Api\SinhVien\GhiDanhController::class, 'checkGhiDanh']);
    Route::get('mon-hoc-ghi-danh', [\App\Http\Controllers\Api\SinhVien\GhiDanhController::class, 'getMonHocGhiDanh']);
    Route::post('ghi-danh', [\App\Http\Controllers\Api\SinhVien\GhiDanhController::class, 'ghiDanh']);
    Route::delete('ghi-danh/{id}', [\App\Http\Controllers\Api\SinhVien\GhiDanhController::class, 'huyGhiDanh']);
    Route::post('huy-ghi-danh', [\App\Http\Controllers\Api\SinhVien\GhiDanhController::class, 'huyGhiDanhBatch']);
    Route::get('ghi-danh/my', [\App\Http\Controllers\Api\SinhVien\GhiDanhController::class, 'getDanhSachDaGhiDanh']);

    // Course Registration (Đăng Ký Học Phần)
    Route::get('check-phase-dang-ky', [\App\Http\Controllers\Api\SinhVien\DangKyHocPhanController::class, 'checkPhaseDangKy']);
    Route::get('lop-hoc-phan', [\App\Http\Controllers\Api\SinhVien\DangKyHocPhanController::class, 'getLopHocPhan']);
    Route::get('lop-da-dang-ky', [\App\Http\Controllers\Api\SinhVien\DangKyHocPhanController::class, 'getLopDaDangKy']);
    Route::post('dang-ky-hoc-phan', [\App\Http\Controllers\Api\SinhVien\DangKyHocPhanController::class, 'dangKyHocPhan']);
    Route::post('huy-dang-ky-hoc-phan', [\App\Http\Controllers\Api\SinhVien\DangKyHocPhanController::class, 'huyDangKyHocPhan']);
    Route::post('chuyen-lop-hoc-phan', [\App\Http\Controllers\Api\SinhVien\DangKyHocPhanController::class, 'chuyenLopHocPhan']);
    Route::get('lop-hoc-phan/mon-hoc', [\App\Http\Controllers\Api\SinhVien\DangKyHocPhanController::class, 'getLopByMonHoc']);
    Route::get('lich-su-dang-ky', [\App\Http\Controllers\Api\SinhVien\DangKyHocPhanController::class, 'getLichSuDangKy']);
    Route::get('tkb-weekly', [\App\Http\Controllers\Api\SinhVien\DangKyHocPhanController::class, 'getTKBWeekly']);
    Route::get('tra-cuu-hoc-phan', [\App\Http\Controllers\Api\SinhVien\DangKyHocPhanController::class, 'traCuuHocPhan']);
    Route::get('hoc-phi', [\App\Http\Controllers\Api\SinhVien\DangKyHocPhanController::class, 'getHocPhi']);
    Route::get('lop-da-dang-ky/tai-lieu', [\App\Http\Controllers\Api\SinhVien\DangKyHocPhanController::class, 'getTaiLieuLopDaDangKy']);
});

// Payment endpoints
Route::group(['prefix' => 'payment'], function () {
    // Authenticated routes
    Route::middleware('auth:api')->group(function () {
        Route::post('create', [\App\Http\Controllers\Api\Payment\PaymentController::class, 'createPayment']);
        Route::get('status', [\App\Http\Controllers\Api\Payment\PaymentController::class, 'getPaymentStatus']);
    });

    // Public route (IPN callback from payment gateway)
    Route::post('ipn', [\App\Http\Controllers\Api\Payment\PaymentController::class, 'handleIPN']);

    // Demo route (for testing without real gateway - REMOVE IN PRODUCTION)
    Route::post('demo-complete', [\App\Http\Controllers\Api\Payment\PaymentController::class, 'demoComplete']);
});

// Instructor (GiangVien) endpoints - require auth + giang_vien role
Route::group(['middleware' => ['auth:api', 'giang_vien'], 'prefix' => 'gv'], function () {
    // Lop Hoc Phan
    Route::get('lop-hoc-phan', [\App\Http\Controllers\Api\GiangVien\GVController::class, 'getLopHocPhanList']);
    Route::get('lop-hoc-phan/{id}', [\App\Http\Controllers\Api\GiangVien\GVController::class, 'getLopHocPhanDetail']);
    Route::get('lop-hoc-phan/{id}/sinh-vien', [\App\Http\Controllers\Api\GiangVien\GVController::class, 'getLopHocPhanStudents']);
    Route::get('lop-hoc-phan/{id}/diem', [\App\Http\Controllers\Api\GiangVien\GVController::class, 'getGrades']);
    Route::put('lop-hoc-phan/{id}/diem', [\App\Http\Controllers\Api\GiangVien\GVController::class, 'updateGrades']);

    // Tai Lieu (Documents)
    Route::get('lop-hoc-phan/{id}/tai-lieu', [\App\Http\Controllers\Api\GiangVien\GVController::class, 'getTaiLieuList']);
    Route::post('lop-hoc-phan/{id}/tai-lieu/upload', [\App\Http\Controllers\Api\GiangVien\GVController::class, 'uploadTaiLieu']);
    Route::get('lop-hoc-phan/{id}/tai-lieu/{doc_id}', [\App\Http\Controllers\Api\GiangVien\GVController::class, 'getTaiLieuDetail']);
    Route::get('lop-hoc-phan/{id}/tai-lieu/{doc_id}/download', [\App\Http\Controllers\Api\GiangVien\GVController::class, 'downloadTaiLieu']);

    // TKB Weekly
    Route::get('tkb-weekly', [\App\Http\Controllers\Api\GiangVien\GVController::class, 'getTKBWeekly']);
});

// TLK (Tro Ly Khoa) endpoints - require auth + tro_ly_khoa role
Route::group(['middleware' => ['auth:api', 'tro_ly_khoa'], 'prefix' => 'tlk'], function () {
    // Mon Hoc and Giang Vien
    Route::get('mon-hoc', [\App\Http\Controllers\Api\TLK\TLKController::class, 'getMonHoc']);
    Route::get('giang-vien', [\App\Http\Controllers\Api\TLK\TLKController::class, 'getGiangVien']);

    // Lop Hoc Phan
    Route::get('lop-hoc-phan/get-hoc-phan/{hoc_ky_id}', [\App\Http\Controllers\Api\TLK\TLKController::class, 'getHocPhanForSemester']);

    // Phong Hoc
    Route::get('phong-hoc', [\App\Http\Controllers\Api\TLK\TLKController::class, 'getPhongHoc']);
    Route::get('phong-hoc/available', [\App\Http\Controllers\Api\TLK\TLKController::class, 'getAvailablePhongHoc']);

    // De Xuat Hoc Phan
    Route::get('de-xuat-hoc-phan', [\App\Http\Controllers\Api\TLK\TLKController::class, 'getDeXuatHocPhan']);
    Route::post('de-xuat-hoc-phan', [\App\Http\Controllers\Api\TLK\TLKController::class, 'createDeXuatHocPhan']);

    // Thoi Khoa Bieu
    Route::post('thoi-khoa-bieu/batch', [\App\Http\Controllers\Api\TLK\TLKController::class, 'getTKBBatch']);
    Route::post('thoi-khoa-bieu', [\App\Http\Controllers\Api\TLK\TLKController::class, 'xepThoiKhoaBieu']);
});

// TK (Truong Khoa) endpoints - require auth + truong_khoa role
Route::group(['middleware' => ['auth:api', 'truong_khoa'], 'prefix' => 'tk'], function () {
    // De Xuat Hoc Phan
    Route::get('de-xuat-hoc-phan', [\App\Http\Controllers\Api\TK\TKController::class, 'getDeXuatHocPhan']);
    Route::post('de-xuat-hoc-phan/duyet', [\App\Http\Controllers\Api\TK\TKController::class, 'duyetDeXuat']);
    Route::post('de-xuat-hoc-phan/tu-choi', [\App\Http\Controllers\Api\TK\TKController::class, 'tuChoiDeXuat']);
});

// Admin (Phòng CNTT) endpoints - require auth + admin_system role
Route::group(['middleware' => ['auth:api', 'admin_system'], 'prefix' => 'admin'], function () {
    // RBAC Management
    Route::get('roles', [\App\Http\Controllers\Api\Admin\RBACController::class, 'getRoles']);
    Route::get('roles/{id}/permissions', [\App\Http\Controllers\Api\Admin\RBACController::class, 'getRolePermissions']);
    Route::put('roles/{roleId}/permissions/{permissionId}', [\App\Http\Controllers\Api\Admin\RBACController::class, 'togglePermission']);
});
