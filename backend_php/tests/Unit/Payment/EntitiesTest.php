<?php

namespace Tests\Unit\Payment;

use PHPUnit\Framework\TestCase;
use App\Domain\Payment\Entities\PaymentTransactionEntity;
use DateTimeImmutable;

/**
 * Unit Tests for Payment Domain Entities
 */
class EntitiesTest extends TestCase
{
    public function test_payment_transaction_entity_creation(): void
    {
        $entity = new PaymentTransactionEntity(
            id: 'txn-001',
            orderId: 'ORD123456',
            sinhVienId: 'sv-001',
            hocKyId: 'hk-001',
            amount: 15000000,
            provider: PaymentTransactionEntity::PROVIDER_VNPAY,
        );

        $this->assertEquals('txn-001', $entity->id);
        $this->assertEquals('ORD123456', $entity->orderId);
        $this->assertEquals(15000000, $entity->amount);
        $this->assertEquals('vnpay', $entity->provider);
    }

    public function test_payment_is_successful(): void
    {
        $success = new PaymentTransactionEntity(
            '1', 'ORD1', 'sv-1', 'hk-1', 1000000, 'vnpay',
            status: PaymentTransactionEntity::STATUS_COMPLETED
        );
        $pending = new PaymentTransactionEntity(
            '2', 'ORD2', 'sv-2', 'hk-2', 1000000, 'vnpay',
            status: PaymentTransactionEntity::STATUS_PENDING
        );

        $this->assertTrue($success->isSuccessful());
        $this->assertFalse($pending->isSuccessful());
    }

    public function test_payment_is_pending(): void
    {
        $pending = new PaymentTransactionEntity(
            '1', 'ORD1', 'sv-1', 'hk-1', 1000000, 'vnpay',
            status: PaymentTransactionEntity::STATUS_PENDING
        );
        $this->assertTrue($pending->isPending());
    }

    public function test_payment_is_failed(): void
    {
        $failed = new PaymentTransactionEntity(
            '1', 'ORD1', 'sv-1', 'hk-1', 1000000, 'vnpay',
            status: PaymentTransactionEntity::STATUS_FAILED
        );
        $this->assertTrue($failed->isFailed());
    }

    public function test_payment_can_retry(): void
    {
        $failed = new PaymentTransactionEntity(
            '1', 'ORD1', 'sv-1', 'hk-1', 1000000, 'vnpay',
            status: PaymentTransactionEntity::STATUS_FAILED
        );
        $cancelled = new PaymentTransactionEntity(
            '2', 'ORD2', 'sv-2', 'hk-2', 1000000, 'vnpay',
            status: PaymentTransactionEntity::STATUS_CANCELLED
        );
        $success = new PaymentTransactionEntity(
            '3', 'ORD3', 'sv-3', 'hk-3', 1000000, 'vnpay',
            status: PaymentTransactionEntity::STATUS_COMPLETED
        );

        $this->assertTrue($failed->canRetry());
        $this->assertTrue($cancelled->canRetry());
        $this->assertFalse($success->canRetry());
    }

    public function test_payment_get_amount_formatted(): void
    {
        $entity = new PaymentTransactionEntity(
            '1', 'ORD1', 'sv-1', 'hk-1', 15000000, 'vnpay'
        );
        $this->assertEquals('15.000.000 VNĐ', $entity->getAmountFormatted());
    }

    public function test_payment_get_status_label(): void
    {
        $pending = new PaymentTransactionEntity(
            '1', 'ORD1', 'sv-1', 'hk-1', 1000000, 'vnpay',
            status: PaymentTransactionEntity::STATUS_PENDING
        );
        $success = new PaymentTransactionEntity(
            '2', 'ORD2', 'sv-2', 'hk-2', 1000000, 'vnpay',
            status: PaymentTransactionEntity::STATUS_COMPLETED
        );

        $this->assertEquals('Chờ thanh toán', $pending->getStatusLabel());
        $this->assertEquals('Thành công', $success->getStatusLabel());
    }

    public function test_payment_get_provider_label(): void
    {
        $vnpay = new PaymentTransactionEntity('1', 'ORD1', 'sv-1', 'hk-1', 1000000, PaymentTransactionEntity::PROVIDER_VNPAY);
        $momo = new PaymentTransactionEntity('2', 'ORD2', 'sv-2', 'hk-2', 1000000, PaymentTransactionEntity::PROVIDER_MOMO);
        $zalo = new PaymentTransactionEntity('3', 'ORD3', 'sv-3', 'hk-3', 1000000, PaymentTransactionEntity::PROVIDER_ZALOPAY);

        $this->assertEquals('VNPay', $vnpay->getProviderLabel());
        $this->assertEquals('MoMo', $momo->getProviderLabel());
        $this->assertEquals('ZaloPay', $zalo->getProviderLabel());
    }
}
