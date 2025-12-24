<?php

namespace App\Domain\GiangVien\Entities;

use DateTimeImmutable;

/**
 * Domain Entity for TaiLieu (Class Document)
 * 
 * Represents a document uploaded for a class
 */
class TaiLieuEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $lopHocPhanId,
        public readonly string $tenTaiLieu,
        public readonly ?string $moTa = null,
        public readonly ?string $filePath = null,
        public readonly ?string $fileUrl = null,
        public readonly ?string $mimeType = null,
        public readonly ?int $fileSize = null, // in bytes
        public readonly ?DateTimeImmutable $uploadedAt = null,
        public readonly ?string $uploadedBy = null,
    ) {
    }

    /**
     * Get human-readable file size
     */
    public function getFileSizeFormatted(): string
    {
        if ($this->fileSize === null) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->fileSize;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Check if file is an image
     */
    public function isImage(): bool
    {
        return $this->mimeType && str_starts_with($this->mimeType, 'image/');
    }

    /**
     * Check if file is a PDF
     */
    public function isPdf(): bool
    {
        return $this->mimeType === 'application/pdf';
    }

    /**
     * Check if file is downloadable
     */
    public function isDownloadable(): bool
    {
        return $this->filePath !== null || $this->fileUrl !== null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'lopHocPhanId' => $this->lopHocPhanId,
            'tenTaiLieu' => $this->tenTaiLieu,
            'moTa' => $this->moTa,
            'filePath' => $this->filePath,
            'fileUrl' => $this->fileUrl,
            'mimeType' => $this->mimeType,
            'fileSize' => $this->fileSize,
            'fileSizeFormatted' => $this->getFileSizeFormatted(),
            'uploadedAt' => $this->uploadedAt?->format('Y-m-d H:i:s'),
            'uploadedBy' => $this->uploadedBy,
            'isImage' => $this->isImage(),
            'isPdf' => $this->isPdf(),
        ];
    }
}
