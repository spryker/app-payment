<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Shared\AppPayment\Helper;

use Codeception\Module;
use Generated\Shared\DataBuilder\CancelPaymentRequestBuilder;
use Generated\Shared\DataBuilder\CapturePaymentRequestBuilder;
use Generated\Shared\DataBuilder\ConfirmPreOrderPaymentRequestBuilder;
use Generated\Shared\DataBuilder\InitializePaymentRequestBuilder;
use Generated\Shared\DataBuilder\OrderItemBuilder;
use Generated\Shared\DataBuilder\PaymentBuilder;
use Generated\Shared\DataBuilder\PaymentTransmissionBuilder;
use Generated\Shared\DataBuilder\PaymentTransmissionsRequestBuilder;
use Generated\Shared\DataBuilder\PaymentTransmissionsResponseBuilder;
use Generated\Shared\DataBuilder\QuoteBuilder;
use Generated\Shared\Transfer\CancelPaymentRequestTransfer;
use Generated\Shared\Transfer\CapturePaymentRequestTransfer;
use Generated\Shared\Transfer\ConfirmPreOrderPaymentRequestTransfer;
use Generated\Shared\Transfer\InitializePaymentRequestTransfer;
use Generated\Shared\Transfer\OrderItemTransfer;
use Generated\Shared\Transfer\PaymentPageRequestTransfer;
use Generated\Shared\Transfer\PaymentStatusRequestTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\PaymentTransmissionsRequestTransfer;
use Generated\Shared\Transfer\PaymentTransmissionsResponseTransfer;
use Generated\Shared\Transfer\PaymentTransmissionTransfer;
use Orm\Zed\AppPayment\Persistence\SpyPaymentQuery;
use Orm\Zed\AppPayment\Persistence\SpyPaymentTransfer;
use Orm\Zed\AppPayment\Persistence\SpyPaymentTransferQuery;
use Ramsey\Uuid\Uuid;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;
use Spryker\Zed\AppPayment\Persistence\AppPaymentEntityManager;
use Spryker\Zed\AppPayment\Persistence\AppPaymentRepository;
use SprykerTest\Shared\AppKernel\Helper\AppConfigHelperTrait;
use SprykerTest\Shared\Testify\Helper\DataCleanupHelperTrait;

class AppPaymentHelper extends Module
{
    use DataCleanupHelperTrait;
    use AppConfigHelperTrait;

    public function assertPaymentIsInState(string $transactionId, string $expectedState): void
    {
        $paymentEntity = SpyPaymentQuery::create()->findOneByTransactionId($transactionId);

        $this->assertNotNull($paymentEntity, sprintf('Could not find a payment with transaction id "%s".', $transactionId));
        $this->assertSame($expectedState, $paymentEntity->getStatus(), sprintf('Expected payment to be in status "%s" but got "%s"', $expectedState, $paymentEntity->getStatus()));
    }

    public function havePaymentForTransactionId(
        string $transactionId,
        string $tenantIdentifier,
        string $status = PaymentStatus::STATUS_NEW
    ): PaymentTransfer {
        $seed = [
            PaymentTransfer::TRANSACTION_ID => $transactionId,
            PaymentTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            PaymentTransfer::STATUS => $status,
        ];

        return $this->havePayment($seed);
    }

    public function havePaymentTransmissionPersisted(
        array $seed = []
    ): PaymentTransmissionTransfer {
        $paymentTransmissionTransfer = (new PaymentTransmissionBuilder($seed))->build();

        $paymentTransferData = $paymentTransmissionTransfer->toArray();

        $spyPaymentTransmissionEntity = new SpyPaymentTransfer();
        $spyPaymentTransmissionEntity->fromArray($paymentTransferData);
        $spyPaymentTransmissionEntity->save();

        return $paymentTransmissionTransfer;
    }

    public function havePayment(array $seed = []): PaymentTransfer
    {
        $quoteBuilder = new QuoteBuilder($seed);
        $quoteBuilder->withItem()
            ->withAnotherItem();

        $paymentTransfer = (new PaymentBuilder($seed))->build();
        $quoteTransfer = $quoteBuilder->build();
        $orderReference = $quoteTransfer->getOrderReference();

        $paymentTransfer
            ->setQuote($quoteTransfer)
            ->setOrderReference($orderReference)
            ->setStatus($seed['status'] ?? PaymentStatus::STATUS_NEW);

        $paymentEntityManager = new AppPaymentEntityManager();

        $this->getDataCleanupHelper()->addCleanup(function () use ($orderReference): void {
            $paymentEntity = SpyPaymentQuery::create()->findOneByOrderReference($orderReference);

            if ($paymentEntity) {
                $paymentEntity->delete();
            }
        });

        return $paymentEntityManager->createPayment($paymentTransfer);
    }

