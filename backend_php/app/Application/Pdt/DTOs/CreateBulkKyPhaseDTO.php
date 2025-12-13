<?php

namespace App\Application\Pdt\DTOs;

use Carbon\Carbon;

/**
 * DTO cho tạo bulk phases cho học kỳ
 */
class CreateBulkKyPhaseDTO
{
    /**
     * @param array $phases Array of phase data
     */
    public function __construct(
        public readonly string $hocKyId,
        public readonly Carbon $hocKyStartAt,
        public readonly Carbon $hocKyEndAt,
        public readonly array $phases,
    ) {
    }

    /**
     * Factory method từ Request array
     * 
     * @throws \InvalidArgumentException Khi validation fail
     */
    public static function fromRequest(array $data): self
    {
        $hocKyId = $data['hocKyId'] ?? $data['hoc_ky_id'] ?? '';
        $hocKyStartAt = $data['hocKyStartAt'] ?? $data['hoc_ky_start_at'] ?? null;
        $hocKyEndAt = $data['hocKyEndAt'] ?? $data['hoc_ky_end_at'] ?? null;
        $phases = $data['phases'] ?? [];

        // Validate required fields
        if (empty($hocKyId) || empty($hocKyStartAt) || empty($hocKyEndAt)) {
            throw new \InvalidArgumentException('Thiếu thông tin học kỳ');
        }

        if (empty($phases) || !is_array($phases)) {
            throw new \InvalidArgumentException('Danh sách phases rỗng');
        }

        // Parse dates
        try {
            $startAt = Carbon::parse($hocKyStartAt);
            $endAt = Carbon::parse($hocKyEndAt);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Định dạng ngày không hợp lệ');
        }

        if ($startAt >= $endAt) {
            throw new \InvalidArgumentException('Thời gian bắt đầu học kỳ phải trước thời gian kết thúc');
        }

        return new self(
            hocKyId: $hocKyId,
            hocKyStartAt: $startAt,
            hocKyEndAt: $endAt,
            phases: $phases,
        );
    }
}
