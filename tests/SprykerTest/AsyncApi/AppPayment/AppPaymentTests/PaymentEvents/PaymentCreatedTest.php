<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\AsyncApi\AppPayment\PaymentTests\PaymentEvents;

use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\AppConfigTransfer;
use Generated\Shared\Transfer\InitializePaymentRequestTransfer;
use Generated\Shared\Transfer\InitializePaymentResponseTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;
use SprykerTest\AsyncApi\AppPayment\AppPaymentAsyncApiTester;
use SprykerTest\Shared\Testify\Helper\DependencyHelperTrait;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group AsyncApi
 * @group AppPayment
 * @group PaymentTests
 * @group PaymentEvents
 * @group PaymentCreatedTest
 * Add your own group annotations below this line
 */
class PaymentCreatedTest extends Unit
{
    use DependencyHelperTrait;

    protected AppPaymentAsyncApiTester $tester;

    public function testPaymentCreatedMessageIsSendAfterAPaymentWasInitialized(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);

        $initializePaymentRequestTransfer = $this->tester->haveInitializePaymentRequestTransfer([
            InitializePaymentRequestTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
        ]);

        $initializePaymentResponseTransfer = new InitializePaymentResponseTransfer();
        $initializePaymentResponseTransfer
            ->setIsSuccessful(true)
            ->setTransactionId($transactionId);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'initializePayment' => function (InitializePaymentRequestTransfer $initializePaymentRequestTransfer) use ($initializePaymentResponseTransfer) {
                // Ensure that the AppConfig is always passed to the platform plugin.
                $this->assertInstanceOf(AppConfigTransfer::class, $initializePaymentRequestTransfer->getAppConfig());

                return $initializePaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        $this->tester->getFacade()->initializePayment($initializePaymentRequestTransfer);
        $paymentCreatedTransfer = $this->tester->havePaymentCreatedTransfer();

        // Assert
        $this->tester->assertMessageWasEmittedOnChannel($paymentCreatedTransfer, 'payment-events');
    }

    /**
     * Covers the pre-order payment case where we don't have an orderReference.
     */
    public function testPaymentCreatedMessageIsSendAfterAPaymentWasInitializedForPreOrderPayment(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);

        $initializePaymentRequestTransfer = $this->tester->haveInitializePaymentRequestTransfer([
            InitializePaymentRequestTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
        ]);
        // Remove the orderReference to simulate a pre-order payment.
        $initializePaymentRequestTransfer->getOrderData()->setOrderReference(null);

        $initializePaymentResponseTransfer = new InitializePaymentResponseTransfer();
        $initializePaymentResponseTransfer
            ->setIsSuccessful(true)
            ->setTransactionId($transactionId);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'initializePayment' => function (InitializePaymentRequestTransfer $initializePaymentRequestTransfer) use ($initializePaymentResponseTransfer) {
                // Ensure that the AppConfig is always passed to the platform plugin.
                $this->assertInstanceOf(AppConfigTransfer::class, $initializePaymentRequestTransfer->getAppConfig());

                return $initializePaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        $this->tester->getFacade()->initializePayment($initializePaymentRequestTransfer);
        $paymentCreatedTransfer = $this->tester->havePaymentCreatedTransfer();

        // Assert
        $this->tester->assertMessageWasEmittedOnChannel($paymentCreatedTransfer, 'payment-events');
    }
}