    public function haveInitializePaymentRequestTransfer(array $seed = [], array $additionalPaymentData = []): InitializePaymentRequestTransfer
    {
        $tenantIdentifier = $seed[InitializePaymentRequestTransfer::TENANT_IDENTIFIER] ?? Uuid::uuid4()->toString();
        $quoteBuilder = new QuoteBuilder();
        $quoteBuilder->withItem()
            ->withAnotherItem();

        $quoteTransfer = $quoteBuilder->build();

        if ($additionalPaymentData) {
            $paymentTransfer = $quoteTransfer->getPayment() ?? new PaymentTransfer();
            $paymentTransfer->setAdditionalPaymentData($additionalPaymentData);
            $quoteTransfer->setPayment($paymentTransfer);
        }

        $initializePaymentRequestTransfer = (new InitializePaymentRequestBuilder($seed))->build();
        $initializePaymentRequestTransfer->setOrderData($quoteTransfer);
        $initializePaymentRequestTransfer->setTenantIdentifier($tenantIdentifier);

        $this->getDataCleanupHelper()->addCleanup(function () use ($tenantIdentifier): void {
            $paymentEntity = SpyPaymentQuery::create()->findOneByTenantIdentifier($tenantIdentifier);

            if ($paymentEntity) {
                $paymentEntity->delete();
            }
        });

        return $initializePaymentRequestTransfer;
    }

    /**
     * This method should only be used by the PlatformPluginInterface implementation tests.
     * It Provides a request transfer as it would come from the Payment module.
     */
    public function haveInitializePaymentRequestWithAppConfigTransfer(array $seed = [], array $additionalPaymentData = []): InitializePaymentRequestTransfer
    {
        $tenantIdentifier = $seed[InitializePaymentRequestTransfer::TENANT_IDENTIFIER] ?? Uuid::uuid4()->toString();
        $quoteBuilder = new QuoteBuilder($seed);
        $quoteBuilder->withItem()
            ->withAnotherItem();

        $quoteTransfer = $quoteBuilder->build();

        if ($additionalPaymentData) {
            $paymentTransfer = $quoteTransfer->getPayment() ?? new PaymentTransfer();
            $paymentTransfer->setAdditionalPaymentData($additionalPaymentData);
            $quoteTransfer->setPayment($paymentTransfer);
        }

        $initializePaymentRequestTransfer = (new InitializePaymentRequestBuilder($seed))->build();
        $initializePaymentRequestTransfer->setOrderData($quoteTransfer);
        $initializePaymentRequestTransfer->setTenantIdentifier($tenantIdentifier);

        $appConfigTransfer = $this->getAppConfigHelper()->haveAppConfigForTenant($tenantIdentifier, $seed);

        $initializePaymentRequestTransfer->setAppConfig($appConfigTransfer);

        return $initializePaymentRequestTransfer;
    }

    public function haveConfirmPreOrderPaymentRequestTransfer(array $seed = []): ConfirmPreOrderPaymentRequestTransfer
    {
        $tenantIdentifier = $seed[ConfirmPreOrderPaymentRequestTransfer::TENANT_IDENTIFIER] ?? Uuid::uuid4()->toString();
        $orderReference = $seed[ConfirmPreOrderPaymentRequestTransfer::ORDER_REFERENCE] ?? Uuid::uuid4()->toString();
        $transactionId = $seed[ConfirmPreOrderPaymentRequestTransfer::TRANSACTION_ID] ?? Uuid::uuid4()->toString();

        $confirmPreOrderPaymentRequestTransfer = (new ConfirmPreOrderPaymentRequestBuilder($seed))->build();
        $confirmPreOrderPaymentRequestTransfer
            ->setTenantIdentifier($tenantIdentifier)
            ->setOrderReference($orderReference)
            ->setTransactionId($transactionId);

        return $confirmPreOrderPaymentRequestTransfer;
    }

