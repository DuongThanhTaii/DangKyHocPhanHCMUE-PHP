<?php

namespace App\Domain\SinhVien\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object for TrangThaiDangKy (Registration Status)
 * 
 * Encapsulates registration status with validation and business logic
 */
final class TrangThaiDangKy
{
    // Status constants
    public const DA_DANG_KY = 'da_dang_ky';           // Registered
    public const CHO_DUYET = 'cho_duyet';             // Pending approval
    public const DA_DUYET = 'da_duyet';               // Approved
    public const CHO_THANH_TOAN = 'cho_thanh_toan';   // Pending payment
    public const DA_THANH_TOAN = 'da_thanh_toan';     // Paid
    public const DA_HUY = 'da_huy';                   // Cancelled
    public const COMPLETED = 'completed';             // Completed

    private const VALID_STATUSES = [
        self::DA_DANG_KY,
        self::CHO_DUYET,
        self::DA_DUYET,
        self::CHO_THANH_TOAN,
        self::DA_THANH_TOAN,
        self::DA_HUY,
        self::COMPLETED,
    ];

    private const ACTIVE_STATUSES = [
        self::DA_DANG_KY,
        self::CHO_DUYET,
        self::DA_DUYET,
        self::CHO_THANH_TOAN,
        self::DA_THANH_TOAN,
        self::COMPLETED,
    ];

    private const LABELS = [
        self::DA_DANG_KY => 'Đã đăng ký',
        self::CHO_DUYET => 'Chờ duyệt',
        self::DA_DUYET => 'Đã duyệt',
        self::CHO_THANH_TOAN => 'Chờ thanh toán',
        self::DA_THANH_TOAN => 'Đã thanh toán',
        self::DA_HUY => 'Đã hủy',
        self::COMPLETED => 'Hoàn thành',
    ];

    private string $status;

    public function __construct(string $status)
    {
        $normalized = strtolower(trim($status));
        
        if (!in_array($normalized, self::VALID_STATUSES)) {
            throw new InvalidArgumentException(
                "Invalid registration status: {$status}. Valid: " . implode(', ', self::VALID_STATUSES)
            );
        }

        $this->status = $normalized;
    }

    public function value(): string
    {
        return $this->status;
    }

    public function label(): string
    {
        return self::LABELS[$this->status] ?? $this->status;
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES);
    }

    public function isCancelled(): bool
    {
        return $this->status === self::DA_HUY;
    }

    public function isPendingPayment(): bool
    {
        return $this->status === self::CHO_THANH_TOAN;
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, [self::DA_THANH_TOAN, self::COMPLETED]);
    }

    public function isPending(): bool
    {
        return in_array($this->status, [self::DA_DANG_KY, self::CHO_DUYET]);
    }

    public function equals(TrangThaiDangKy $other): bool
    {
        return $this->status === $other->status;
    }

    public function __toString(): string
    {
        return $this->status;
    }

    // ==================== STATIC FACTORIES ====================

    public static function daDangKy(): self
    {
        return new self(self::DA_DANG_KY);
    }

    public static function daHuy(): self
    {
        return new self(self::DA_HUY);
    }

    public static function daDuyet(): self
    {
        return new self(self::DA_DUYET);
    }

    public static function daThanhToan(): self
    {
        return new self(self::DA_THANH_TOAN);
    }
}
