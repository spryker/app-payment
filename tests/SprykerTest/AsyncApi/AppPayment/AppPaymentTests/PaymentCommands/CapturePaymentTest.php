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
use Generated\Shared\Transfer\CapturePaymentRequestTransfer;
use Generated\Shared\Transfer\CapturePaymentResponseTransfer;
use Generated\Shared\Transfer\PaymentCapturedTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\PaymentUpdatedTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Zed\AppKernel\AppKernelConfig;
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
 * @group PaymentCommands
 * @group CapturePaymentTest
 * Add your own group annotations below this line
 */
class CapturePaymentTest extends Unit
{
    use DependencyHelperTrait;

    protected AppPaymentAsyncApiTester $tester;

    public function testGivenTheAppIsMarkedAsDisconnectedWhenTheMessageHandlerIsExecutedThenThePaymentStatusIsNotChangedAndNoMessageIsSent(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();
        $this->tester->haveAppConfigForTenant($tenantIdentifier, [], true, AppKernelConfig::APP_STATUS_DISCONNECTED);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_NEW);

        $capturePaymentTransfer = $this->tester->haveCapturePaymentTransfer(['tenantIdentifier' => $tenantIdentifier, 'orderReference' => $paymentTransfer->getOrderReference()]);

        // Act: This will trigger the MessageHandlerPlugin for this message.
        $this->tester->runMessageReceiveTest($capturePaymentTransfer, 'payment-commands');