    public function havePaymentPageRequestTransfer(array $seed = []): PaymentPageRequestTransfer
    {
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();

        $appConfigTransfer = $this->getAppConfigHelper()->haveAppConfigForTenant($tenantIdentifier, $seed);
        $paymentTransfer = $this->havePaymentForTransactionId($transactionId, $tenantIdentifier);

        $paymentPageRequestTransfer = new PaymentPageRequestTransfer();
        $paymentPageRequestTransfer
            ->setPayment($paymentTransfer)
            ->setAppConfig($appConfigTransfer)
            ->setTransactionId($transactionId);

        return $paymentPageRequestTransfer;
    }

    public function havePaymentStatusRequestTransfer(array $seed = []): PaymentStatusRequestTransfer
    {
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();

        $appConfigTransfer = $this->getAppConfigHelper()->haveAppConfigForTenant($tenantIdentifier, $seed);
        $paymentTransfer = $this->havePaymentForTransactionId($transactionId, $tenantIdentifier);

        $paymentStatusRequestTransfer = new PaymentStatusRequestTransfer();
        $paymentStatusRequestTransfer
            ->setPayment($paymentTransfer)
            ->setAppConfig($appConfigTransfer)
            ->setTransactionId($transactionId);

        return $paymentStatusRequestTransfer;
    }

    public function haveCapturePaymentRequestTransfer(array $seed = [], $status = PaymentStatus::STATUS_NEW): CapturePaymentRequestTransfer
    {
        $tenantIdentifier = $seed['tenantIdentifier'] ?? Uuid::uuid4()->toString();
        $transactionId = $seed['transactionId'] ?? Uuid::uuid4()->toString();

        $appConfigTransfer = $this->getAppConfigHelper()->haveAppConfigForTenant($tenantIdentifier, $seed);
        $paymentTransfer = $this->havePaymentForTransactionId($transactionId, $tenantIdentifier, $status);

        $capturePaymentRequestTransfer = (new CapturePaymentRequestBuilder($seed))->build();
        $capturePaymentRequestTransfer
            ->setPayment($paymentTransfer)
            ->setAppConfig($appConfigTransfer)
            ->setTransactionId($transactionId);

        return $capturePaymentRequestTransfer;
    }

    public function haveCancelPaymentRequestTransfer(array $seed = [], $status = PaymentStatus::STATUS_NEW): CancelPaymentRequestTransfer
    {
        $tenantIdentifier = $seed['tenantIdentifier'] ?? Uuid::uuid4()->toString();
        $transactionId = $seed['transactionId'] ?? Uuid::uuid4()->toString();

        $appConfigTransfer = $this->getAppConfigHelper()->haveAppConfigForTenant($tenantIdentifier, $seed);
        $paymentTransfer = $this->havePaymentForTransactionId($transactionId, $tenantIdentifier, $status);

        $cancelPaymentRequestTransfer = (new CancelPaymentRequestBuilder($seed))->build();
        $cancelPaymentRequestTransfer
            ->setPayment($paymentTransfer)
            ->setAppConfig($appConfigTransfer)
            ->setTransactionId($transactionId);

        return $cancelPaymentRequestTransfer;
    }

    public function getPaymentTransferByTransactionId(string $transactionId): PaymentTransfer
    {
        return (new AppPaymentRepository())->getPaymentByTransactionId($transactionId);
    }

    public function dontSeePaymentByTenantIdentifier(string $tenantIdentifier): void
    {
        $paymentQuery = SpyPaymentQuery::create();
        $paymentQuery->filterByTenantIdentifier($tenantIdentifier);

        $this->assertEmpty($paymentQuery->find(), 'Found payment by tenant identifier');
    }

    public function seePaymentByTenantIdentifier(string $tenantIdentifier): void
    {
        $paymentQuery = SpyPaymentQuery::create();
        $paymentQuery->filterByTenantIdentifier($tenantIdentifier);

        $this->assertGreaterThan(0, $paymentQuery->find()->count(), 'Did not find payment by tenant identifier');
    }

    // Transfer related code

    public function havePaymentTransmissionsRequestTransfer(array $seed = [], array $paymentTransmissionsSeed = []): PaymentTransmissionsRequestTransfer
    {
        $paymentTransmissionsRequestTransferBuilder = new PaymentTransmissionsRequestBuilder($seed);

        foreach ($paymentTransmissionsSeed as $paymentTransmissionSeed) {
            $paymentTransmissionBuilder = new PaymentTransmissionBuilder($paymentTransmissionSeed);
            $paymentTransmissionsRequestTransferBuilder->withPaymentTransmission($paymentTransmissionBuilder);
        }

        return $paymentTransmissionsRequestTransferBuilder->build();
    }

