<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\AsyncApi\AppPayment\AppPaymentTests\PaymentEvents;

use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\AppConfigTransfer;
use Generated\Shared\Transfer\CancelPaymentRequestTransfer;
use Generated\Shared\Transfer\CancelPaymentResponseTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\PaymentUpdatedTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
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
 * @group PaymentEvents
 * @group PaymentCancellationFailedTest
 * Add your own group annotations below this line
 */
class PaymentCancellationFailedTest extends Unit
{
    use DependencyHelperTrait;

    protected AppPaymentAsyncApiTester $tester;

    /**
     * The PaymentCancellationFailed message is sent when the payment should be cancelled but the PaymentPluginInterface implementation returns a failed response.
     */
    public function testPaymentCancellationFailedMessageIsSendWhenPlatformPluginReturnsCancellationFailedStatus(): void
    {
        // Arrange
        $paymentCancellationFailedTransfer = $this->tester->havePaymentCancellationFailedTransfer();

        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();
        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier);

        $cancelPaymentTransfer = $this->tester->haveCancelPaymentTransfer(['tenantIdentifier' => $tenantIdentifier, 'orderReference' => $paymentTransfer->getOrderReference()]);
        $cancelPaymentResponseTransfer = (new CancelPaymentResponseTransfer())
            ->setIsSuccessful(true)
            ->setTransactionId($transactionId)
            ->setStatus(PaymentStatus::STATUS_CANCELLATION_FAILED);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'cancelPayment' => function (CancelPaymentRequestTransfer $cancelPaymentRequestTransfer) use ($cancelPaymentResponseTransfer) {
                $this->assertInstanceOf(AppConfigTransfer::class, $cancelPaymentRequestTransfer->getAppConfig());
                $this->assertInstanceOf(PaymentTransfer::class, $cancelPaymentRequestTransfer->getPayment());

                return $cancelPaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->getFacade()->handleCancelPayment($cancelPaymentTransfer);

        // Assert
        $this->tester->assertMessageWasEmittedOnChannel($paymentCancellationFailedTransfer, 'payment-events');
    }

    /**
     * The PaymentCancellationFailed message is sent when the payment should be cancelled but the PaymentPluginInterface implementation returns a failed response.
     */
    public function testPaymentUpdatedMessageIsSentWithSourceAndTargetStatus(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();
        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier);

        $cancelPaymentTransfer = $this->tester->haveCancelPaymentTransfer(['tenantIdentifier' => $tenantIdentifier, 'orderReference' => $paymentTransfer->getOrderReference()]);
        $cancelPaymentResponseTransfer = (new CancelPaymentResponseTransfer())
            ->setIsSuccessful(true)
            ->setTransactionId($transactionId)
            ->setStatus(PaymentStatus::STATUS_CANCELLATION_FAILED);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'cancelPayment' => function (CancelPaymentRequestTransfer $cancelPaymentRequestTransfer) use ($cancelPaymentResponseTransfer) {
                $this->assertInstanceOf(AppConfigTransfer::class, $cancelPaymentRequestTransfer->getAppConfig());
                $this->assertInstanceOf(PaymentTransfer::class, $cancelPaymentRequestTransfer->getPayment());

                return $cancelPaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->getFacade()->handleCancelPayment($cancelPaymentTransfer);

        // Assert
        $paymentUpdatedTransfer = $this->tester->havePaymentUpdatedTransfer();

        $this->tester->assertMessageWasEmittedOnChannel($paymentUpdatedTransfer, 'payment-events', function (PaymentUpdatedTransfer $usedPaymentUpdatedTransfer, PaymentUpdatedTransfer $sentPaymentUpdatedTransfer): void {
            $detailsArray = json_decode($sentPaymentUpdatedTransfer->getDetails(), true);

            $this->assertSame($detailsArray['sourceStatus'], PaymentStatus::STATUS_NEW);
            $this->assertSame($detailsArray['targetStatus'], PaymentStatus::STATUS_CANCELLATION_FAILED);
        });
    }
}
