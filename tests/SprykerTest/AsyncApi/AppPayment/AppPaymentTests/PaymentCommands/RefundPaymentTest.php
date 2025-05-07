<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\AsyncApi\AppPayment\AppPaymentTests\PaymentCommands;

use Codeception\Stub;
use Codeception\Test\Unit;
use Exception;
use Generated\Shared\Transfer\AppConfigTransfer;
use Generated\Shared\Transfer\MessageAttributesTransfer;
use Generated\Shared\Transfer\PaymentRefundedTransfer;
use Generated\Shared\Transfer\PaymentRefundFailedTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\PaymentUpdatedTransfer;
use Generated\Shared\Transfer\RefundPaymentRequestTransfer;
use Generated\Shared\Transfer\RefundPaymentResponseTransfer;
use Generated\Shared\Transfer\RefundPaymentTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Zed\AppKernel\AppKernelConfig;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Business\Payment\Refund\PaymentRefundStatus;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;
use SprykerTest\AsyncApi\AppPayment\AppPaymentAsyncApiTester;
use SprykerTest\Shared\Testify\Helper\DependencyHelperTrait;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group AsyncApi
 * @group AppPayment
 * @group AppPaymentTests
 * @group PaymentCommands
 * @group RefundPaymentTest
 * Add your own group annotations below this line
 */
class RefundPaymentTest extends Unit
{
    use DependencyHelperTrait;

    protected AppPaymentAsyncApiTester $tester;

    public function testGivenTheAppIsMarkedAsDisconnectedWhenTheMessageHandlerIsExecutedThenThePaymentStatusIsNotChangedAndNoMessageIsSent(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();
        $refundId = Uuid::uuid4()->toString();
        $this->tester->haveAppConfigForTenant($tenantIdentifier, [], true, AppKernelConfig::APP_STATUS_DISCONNECTED);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_CAPTURED);

        $refundPaymentTransfer = $this->tester->haveRefundPaymentTransfer([
            MessageAttributesTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            RefundPaymentTransfer::ORDER_REFERENCE => $paymentTransfer->getOrderReference(),
        ]);

        // Act: This will trigger the MessageHandlerPlugin for this message.
        $this->tester->runMessageReceiveTest($refundPaymentTransfer, 'payment-commands');

