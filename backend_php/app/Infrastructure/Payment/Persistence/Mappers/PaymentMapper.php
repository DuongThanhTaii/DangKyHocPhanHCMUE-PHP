<?php

namespace App\Infrastructure\Payment\Persistence\Mappers;

use App\Domain\Payment\Entities\PaymentTransactionEntity;
use DateTimeImmutable;
use Illuminate\Support\Collection;

/**
 * Mapper for Payment Module
 * 
 * Converts Eloquent Models → Domain Entities
 */
class PaymentMapper
{
    /**
     * Convert PaymentTransaction Model to Entity
     */
    public static function toPaymentTransactionEntity(object $model): PaymentTransactionEntity
    {
        return new PaymentTransactionEntity(
            id: $model->id,
            orderId: $model->order_id,
            sinhVienId: $model->sinh_vien_id,
            hocKyId: $model->hoc_ky_id,
            amount: (float) ($model->amount ?? 0),
            provider: $model->provider ?? 'unknown',
            status: $model->status ?? PaymentTransactionEntity::STATUS_PENDING,
            transactionRef: $model->transaction_ref ?? null,
            bankCode: $model->bank_code ?? null,
            paidAt: $model->paid_at 
                ? new DateTimeImmutable($model->paid_at) 
                : null,
            createdAt: $model->created_at 
                ? new DateTimeImmutable($model->created_at) 
                : null,
            errorMessage: $model->error_message ?? null,
        );
    }

    /**
     * Convert Collection of PaymentTransaction Models to Entities
     */
    public static function toPaymentTransactionEntities(Collection $models): array
    {
        return $models->map(fn($m) => self::toPaymentTransactionEntity($m))->toArray();
    }

    /**
     * Format PaymentTransactionEntity for API response (FE-compatible format)
     */
    public static function formatTransactionForApi(PaymentTransactionEntity $entity, ?object $model = null): array
    {
        $data = [
            'id' => $entity->id,
            'orderId' => $entity->orderId,
            'sinhVienId' => $entity->sinhVienId,
            'hocKyId' => $entity->hocKyId,
            'amount' => $entity->amount,
            'amountFormatted' => $entity->getAmountFormatted(),
            'provider' => $entity->provider,
            'providerLabel' => $entity->getProviderLabel(),
            'status' => $entity->status,
            'statusLabel' => $entity->getStatusLabel(),
            'transactionRef' => $entity->transactionRef,
            'bankCode' => $entity->bankCode,
            'paidAt' => $entity->paidAt?->format('c'),
            'createdAt' => $entity->createdAt?->format('c'),
            'isSuccessful' => $entity->isSuccessful(),
            'isPending' => $entity->isPending(),
            'canRetry' => $entity->canRetry(),
        ];

        if ($model) {
            $data['tenSinhVien'] = $model->sinhVien?->user?->ho_ten ?? '';
            $data['maSoSinhVien'] = $model->sinhVien?->ma_so_sinh_vien ?? '';
            $data['tenHocKy'] = $model->hocKy?->ten_hoc_ky ?? '';
        }

        return $data;
    }
}
