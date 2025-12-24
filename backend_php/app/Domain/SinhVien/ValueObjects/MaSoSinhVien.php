<?php

namespace App\Domain\SinhVien\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object for MaSoSinhVien (Student ID)
 * 
 * Format: XX.YY.ZZZ.NNN (e.g., 49.01.104.123)
 * - XX: Khóa học (year)
 * - YY: Mã khoa
 * - ZZZ: Mã ngành
 * - NNN: Số thứ tự
 */
final class MaSoSinhVien
{
    private string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        
        if (empty($trimmed)) {
            throw new InvalidArgumentException('Mã số sinh viên không được để trống');
        }

        // Basic format validation (flexible to support various formats)
        if (strlen($trimmed) < 3) {
            throw new InvalidArgumentException('Mã số sinh viên quá ngắn');
        }

        $this->value = $trimmed;
    }

    public function value(): string
    {
        return $this->value;
    }

    /**
     * Get khóa học from MSSV (first part before .)
     */
    public function getKhoaHoc(): ?string
    {
        $parts = explode('.', $this->value);
        return $parts[0] ?? null;
    }

    /**
     * Get mã khoa from MSSV (second part)
     */
    public function getMaKhoa(): ?string
    {
        $parts = explode('.', $this->value);
        return $parts[1] ?? null;
    }

    /**
     * Get mã ngành from MSSV (third part)
     */
    public function getMaNganh(): ?string
    {
        $parts = explode('.', $this->value);
        return $parts[2] ?? null;
    }

    /**
     * Get số thứ tự from MSSV (last part)
     */
    public function getSoThuTu(): ?string
    {
        $parts = explode('.', $this->value);
        return end($parts) ?: null;
    }

    /**
     * Check if this is a valid structured MSSV (XX.YY.ZZZ.NNN format)
     */
    public function isStructuredFormat(): bool
    {
        return preg_match('/^\d{2}\.\d{2}\.\d{3}\.\d+$/', $this->value) === 1;
    }

    public function equals(MaSoSinhVien $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
