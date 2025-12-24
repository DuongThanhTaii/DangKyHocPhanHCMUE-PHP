<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// PDT Module bindings
use App\Domain\Pdt\Repositories\BaoCaoRepositoryInterface;
use App\Infrastructure\Pdt\Persistence\Repositories\EloquentBaoCaoRepository;
use App\Domain\Pdt\Repositories\DotDangKyRepositoryInterface;
use App\Infrastructure\Pdt\Persistence\Repositories\EloquentDotDangKyRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     * 
     * Bindings for Dependency Injection (Clean Architecture)
     */
    public function register(): void
    {
        // =============================================
        // PDT Module - Báo cáo thống kê
        // =============================================
        $this->app->bind(
            BaoCaoRepositoryInterface::class,
            EloquentBaoCaoRepository::class
        );

        // =============================================
        // PDT Module - Đợt đăng ký
        // =============================================
        $this->app->bind(
            DotDangKyRepositoryInterface::class,
            EloquentDotDangKyRepository::class
        );

        // =============================================
        // PDT Module - Học kỳ
        // =============================================
        $this->app->bind(
            \App\Domain\Pdt\Repositories\HocKyRepositoryInterface::class,
            \App\Infrastructure\Pdt\Persistence\Repositories\EloquentHocKyRepository::class
        );

        // =============================================
        // PDT Module - Đề xuất học phần
        // =============================================
        $this->app->bind(
            \App\Domain\Pdt\Repositories\DeXuatHocPhanRepositoryInterface::class,
            \App\Infrastructure\Pdt\Persistence\Repositories\EloquentDeXuatHocPhanRepository::class
        );

        // =============================================
        // PDT Module - Chính sách tín chỉ
        // =============================================
        $this->app->bind(
            \App\Domain\Pdt\Repositories\ChinhSachTinChiRepositoryInterface::class,
            \App\Infrastructure\Pdt\Persistence\Repositories\EloquentChinhSachTinChiRepository::class
        );

        // =============================================
        // PDT Module - Môn học
        // =============================================
        $this->app->bind(
            \App\Domain\Pdt\Repositories\MonHocRepositoryInterface::class,
            \App\Infrastructure\Pdt\Persistence\Repositories\EloquentMonHocRepository::class
        );

        // =============================================
        // PDT Module - Giảng viên
        // =============================================
        $this->app->bind(
            \App\Domain\Pdt\Repositories\GiangVienRepositoryInterface::class,
            \App\Infrastructure\Pdt\Persistence\Repositories\EloquentGiangVienRepository::class
        );

        // =============================================
        // PDT Module - Phòng học
        // =============================================
        $this->app->bind(
            \App\Domain\Pdt\Repositories\PhongHocRepositoryInterface::class,
            \App\Infrastructure\Pdt\Persistence\Repositories\EloquentPhongHocRepository::class
        );

        // =============================================
        // PDT Module - Học phí
        // =============================================
        $this->app->bind(
            \App\Domain\Pdt\Repositories\HocPhiRepositoryInterface::class,
            \App\Infrastructure\Pdt\Persistence\Repositories\EloquentHocPhiRepository::class
        );

        // =============================================
        // PDT Module - Ky Phase (giai đoạn học kỳ)
        // =============================================
        $this->app->bind(
            \App\Domain\Pdt\Repositories\KyPhaseRepositoryInterface::class,
            \App\Infrastructure\Pdt\Persistence\Repositories\EloquentKyPhaseRepository::class
        );

        // =============================================
        // PDT Module - Khoa
        // =============================================
        $this->app->bind(
            \App\Domain\Pdt\Repositories\KhoaRepositoryInterface::class,
            \App\Infrastructure\Pdt\Persistence\Repositories\EloquentKhoaRepository::class
        );

        // =============================================
        // Common Module - Common operations
        // =============================================
        $this->app->bind(
            \App\Domain\Common\Repositories\CommonRepositoryInterface::class,
            \App\Infrastructure\Common\Persistence\Repositories\EloquentCommonRepository::class
        );

        // =============================================
        // TK Module - Trưởng khoa operations
        // =============================================
        $this->app->bind(
            \App\Domain\TK\Repositories\TKRepositoryInterface::class,
            \App\Infrastructure\TK\Persistence\Repositories\EloquentTKRepository::class
        );

        // =============================================
        // TLK Module - Trợ lý khoa operations
        // =============================================
        $this->app->bind(
            \App\Domain\TLK\Repositories\TLKRepositoryInterface::class,
            \App\Infrastructure\TLK\Persistence\Repositories\EloquentTLKRepository::class
        );

        // =============================================
        // GiangVien Module - GV portal operations
        // =============================================
        $this->app->bind(
            \App\Domain\GiangVien\Repositories\GVRepositoryInterface::class,
            \App\Infrastructure\GiangVien\Persistence\Repositories\EloquentGVRepository::class
        );

        // =============================================
        // SinhVien Module - Đăng ký học phần
        // =============================================
        $this->app->bind(
            \App\Domain\SinhVien\Repositories\DangKyHocPhanRepositoryInterface::class,
            \App\Infrastructure\SinhVien\Persistence\Repositories\EloquentDangKyHocPhanRepository::class
        );

        // =============================================
        // Payment Module - Thanh toán
        // =============================================
        $this->app->bind(
            \App\Domain\Payment\Repositories\PaymentRepositoryInterface::class,
            \App\Infrastructure\Payment\Persistence\Repositories\EloquentPaymentRepository::class
        );

        // =============================================
        // SinhVien Module - Portal & Tài liệu
        // =============================================
        $this->app->bind(
            \App\Domain\SinhVien\Repositories\SinhVienPortalRepositoryInterface::class,
            \App\Infrastructure\SinhVien\Persistence\Repositories\EloquentSinhVienPortalRepository::class
        );

        // =============================================
        // SinhVien Module - Ghi danh môn học
        // =============================================
        $this->app->bind(
            \App\Domain\SinhVien\Repositories\GhiDanhRepositoryInterface::class,
            \App\Infrastructure\SinhVien\Persistence\Repositories\EloquentGhiDanhRepository::class
        );

        // =============================================
        // RBAC Module - Dynamic Role-Based Access Control
        // =============================================
        $this->app->bind(
            \App\Domain\RBAC\Repositories\RBACRepositoryInterface::class,
            \App\Infrastructure\RBAC\Persistence\Repositories\EloquentRBACRepository::class
        );

        // =============================================
        // Admin Module - Phòng CNTT RBAC Management
        // =============================================
        $this->app->bind(
            \App\Domain\Admin\Repositories\AdminRepositoryInterface::class,
            \App\Infrastructure\Admin\Persistence\Repositories\AdminRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