        // Assert
        $this->tester->assertMessageWasNotSent(PaymentRefundedTransfer::class);
    }

    public function testRefundPaymentMessageSendsPaymentRefundedMessageAndCreatesPaymentRefundWhenPaymentIsInCapturedState(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();
        $refundId = Uuid::uuid4()->toString();
        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_CAPTURED);

        $refundPaymentTransfer = $this->tester->haveRefundPaymentTransfer([
            MessageAttributesTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            RefundPaymentTransfer::ORDER_REFERENCE => $paymentTransfer->getOrderReference(),
        ]);
        $refundPaymentResponseTransfer = (new RefundPaymentResponseTransfer())
            ->setIsSuccessful(true)
            ->setRefundId($refundId)
            ->setStatus(PaymentRefundStatus::SUCCEEDED);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'refundPayment' => function (RefundPaymentRequestTransfer $refundPaymentRequestTransfer) use ($refundPaymentResponseTransfer) {
                $this->assertInstanceOf(AppConfigTransfer::class, $refundPaymentRequestTransfer->getAppConfig());
                $this->assertInstanceOf(PaymentTransfer::class, $refundPaymentRequestTransfer->getPayment());

                return $refundPaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act: This will trigger the MessageHandlerPlugin for this message.
        $this->tester->runMessageReceiveTest($refundPaymentTransfer, 'payment-commands');

        // Assert
        $this->tester->assertPaymentRefundIsInStatus($refundId, PaymentRefundStatus::SUCCEEDED);
        $this->tester->assertPaymentIsInState($transactionId, PaymentStatus::STATUS_REFUNDED);
        $this->tester->assertMessageWasSent(PaymentRefundedTransfer::class);
    }

    public function testRefundPaymentMessageSendsPaymentRefundedMessageAndCreatesPaymentRefundWhenPaymentIsInCapturedStateAndPersistsDetailsFromTheImplementationReturnedPaymentAndSendsAPaymentUpdatedMessage(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();
        $refundId = Uuid::uuid4()->toString();
        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_CAPTURED);

        $refundPaymentTransfer = $this->tester->haveRefundPaymentTransfer([
            MessageAttributesTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            RefundPaymentTransfer::ORDER_REFERENCE => $paymentTransfer->getOrderReference(),
        ]);
        $refundPaymentResponseTransfer = (new RefundPaymentResponseTransfer())
            ->setIsSuccessful(true)
            ->setRefundId($refundId)
            ->setStatus(PaymentRefundStatus::SUCCEEDED);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'refundPayment' => function (RefundPaymentRequestTransfer $refundPaymentRequestTransfer) use ($refundPaymentResponseTransfer, $paymentTransfer) {
                $this->assertInstanceOf(AppConfigTransfer::class, $refundPaymentRequestTransfer->getAppConfig());
                $this->assertInstanceOf(PaymentTransfer::class, $refundPaymentRequestTransfer->getPayment());

                $paymentTransfer->setDetails('{"foo":"bar"}');
                $refundPaymentResponseTransfer->setPayment($paymentTransfer);

                return $refundPaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act: This will trigger the MessageHandlerPlugin for this message.
        $this->tester->runMessageReceiveTest($refundPaymentTransfer, 'payment-commands');

        // Assert
        $this->tester->assertPaymentRefundIsInStatus($refundId, PaymentRefundStatus::SUCCEEDED);
        $this->tester->assertPaymentHasDetails($paymentTransfer, '{"foo":"bar"}');
        $this->tester->assertMessageWasSent(PaymentRefundedTransfer::class);
        $this->tester->assertMessageWasSent(PaymentUpdatedTransfer::class);
    }

    /**
     * @dataProvider refundSuccessfulPathStatusDataProvider
     *
     * @param \Spryker\Zed\AppPayment\Business\Payment\Refund\PaymentRefundStatus $paymentRefundStatus
     *
     * @return void
     */
    public function testRefundPaymentMessageSendsPaymentRefundFailedMessageWhenPaymentRefundAlreadyExists(
        string $paymentRefundStatus
    ): void {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();
        $refundId = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_CAPTURED);
        $paymentRefundTransfer = $this->tester->havePaymentRefundForTransactionIdAndRefundId($transactionId, $refundId, $paymentRefundStatus);

        $refundPaymentTransfer = $this->tester->haveRefundPaymentTransfer([
            MessageAttributesTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            RefundPaymentTransfer::ORDER_REFERENCE => $paymentTransfer->getOrderReference(),
        ], $paymentRefundTransfer->getOrderItemIds());

        // Act: This will trigger the MessageHandlerPlugin for this message.
        $this->tester->runMessageReceiveTest($refundPaymentTransfer, 'payment-commands');

        // Assert
        $this->tester->assertMessageWasSent(PaymentRefundFailedTransfer::class);
    }

    /**
     * @dataProvider refundFailureStatusDataProvider
     *
     * @param \Spryker\Zed\AppPayment\Business\Payment\Refund\PaymentRefundStatus $paymentRefundStatus
     *
     * @return void
     */
    public function testRefundPaymentMessageSendsPaymentRefundedMessageAndCreatesPaymentRefundWhenPaymentIsRefundableAndFailurePaymentRefundAlreadyExists(
        string $paymentRefundStatus
    ): void {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();
        $oldRefundId = Uuid::uuid4()->toString();
        $newRefundId = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_CAPTURED);
        $paymentRefundTransfer = $this->tester->havePaymentRefundForTransactionIdAndRefundId($transactionId, $oldRefundId, $paymentRefundStatus);

        $refundPaymentTransfer = $this->tester->haveRefundPaymentTransfer([
            MessageAttributesTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            RefundPaymentTransfer::ORDER_REFERENCE => $paymentTransfer->getOrderReference(),
        ], $paymentRefundTransfer->getOrderItemIds());
        $refundPaymentResponseTransfer = (new RefundPaymentResponseTransfer())
            ->setIsSuccessful(true)
            ->setRefundId($newRefundId)
            ->setStatus(PaymentRefundStatus::SUCCEEDED);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'refundPayment' => function (RefundPaymentRequestTransfer $refundPaymentRequestTransfer) use ($refundPaymentResponseTransfer) {
                $this->assertInstanceOf(AppConfigTransfer::class, $refundPaymentRequestTransfer->getAppConfig());
                $this->assertInstanceOf(PaymentTransfer::class, $refundPaymentRequestTransfer->getPayment());

                return $refundPaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act: This will trigger the MessageHandlerPlugin for this message.
        $this->tester->runMessageReceiveTest($refundPaymentTransfer, 'payment-commands');

        // Assert
        $this->tester->assertPaymentRefundIsInStatus($oldRefundId, $paymentRefundStatus);
        $this->tester->assertPaymentRefundIsInStatus($newRefundId, PaymentRefundStatus::SUCCEEDED);
        $this->tester->assertMessageWasSent(PaymentRefundedTransfer::class);
    }

    public function testRefundPaymentMessageSendsPaymentRefundFailedMessageWhenPaymentIsNotRefundable(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();
        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_AUTHORIZED);

        $refundPaymentTransfer = $this->tester->haveRefundPaymentTransfer([
            MessageAttributesTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            RefundPaymentTransfer::ORDER_REFERENCE => $paymentTransfer->getOrderReference(),
        ]);

        // Act: This will trigger the MessageHandlerPlugin for this message.
        $this->tester->runMessageReceiveTest($refundPaymentTransfer, 'payment-commands');

        // Assert
        $this->tester->assertMessageWasSent(PaymentRefundFailedTransfer::class);
    }

    public function testRefundPaymentMessageSendsPaymentRefundFailedMessageAndCreatesPaymentRefundWhenPlatformPluginDeclinePaymentRefund(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();
        $refundId = Uuid::uuid4()->toString();
        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_CAPTURED);

        $refundPaymentTransfer = $this->tester->haveRefundPaymentTransfer([
            MessageAttributesTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            RefundPaymentTransfer::ORDER_REFERENCE => $paymentTransfer->getOrderReference(),
        ]);
        $refundPaymentResponseTransfer = (new RefundPaymentResponseTransfer())
            ->setIsSuccessful(true)
            ->setRefundId($refundId)
            ->setStatus(PaymentRefundStatus::FAILED);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'refundPayment' => function (RefundPaymentRequestTransfer $refundPaymentRequestTransfer) use ($refundPaymentResponseTransfer) {
                $this->assertInstanceOf(AppConfigTransfer::class, $refundPaymentRequestTransfer->getAppConfig());
                $this->assertInstanceOf(PaymentTransfer::class, $refundPaymentRequestTransfer->getPayment());

                return $refundPaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act: This will trigger the MessageHandlerPlugin for this message.
        $this->tester->runMessageReceiveTest($refundPaymentTransfer, 'payment-commands');

        // Assert
        $this->tester->assertPaymentRefundIsInStatus($refundId, PaymentRefundStatus::FAILED);
        $this->tester->assertMessageWasSent(PaymentRefundFailedTransfer::class);
    }

    public function testRefundPaymentMessageSendsPaymentRefundFailedMessageWhenPlatformPluginFailsRefundRequest(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();
        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_CAPTURED);

        $refundPaymentTransfer = $this->tester->haveRefundPaymentTransfer([
            MessageAttributesTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            RefundPaymentTransfer::ORDER_REFERENCE => $paymentTransfer->getOrderReference(),
        ]);
        $refundPaymentResponseTransfer = (new RefundPaymentResponseTransfer())
            ->setIsSuccessful(false)
            ->setStatus(PaymentRefundStatus::FAILED);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'refundPayment' => function (RefundPaymentRequestTransfer $refundPaymentRequestTransfer) use ($refundPaymentResponseTransfer) {
                $this->assertInstanceOf(AppConfigTransfer::class, $refundPaymentRequestTransfer->getAppConfig());
                $this->assertInstanceOf(PaymentTransfer::class, $refundPaymentRequestTransfer->getPayment());

                return $refundPaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act: This will trigger the MessageHandlerPlugin for this message.
        $this->tester->runMessageReceiveTest($refundPaymentTransfer, 'payment-commands');

        // Assert
        $this->tester->assertMessageWasSent(PaymentRefundFailedTransfer::class);
    }

    public function testRefundPaymentMessageSendsPaymentRefundFailedMessageForSpecificOrderItemsWhenSomeOfRequestedItemAreAlreadyInProgressOrProcessed(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();
        $oldRefundId = Uuid::uuid4()->toString();
        $orderItemsIds = [111, 112, 113];

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_CAPTURED);
        $paymentRefundTransfer = $this->tester->havePaymentRefundForTransactionIdAndRefundId($transactionId, $oldRefundId, PaymentRefundStatus::SUCCEEDED);

        $refundPaymentTransfer = $this->tester->haveRefundPaymentTransfer([
            MessageAttributesTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            RefundPaymentTransfer::ORDER_REFERENCE => $paymentTransfer->getOrderReference(),
        ], array_merge($paymentRefundTransfer->getOrderItemIds(), $orderItemsIds));

        $paymentRefundFailedTransfer = $this->tester->havePaymentRefundFailedTransfer([
            PaymentRefundedTransfer::ORDER_REFERENCE => $paymentTransfer->getOrderReference(),
            PaymentRefundedTransfer::ORDER_ITEM_IDS => $orderItemsIds,
            PaymentRefundedTransfer::AMOUNT => $refundPaymentTransfer->getAmount(),
        ]);

        // Act: This will trigger the MessageHandlerPlugin for this message.
        $this->tester->runMessageReceiveTest($refundPaymentTransfer, 'payment-commands');

        // Assert
        $this->tester->assertMessageWasEmittedOnChannel(
            $paymentRefundFailedTransfer,
            'payment-events',
            function (PaymentRefundFailedTransfer $messageTransfer, PaymentRefundFailedTransfer $sentMessageTransfer): void {
                $this->assertSame($messageTransfer->getOrderReference(), $sentMessageTransfer->getOrderReference());
                $this->assertEqualsCanonicalizing($messageTransfer->getOrderItemIds(), $sentMessageTransfer->getOrderItemIds());
                $this->assertSame($messageTransfer->getAmount(), $sentMessageTransfer->getAmount());
            },
        );
    }

    public function testRefundPaymentMessageSendsPaymentRefundFailedMessageWhenPlatformPluginThrowsException(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();
        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_CAPTURED);

        $refundPaymentTransfer = $this->tester->haveRefundPaymentTransfer([
            MessageAttributesTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            RefundPaymentTransfer::ORDER_REFERENCE => $paymentTransfer->getOrderReference(),
        ]);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'refundPayment' => function (): void {
                throw new Exception();
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act: This will trigger the MessageHandlerPlugin for this message.
        $this->tester->runMessageReceiveTest($refundPaymentTransfer, 'payment-commands');

        // Assert
        $this->tester->assertMessageWasSent(PaymentRefundFailedTransfer::class);
    }

    public function refundStatusDataProvider(): array
    {
        return [
            'succeeded refund' => [PaymentRefundStatus::SUCCEEDED],
            'failed refund' => [PaymentRefundStatus::FAILED],
            'canceled refund' => [PaymentRefundStatus::CANCELED],
            'pending refund' => [PaymentRefundStatus::PENDING],
            'processing refund' => [PaymentRefundStatus::PROCESSING],
        ];
    }

    public function refundFailureStatusDataProvider(): array
    {
        return [
            'failed refund' => [PaymentRefundStatus::FAILED],
            'canceled refund' => [PaymentRefundStatus::CANCELED],
        ];
    }

    public function refundSuccessfulPathStatusDataProvider(): array
    {
        return [
            'pending refund' => [PaymentRefundStatus::PENDING],
            'processing refund' => [PaymentRefundStatus::PROCESSING],
            'succeeded refund' => [PaymentRefundStatus::SUCCEEDED],
        ];
    }
}