    // Transfer related code

    public function havePaymentTransmissionsResponseTransfer(array $seed = [], array $paymentTransmissionsSeed = []): PaymentTransmissionsResponseTransfer
    {
        $paymentTransmissionsResponseTransferBuilder = new PaymentTransmissionsResponseBuilder($seed);

        foreach ($paymentTransmissionsSeed as $paymentTransmissionSeed) {
            $paymentTransmissionBuilder = new PaymentTransmissionBuilder($paymentTransmissionSeed);
            $paymentTransmissionsResponseTransferBuilder->withPaymentTransmission($paymentTransmissionBuilder);
        }

        return $paymentTransmissionsResponseTransferBuilder->build();
    }

    public function assertPaymentTransferEqualsPaymentTransmission(
        string $transferId,
        PaymentTransmissionTransfer $paymentTransmissionTransfer,
        ?string $merchantReference = null
    ): void {
        $paymentTransferEntity = SpyPaymentTransferQuery::create()->findOneByTransferId($transferId);

        $this->assertInstanceOf(SpyPaymentTransfer::class, $paymentTransferEntity, 'Payment transfer not found in the database');
        $this->assertEquals($paymentTransmissionTransfer->getTenantIdentifier(), $paymentTransferEntity->getTenantIdentifier(), 'Tenant Identifier does not match');
        $this->assertEquals($paymentTransmissionTransfer->getTransactionId(), $paymentTransferEntity->getTransactionId(), 'Transaction ID does not match');
        $this->assertEquals($paymentTransmissionTransfer->getTransferId(), $paymentTransferEntity->getTransferId(), 'Transfer ID does not match');
        $this->assertEquals($paymentTransmissionTransfer->getOrderReference(), $paymentTransferEntity->getOrderReference(), 'Order references does not match');
        $this->assertEquals(implode(',', $paymentTransmissionTransfer->getItemReferences()), $paymentTransferEntity->getItemReferences(), 'Item references does not match');
        $this->assertEquals($paymentTransmissionTransfer->getAmount(), $paymentTransferEntity->getAmount(), 'Amount does not match');

        if ($merchantReference) {
            $this->assertEquals($merchantReference, $paymentTransferEntity->getMerchantReference(), 'Merchant reference does not match');
        }
    }

    public function assertPaymentTransferIsNotPersisted(
        string $tenantIdentifier,
        string $merchantReference
    ): void {
        $paymentTransferEntity = SpyPaymentTransferQuery::create()
            ->filterByTenantIdentifier($tenantIdentifier)
            ->filterByMerchantReference($merchantReference)
            ->findOne();

        $this->assertNull($paymentTransferEntity, 'Payment transfer is persisted which is not expected. Only successful transmissions have to be persisted on the App side.');
    }

    public function haveOrderItem(array $seed = []): OrderItemTransfer
    {
        return (new OrderItemBuilder($seed))->build();
    }

    /**
     * @param array<\Generated\Shared\Transfer\OrderItemTransfer> $expectedOrderItems
     */
    public function assertPaymentTransmissionEquals(
        PaymentTransmissionTransfer $paymentTransmissionTransfer,
        array $expectedOrderItems,
        ?string $merchantReference = null
    ): void {
        if ($merchantReference) {
            $this->assertSame($merchantReference, $paymentTransmissionTransfer->getMerchantOrFail()->getMerchantReference(), 'Expected to have the same Merchant Reference but got different ones.');
        }

        /** @var \ArrayObject<int, \Generated\Shared\Transfer\OrderItemTransfer> $orderItems */
        $orderItems = $paymentTransmissionTransfer->getOrderItems();
        $this->assertCount(count($expectedOrderItems), $orderItems);

        foreach ($expectedOrderItems as $position => $expectedOrderItemTransfer) {
            $this->assertSame($expectedOrderItemTransfer->getOrderReference(), $orderItems[$position]->getOrderReference(), 'Expected to have the same Order Reference but got different ones.');
            $this->assertSame($expectedOrderItemTransfer->getItemReference(), $orderItems[$position]->getItemReference(), 'Expected to have the same Item Reference but got different ones.');
        }
    }
}
