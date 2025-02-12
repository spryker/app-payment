<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace AppPaymentTests\PaymentEvents;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\PaymentCapturedTransfer;
use Generated\Shared\Transfer\PaymentUpdatedTransfer;
use Generated\Shared\Transfer\WebhookRequestTransfer;
use Generated\Shared\Transfer\WebhookResponseTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;
use Spryker\Zed\AppPayment\Business\Payment\Webhook\WebhookDataType;
use SprykerTest\AsyncApi\AppPayment\AppPaymentAsyncApiTester;

/**
 * Auto-generated group annotations
 *
 * @group AppPaymentTests
 * @group PaymentEvents
 * @group PaymentOverpaidTest
 * Add your own group annotations below this line
 */
class PaymentOverpaidTest extends Unit
{
    protected AppPaymentAsyncApiTester $tester;

    public function testGivenAPaymentIsOverpaidWhenTheWebhookIsHandledThenAPaymentOverpaidAndAPaymentCapturedMessageIsSent(): void
    {
        // Arrange
        $paymentOverpaidTransfer = $this->tester->havePaymentOverpaidTransfer();

        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_AUTHORIZED);

        $webhookRequestTransfer = (new WebhookRequestTransfer())
            ->setMode('test')
            ->setType(WebhookDataType::PAYMENT)
            ->setTransactionId($transactionId);

        $this->tester->mockPlatformPlugin(PaymentStatus::STATUS_OVERPAID);

        // Act
        $this->tester->getFacade()->handleWebhook($webhookRequestTransfer, new WebhookResponseTransfer());

        // Assert
        $this->tester->assertMessageWasEmittedOnChannel(new PaymentCapturedTransfer(), 'payment-events');
        $this->tester->assertMessageWasEmittedOnChannel($paymentOverpaidTransfer, 'payment-events');
    }

    public function testPaymentUpdatedMessageIsSentWithSourceAndTargetStatus(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier, PaymentStatus::STATUS_AUTHORIZED);

        $webhookRequestTransfer = (new WebhookRequestTransfer())
            ->setMode('test')
            ->setType(WebhookDataType::PAYMENT)
            ->setTransactionId($transactionId);

        $this->tester->mockPlatformPlugin(PaymentStatus::STATUS_OVERPAID);

        // Act
        $this->tester->getFacade()->handleWebhook($webhookRequestTransfer, new WebhookResponseTransfer());

        // Assert
        $paymentUpdatedTransfer = $this->tester->havePaymentUpdatedTransfer();

        $this->tester->assertMessageWasEmittedOnChannel($paymentUpdatedTransfer, 'payment-events', function (PaymentUpdatedTransfer $usedPaymentUpdatedTransfer, PaymentUpdatedTransfer $sentPaymentUpdatedTransfer): void {
            $detailsArray = json_decode($sentPaymentUpdatedTransfer->getDetails(), true);

            $this->assertSame($detailsArray['sourceStatus'], PaymentStatus::STATUS_AUTHORIZED);
            $this->assertSame($detailsArray['targetStatus'], PaymentStatus::STATUS_OVERPAID);
        });
    }
}
