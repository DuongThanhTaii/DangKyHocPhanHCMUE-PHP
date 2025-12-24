<?php

namespace App\Domain\Payment\Entities;

use DateTimeImmutable;

/**
 * Domain Entity for PaymentTransaction 
 * 
 * Represents a payment transaction for tuition
 */
class PaymentTransactionEntity
{
    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    // Provider constants
    public const PROVIDER_VNPAY = 'vnpay';
    public const PROVIDER_MOMO = 'momo';
    public const PROVIDER_ZALOPAY = 'zalopay';

    public function __construct(
        public readonly string $id,
        public readonly string $orderId,
        public readonly string $sinhVienId,
        public readonly string $hocKyId,
        public readonly float $amount,
        public readonly string $provider,
        public readonly string $status = self::STATUS_PENDING,
        public readonly ?string $transactionRef = null, // Payment gateway ref
        public readonly ?string $bankCode = null,
        public readonly ?DateTimeImmutable $paidAt = null,
        public readonly ?DateTimeImmutable $createdAt = null,
        public readonly ?string $errorMessage = null,
    ) {
    }

    /**
     * Check if payment is successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if payment failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if payment can be retried
     */
    public function canRetry(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_CANCELLED]);
    }

    /**
     * Get amount formatted (VND)
     */
    public function getAmountFormatted(): string
    {
        return number_format($this->amount, 0, ',', '.') . ' VNĐ';
    }

    /**
     * Get status label
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Chờ thanh toán',
            self::STATUS_COMPLETED => 'Thành công',
            self::STATUS_FAILED => 'Thất bại',
            self::STATUS_CANCELLED => 'Đã hủy',
            self::STATUS_REFUNDED => 'Đã hoàn tiền',
            default => $this->status,
        };
    }

    /**
     * Get provider label
     */
    public function getProviderLabel(): string
    {
        return match($this->provider) {
            self::PROVIDER_VNPAY => 'VNPay',
            self::PROVIDER_MOMO => 'MoMo',
            self::PROVIDER_ZALOPAY => 'ZaloPay',
            default => $this->provider,
        };
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'orderId' => $this->orderId,
            'sinhVienId' => $this->sinhVienId,
            'hocKyId' => $this->hocKyId,
            'amount' => $this->amount,
            'amountFormatted' => $this->getAmountFormatted(),
            'provider' => $this->provider,
            'providerLabel' => $this->getProviderLabel(),
            'status' => $this->status,
            'statusLabel' => $this->getStatusLabel(),
            'transactionRef' => $this->transactionRef,
            'bankCode' => $this->bankCode,
            'paidAt' => $this->paidAt?->format('Y-m-d H:i:s'),
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'isSuccessful' => $this->isSuccessful(),
            'canRetry' => $this->canRetry(),
        ];
    }
}
