<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\AsyncApi\AppPayment\AppPaymentTests\PaymentEvents;

use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\AppConfigTransfer;
use Generated\Shared\Transfer\ConfirmPreOrderPaymentRequestTransfer;
use Generated\Shared\Transfer\ConfirmPreOrderPaymentResponseTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\PaymentUpdatedTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformConfirmPreOrderPluginInterface;
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
 * @group PaymentUpdatedTest
 * Add your own group annotations below this line
 */
class PaymentUpdatedTest extends Unit
{
    use DependencyHelperTrait;

    protected AppPaymentAsyncApiTester $tester;

    public function testPaymentUpdatedMessageIsSendAfterAPreOrderPaymentWasConfirmed(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();

        $confirmPreOrderPaymentRequestTransfer = $this->tester->haveConfirmPreOrderPaymentRequestTransfer([
            ConfirmPreOrderPaymentRequestTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
        ]);

        $this->tester->haveAppConfigForTenant($confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $paymentTransfer = $this->tester->havePaymentForTransactionId($confirmPreOrderPaymentRequestTransfer->getTransactionId(), $confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $confirmPreOrderPaymentRequestTransfer->setOrderData($paymentTransfer->getQuote());

        $confirmPreOrderPaymentResponseTransfer = new ConfirmPreOrderPaymentResponseTransfer();
        $confirmPreOrderPaymentResponseTransfer
            ->setIsSuccessful(true);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformConfirmPreOrderPluginInterface::class, [
            'confirmPreOrderPayment' => function (ConfirmPreOrderPaymentRequestTransfer $confirmPreOrderPaymentRequestTransfer) use ($confirmPreOrderPaymentResponseTransfer) {
                $paymentTransfer = $confirmPreOrderPaymentRequestTransfer->getPayment();

                // Ensure that the AppConfig is always passed to the platform plugin.
                $this->assertInstanceOf(AppConfigTransfer::class, $confirmPreOrderPaymentRequestTransfer->getAppConfig());
                $this->assertInstanceOf(PaymentTransfer::class, $paymentTransfer);

                return $confirmPreOrderPaymentResponseTransfer->setStatus(PaymentStatus::STATUS_AUTHORIZED);
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        $this->tester->getFacade()->confirmPreOrderPayment($confirmPreOrderPaymentRequestTransfer);
        $paymentUpdatedTransfer = $this->tester->havePaymentUpdatedTransfer();

        // Assert
        $this->tester->assertMessageWasEmittedOnChannel($paymentUpdatedTransfer, 'payment-events');
    }

    public function testSourceAndTargetStateArePersistedWhenThePaymentStatusHasChanged(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();

        $confirmPreOrderPaymentRequestTransfer = $this->tester->haveConfirmPreOrderPaymentRequestTransfer([
            ConfirmPreOrderPaymentRequestTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
        ]);

        $this->tester->haveAppConfigForTenant($confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $paymentTransfer = $this->tester->havePaymentForTransactionId($confirmPreOrderPaymentRequestTransfer->getTransactionId(), $confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $confirmPreOrderPaymentRequestTransfer->setOrderData($paymentTransfer->getQuote());

        $confirmPreOrderPaymentResponseTransfer = new ConfirmPreOrderPaymentResponseTransfer();
        $confirmPreOrderPaymentResponseTransfer
            ->setIsSuccessful(true);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformConfirmPreOrderPluginInterface::class, [
            'confirmPreOrderPayment' => function (ConfirmPreOrderPaymentRequestTransfer $confirmPreOrderPaymentRequestTransfer) use ($confirmPreOrderPaymentResponseTransfer) {
                $paymentTransfer = $confirmPreOrderPaymentRequestTransfer->getPayment();

                // Ensure that the AppConfig is always passed to the platform plugin.
                $this->assertInstanceOf(AppConfigTransfer::class, $confirmPreOrderPaymentRequestTransfer->getAppConfig());
                $this->assertInstanceOf(PaymentTransfer::class, $paymentTransfer);

                return $confirmPreOrderPaymentResponseTransfer->setStatus('updated');
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        $this->tester->getFacade()->confirmPreOrderPayment($confirmPreOrderPaymentRequestTransfer);

        // Assert
        $this->tester->assertPaymentStatusHistory('updated', $paymentTransfer->getTransactionId());
        $paymentUpdatedTransfer = $this->tester->havePaymentUpdatedTransfer();

        $this->tester->assertMessageWasEmittedOnChannel($paymentUpdatedTransfer, 'payment-events', function (PaymentUpdatedTransfer $usedPaymentUpdatedTransfer, PaymentUpdatedTransfer $sentPaymentUpdatedTransfer): void {
            $detailsArray = json_decode($sentPaymentUpdatedTransfer->getDetails(), true);

            $this->assertSame($detailsArray['sourceStatus'], 'new');
            $this->assertSame($detailsArray['targetStatus'], 'updated');
        });
    }
}
