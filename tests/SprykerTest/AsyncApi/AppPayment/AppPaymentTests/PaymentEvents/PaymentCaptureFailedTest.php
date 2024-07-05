<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\AsyncApi\AppPayment\PaymentTests\PaymentEvents;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\WebhookRequestTransfer;
use Generated\Shared\Transfer\WebhookResponseTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;
use Spryker\Zed\AppPayment\Business\Payment\Webhook\WebhookDataType;
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
 * @group PaymentCaptureFailedTest
 * Add your own group annotations below this line
 */
class PaymentCaptureFailedTest extends Unit
{
    use DependencyHelperTrait;

    protected AppPaymentAsyncApiTester $tester;

    /**
     * The PaymentCaptureFailed message is sent when the payment should be confirmed but the PaymentPluginInterface implementation returns a failed response.
     *
     * This action can only be made when the payment is in state PaymentStatusEnum::STATUS_AUTHORIZED
     */
    public function testPaymentCaptureFailedMessageIsSendWhenPlatformPluginReturnsCaptureFailed(): void
    {
        // Arrange
        $paymentCaptureFailedTransfer = $this->tester->havePaymentCaptureFailedTransfer();

        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_AUTHORIZED);

        $webhookRequestTransfer = (new WebhookRequestTransfer())
            ->setMode('test')
            ->setType(WebhookDataType::PAYMENT)
            ->setTransactionId($transactionId);

        $this->tester->mockPlatformPlugin(PaymentStatus::STATUS_CAPTURE_FAILED);

        // Act
        $this->tester->getFacade()->handleWebhook($webhookRequestTransfer, new WebhookResponseTransfer());

        // Assert
        $this->tester->assertMessageWasEmittedOnChannel($paymentCaptureFailedTransfer, 'payment-events');
    }
}
