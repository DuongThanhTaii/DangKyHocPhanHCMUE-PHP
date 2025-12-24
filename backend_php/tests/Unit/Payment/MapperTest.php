<?php

namespace Tests\Unit\Payment;

use PHPUnit\Framework\TestCase;
use App\Infrastructure\Payment\Persistence\Mappers\PaymentMapper;
use App\Domain\Payment\Entities\PaymentTransactionEntity;

/**
 * Unit Tests for PaymentMapper
 */
class MapperTest extends TestCase
{
    public function test_to_payment_transaction_entity_converts_model_correctly(): void
    {
        $model = (object) [
            'id' => 'uuid-pt-1',
            'order_id' => 'ORDER-2024-001',
            'sinh_vien_id' => 'uuid-sv-1',
            'hoc_ky_id' => 'uuid-hk-1',
            'amount' => 5000000,
            'provider' => 'vnpay',
            'status' => PaymentTransactionEntity::STATUS_COMPLETED,
            'transaction_ref' => 'VNP123456',
            'bank_code' => 'VCB',
            'paid_at' => '2024-01-15 14:30:00',
            'created_at' => '2024-01-15 14:00:00',
            'error_message' => null,
        ];

        $entity = PaymentMapper::toPaymentTransactionEntity($model);

        $this->assertInstanceOf(PaymentTransactionEntity::class, $entity);
        $this->assertEquals('uuid-pt-1', $entity->id);
        $this->assertEquals('ORDER-2024-001', $entity->orderId);
        $this->assertEquals(5000000, $entity->amount);
        $this->assertEquals('vnpay', $entity->provider);
        $this->assertTrue($entity->isSuccessful());
    }

    public function test_format_transaction_for_api_returns_fe_compatible_format(): void
    {
        $entity = new PaymentTransactionEntity(
            id: 'uuid-pt-1',
            orderId: 'ORDER-2024-001',
            sinhVienId: 'uuid-sv-1',
            hocKyId: 'uuid-hk-1',
            amount: 5000000,
            provider: 'vnpay',
            status: PaymentTransactionEntity::STATUS_PENDING,
        );

        $result = PaymentMapper::formatTransactionForApi($entity);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('orderId', $result);
        $this->assertArrayHasKey('amount', $result);
        $this->assertArrayHasKey('amountFormatted', $result);
        $this->assertArrayHasKey('provider', $result);
        $this->assertArrayHasKey('providerLabel', $result);
        $this->assertArrayHasKey('isPending', $result);
        $this->assertArrayHasKey('canRetry', $result);
        $this->assertTrue($result['isPending']);
        $this->assertFalse($result['isSuccessful']);
    }

    public function test_format_transaction_for_api_shows_correct_provider_label(): void
    {
        $vnpayEntity = new PaymentTransactionEntity(
            id: 'uuid-1',
            orderId: 'O1',
            sinhVienId: 'sv1',
            hocKyId: 'hk1',
            amount: 1000000,
            provider: 'vnpay',
            status: PaymentTransactionEntity::STATUS_COMPLETED,
        );

        $momoEntity = new PaymentTransactionEntity(
            id: 'uuid-2',
            orderId: 'O2',
            sinhVienId: 'sv1',
            hocKyId: 'hk1',
            amount: 1000000,
            provider: 'momo',
            status: PaymentTransactionEntity::STATUS_COMPLETED,
        );

        $vnpayResult = PaymentMapper::formatTransactionForApi($vnpayEntity);
        $momoResult = PaymentMapper::formatTransactionForApi($momoEntity);

        $this->assertEquals('VNPay', $vnpayResult['providerLabel']);
        $this->assertEquals('MoMo', $momoResult['providerLabel']);
    }
}
