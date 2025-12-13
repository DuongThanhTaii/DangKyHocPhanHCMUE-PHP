<?php

namespace App\Application\Pdt\DTOs;

/**
 * DTO cho việc toggle phase
 */
class TogglePhaseDTO
{
    private const VALID_PHASES = [
        'de_xuat_phe_duyet',
        'ghi_danh',
        'dang_ky_hoc_phan',
        'sap_xep_tkb',
        'binh_thuong'
    ];

    public function __construct(
        public readonly string $phase,
        public readonly ?string $hocKyId = null,
    ) {
    }

    public static function fromRequest(array $data): self
    {
        $phase = $data['phase'] ?? null;

        if (!$phase) {
            throw new \InvalidArgumentException('phase is required');
        }

        if (!in_array($phase, self::VALID_PHASES)) {
            throw new \InvalidArgumentException('Phase không hợp lệ. Valid: ' . implode(', ', self::VALID_PHASES));
        }

        return new self(
            phase: $phase,
            hocKyId: $data['hocKyId'] ?? $data['hoc_ky_id'] ?? null,
        );
    }
}