        // Assert
        $this->tester->assertPaymentHasStatus($paymentTransfer, PaymentStatus::STATUS_NEW);
        $this->tester->assertMessageWasNotSent(PaymentCapturedTransfer::class);
    }

    public function testHandleCapturePaymentMessageUpdatesPaymentToCapturedRequested(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();
        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier);

        $capturePaymentTransfer = $this->tester->haveCapturePaymentTransfer(['tenantIdentifier' => $tenantIdentifier, 'orderReference' => $paymentTransfer->getOrderReference()]);

        $capturePaymentResponseTransfer = (new CapturePaymentResponseTransfer())
            ->setIsSuccessful(true)
            ->setTransactionId($transactionId)
            ->setStatus(PaymentStatus::STATUS_CAPTURE_REQUESTED);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'capturePayment' => function (CapturePaymentRequestTransfer $capturePaymentRequestTransfer) use ($capturePaymentResponseTransfer) {
                $this->assertInstanceOf(AppConfigTransfer::class, $capturePaymentRequestTransfer->getAppConfig());
                $this->assertInstanceOf(PaymentTransfer::class, $capturePaymentRequestTransfer->getPayment());

                return $capturePaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act: This will trigger the MessageHandlerPlugin for this message.
        $this->tester->runMessageReceiveTest($capturePaymentTransfer, 'payment-commands');

        // Assert
        $this->tester->assertPaymentHasStatus($paymentTransfer, PaymentStatus::STATUS_CAPTURE_REQUESTED);
    }

    public function testHandleCapturePaymentMessageUpdatesPaymentToCapturedRequestedAndPersistsDetailsFromTheImplementationReturnedPayment(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();
        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier);

        $capturePaymentTransfer = $this->tester->haveCapturePaymentTransfer(['tenantIdentifier' => $tenantIdentifier, 'orderReference' => $paymentTransfer->getOrderReference()]);

        $capturePaymentResponseTransfer = (new CapturePaymentResponseTransfer())
            ->setIsSuccessful(true)
            ->setTransactionId($transactionId)
            ->setStatus(PaymentStatus::STATUS_CAPTURE_REQUESTED);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'capturePayment' => function (CapturePaymentRequestTransfer $capturePaymentRequestTransfer) use ($capturePaymentResponseTransfer, $paymentTransfer) {
                $this->assertInstanceOf(AppConfigTransfer::class, $capturePaymentRequestTransfer->getAppConfig());
                $this->assertInstanceOf(PaymentTransfer::class, $capturePaymentRequestTransfer->getPayment());

                $paymentTransfer->setDetails('{"foo":"bar"}');
                $capturePaymentResponseTransfer->setPayment($paymentTransfer);

                return $capturePaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act: This will trigger the MessageHandlerPlugin for this message.
        $this->tester->runMessageReceiveTest($capturePaymentTransfer, 'payment-commands');

        // Assert
        $this->tester->assertPaymentHasStatus($paymentTransfer, PaymentStatus::STATUS_CAPTURE_REQUESTED);
        $this->tester->assertPaymentHasDetails($paymentTransfer, '{"foo":"bar"}');
        $this->tester->assertMessageWasSent(PaymentUpdatedTransfer::class);
    }

    /**
     * Covers partial capture. When the amount to be captured is set in the message this amount will be captured (WIP: Partial capture is not fully implemented).
     */
    public function testHandleCapturePaymentMessageUpdatesPaymentToCapturedRequestedAndSetsAmountToBeCaptured(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();
        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier);

        $capturePaymentTransfer = $this->tester->haveCapturePaymentTransfer(['tenantIdentifier' => $tenantIdentifier, 'orderReference' => $paymentTransfer->getOrderReference()]);

        $capturePaymentResponseTransfer = (new CapturePaymentResponseTransfer())
            ->setIsSuccessful(true)
            ->setTransactionId($transactionId)
            ->setStatus(PaymentStatus::STATUS_CAPTURE_REQUESTED);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'capturePayment' => function (CapturePaymentRequestTransfer $capturePaymentRequestTransfer) use ($capturePaymentResponseTransfer) {
                $this->assertInstanceOf(AppConfigTransfer::class, $capturePaymentRequestTransfer->getAppConfig());
                $this->assertInstanceOf(PaymentTransfer::class, $capturePaymentRequestTransfer->getPayment());

                return $capturePaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act: This will trigger the MessageHandlerPlugin for this message.
        $this->tester->runMessageReceiveTest($capturePaymentTransfer, 'payment-commands');

        // Assert
        $this->tester->assertPaymentHasStatus($paymentTransfer, PaymentStatus::STATUS_CAPTURE_REQUESTED);
    }

    /**
     * @deprecated This method will be removed when all Tenants are using the tenantIdentifier instead of the storeReference.
     */
    public function testHandleCapturePaymentMessageUpdatesPaymentToCapturedRequestedWithStoreReference(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();
        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier);

        $capturePaymentTransfer = $this->tester->haveCapturePaymentTransfer(['tenantIdentifier' => null, 'storeReference' => $tenantIdentifier, 'orderReference' => $paymentTransfer->getOrderReference()]);

        $capturePaymentResponseTransfer = (new CapturePaymentResponseTransfer())
            ->setIsSuccessful(true)
            ->setTransactionId($transactionId)
            ->setStatus(PaymentStatus::STATUS_CAPTURE_REQUESTED);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'capturePayment' => function (CapturePaymentRequestTransfer $capturePaymentRequestTransfer) use ($capturePaymentResponseTransfer) {
                $this->assertInstanceOf(AppConfigTransfer::class, $capturePaymentRequestTransfer->getAppConfig());
                $this->assertInstanceOf(PaymentTransfer::class, $capturePaymentRequestTransfer->getPayment());

                return $capturePaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act: This will trigger the MessageHandlerPlugin for this message.
        $this->tester->runMessageReceiveTest($capturePaymentTransfer, 'payment-commands');

        // Assert
        $this->tester->assertPaymentHasStatus($paymentTransfer, PaymentStatus::STATUS_CAPTURE_REQUESTED);
    }

    public function testHandleCapturePaymentDoesNotThrowExceptionWhenPaymentDoesNotExist(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $this->tester->haveAppConfigForTenant($tenantIdentifier);

        $capturePaymentTransfer = $this->tester->haveCapturePaymentTransfer(['tenantIdentifier' => $tenantIdentifier, 'orderReference' => Uuid::uuid4()->toString()]);

        // Act
        $this->tester->runMessageReceiveTest($capturePaymentTransfer, 'payment-commands');
    }

    public function testHandleCapturePaymentUpdatesPaymentToCaptureFailedWhenPlatformPluginThrowsException(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();
        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier);

        $capturePaymentTransfer = $this->tester->haveCapturePaymentTransfer(['tenantIdentifier' => $tenantIdentifier, 'orderReference' => $paymentTransfer->getOrderReference()]);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'capturePayment' => static function (): never {
                throw new Exception();
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->runMessageReceiveTest($capturePaymentTransfer, 'payment-commands');

        // Assert
        $this->tester->assertPaymentHasStatus($paymentTransfer, PaymentStatus::STATUS_CAPTURE_FAILED);
    }
}
