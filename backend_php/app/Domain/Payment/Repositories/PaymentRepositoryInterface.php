<?php

namespace App\Domain\Payment\Repositories;

/**
 * Repository Interface cho Payment operations
 */
interface PaymentRepositoryInterface
{
    /**
     * Tạo giao dịch thanh toán
     */
    public function createTransaction(array $data): object;

    /**
     * Tìm giao dịch theo orderId
     */
    public function findByOrderId(string $orderId): ?object;

    /**
     * Cập nhật trạng thái giao dịch
     */
    public function updateTransactionStatus(object $transaction, string $status, array $data = []): void;

    /**
     * Cập nhật trạng thái học phí
     */
    public function updateHocPhiStatus(string $sinhVienId, string $hocKyId): void;

    /**
     * Tính tổng học phí từ đăng ký
     */
    public function calculateTuition(string $sinhVienId, string $hocKyId): ?array;

    /**
     * Tạo order ID
     */
    public function generateOrderId(string $provider): string;
}
