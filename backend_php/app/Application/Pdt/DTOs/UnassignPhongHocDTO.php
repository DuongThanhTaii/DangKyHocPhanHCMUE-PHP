<?php

namespace App\Application\Pdt\DTOs;

/**
 * DTO cho việc hủy gán phòng học khỏi khoa
 */
class UnassignPhongHocDTO
{
    public function __construct(
        public readonly array $phongIds,
    ) {
    }

    public static function fromRequest(array $data): self
    {
        $phongIds = $data['phongHocIds'] ?? $data['phong_hoc_ids'] ?? null;
        $phongId = $data['phongId'] ?? $data['phong_id'] ?? null;

        // If single phongId is provided, convert to list
        if ($phongId && !$phongIds) {
            $phongIds = [$phongId];
        }

        if (empty($phongIds)) {
            throw new \InvalidArgumentException('Thiếu phongId hoặc phongHocIds');
        }

        return new self(phongIds: $phongIds);
    }
}
