<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\AsyncApi\AppPayment\PaymentTests\PaymentEvents;

use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\WebhookRequestTransfer;
use Generated\Shared\Transfer\WebhookResponseTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;
use Spryker\Zed\AppPayment\Business\Payment\Webhook\WebhookDataType;
use Spryker\Zed\AppPayment\Dependency\Plugin\PlatformPluginInterface;
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
 * @group PaymentAuthorizedTest
 * Add your own group annotations below this line
 */
class PaymentAuthorizedTest extends Unit
{
    use DependencyHelperTrait;

    protected AppPaymentAsyncApiTester $tester;

    /**
     * The PaymentAuthorized message is sent when the Webhook handler receives a successful response from the PaymentPlatformPlugin and the status of the Payment is changed to PaymentStatusEnum::STATUS_AUTHORIZED.
     */
    public function testPaymentAuthorizedMessageIsSend(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier);

        $paymentAuthorizedTransfer = $this->tester->havePaymentAuthorizedTransfer();

        $webHookRequestTransfer = (new WebhookRequestTransfer())
            ->setMode('test')
            ->setType(WebhookDataType::PAYMENT)
            ->setTransactionId($transactionId);

        $platformPluginMock = Stub::makeEmpty(PlatformPluginInterface::class, [
            'handleWebhook' => function (WebhookRequestTransfer $webhookRequestTransfer) use ($transactionId): WebhookResponseTransfer {
                // Ensure that required data is passed to the PaymentPlatformPlugin
                $this->assertNotNull($webhookRequestTransfer->getPayment());
                $this->assertNotNull($webhookRequestTransfer->getAppConfig());
                $this->assertSame($webhookRequestTransfer->getTransactionId(), $transactionId);

                $webhookResponseTransfer = new WebhookResponseTransfer();
                $webhookResponseTransfer->setIsSuccessful(true);
                $webhookResponseTransfer->setPaymentStatus(PaymentStatus::STATUS_AUTHORIZED);

                return $webhookResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->getFacade()->handleWebhook($webHookRequestTransfer, new WebhookResponseTransfer());

        // Assert
        $this->tester->assertMessageWasEmittedOnChannel($paymentAuthorizedTransfer, 'payment-events');
    }
}