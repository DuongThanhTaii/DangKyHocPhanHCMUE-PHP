<?php

namespace App\Domain\TLK\Entities;

use DateTimeImmutable;

/**
 * Domain Entity for DeXuat (Course Proposal)
 * 
 * Represents a proposal for opening a course section, created by TLK
 */
class DeXuatEntity
{
    // Status constants
    public const STATUS_CHO_DUYET_TK = 'cho_duyet_tk';     // Pending TK approval
    public const STATUS_TK_DUYET = 'tk_duyet';             // TK approved, pending PDT
    public const STATUS_CHO_DUYET_PDT = 'cho_duyet_pdt';   // Waiting PDT approval
    public const STATUS_PDT_DUYET = 'pdt_duyet';           // PDT approved
    public const STATUS_TU_CHOI = 'tu_choi';               // Rejected

    public function __construct(
        public readonly string $id,
        public readonly string $monHocId,
        public readonly string $hocKyId,
        public readonly string $khoaId,
        public readonly ?string $createdById = null,
        public readonly ?string $trangThai = self::STATUS_CHO_DUYET_TK,
        public readonly ?int $soLopDeXuat = 1,
        public readonly ?int $soSinhVienDuKien = null,
        public readonly ?string $ghiChu = null,
        public readonly ?string $lyDoTuChoi = null,
        public readonly ?DateTimeImmutable $ngayTao = null,
        // Denormalized for display
        public readonly ?string $tenMonHoc = null,
        public readonly ?string $maMonHoc = null,
    ) {
    }

    /**
     * Check if pending TK approval
     */
    public function isPendingTK(): bool
    {
        return $this->trangThai === self::STATUS_CHO_DUYET_TK;
    }

    /**
     * Check if pending PDT approval
     */
    public function isPendingPDT(): bool
    {
        return in_array($this->trangThai, [self::STATUS_TK_DUYET, self::STATUS_CHO_DUYET_PDT]);
    }

    /**
     * Check if approved
     */
    public function isApproved(): bool
    {
        return $this->trangThai === self::STATUS_PDT_DUYET;
    }

    /**
     * Check if rejected
     */
    public function isRejected(): bool
    {
        return $this->trangThai === self::STATUS_TU_CHOI;
    }

    /**
     * Check if can be edited (only pending TK)
     */
    public function canEdit(): bool
    {
        return $this->isPendingTK();
    }

    /**
     * Get status label
     */
    public function getStatusLabel(): string
    {
        return match($this->trangThai) {
            self::STATUS_CHO_DUYET_TK => 'Chờ TK duyệt',
            self::STATUS_TK_DUYET => 'TK đã duyệt',
            self::STATUS_CHO_DUYET_PDT => 'Chờ PDT duyệt',
            self::STATUS_PDT_DUYET => 'PDT đã duyệt',
            self::STATUS_TU_CHOI => 'Từ chối',
            default => $this->trangThai ?? 'Không xác định',
        };
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'monHocId' => $this->monHocId,
            'hocKyId' => $this->hocKyId,
            'khoaId' => $this->khoaId,
            'trangThai' => $this->trangThai,
            'statusLabel' => $this->getStatusLabel(),
            'soLopDeXuat' => $this->soLopDeXuat,
            'soSinhVienDuKien' => $this->soSinhVienDuKien,
            'ghiChu' => $this->ghiChu,
            'lyDoTuChoi' => $this->lyDoTuChoi,
            'ngayTao' => $this->ngayTao?->format('Y-m-d H:i:s'),
            'tenMonHoc' => $this->tenMonHoc,
            'maMonHoc' => $this->maMonHoc,
            'canEdit' => $this->canEdit(),
        ];
    }
}
