<?php

namespace App\Application\Pdt\DTOs;

/**
 * DTO cho việc gán phòng học cho khoa
 */
class AssignPhongHocDTO
{
    public function __construct(
        public readonly array $phongIds,
        public readonly string $khoaId,
    ) {
    }

    public static function fromRequest(array $data): self
    {
        $phongId = $data['phongId'] ?? $data['phong_id'] ?? null;
        $khoaId = $data['khoaId'] ?? $data['khoa_id'] ?? null;

        if (!$phongId || !$khoaId) {
            throw new \InvalidArgumentException('Thiếu phongId hoặc khoaId');
        }

        // Handle both single and array
        $phongIds = is_array($phongId) ? $phongId : [$phongId];

        return new self(
            phongIds: $phongIds,
            khoaId: $khoaId,
        );
    }
}
