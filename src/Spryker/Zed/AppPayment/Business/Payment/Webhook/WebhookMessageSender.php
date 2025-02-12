<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Webhook;

use Generated\Shared\Transfer\MessageContextTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\WebhookRequestTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AppPayment\Business\Payment\Message\MessageSender;
use Spryker\Zed\AppPayment\Business\Payment\Refund\PaymentRefundStatus;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;

class WebhookMessageSender
{
    use LoggerTrait;

    public function __construct(protected MessageSender $messageSender)
    {
    }

    public function determineAndSendMessage(WebhookRequestTransfer $webhookRequestTransfer): void
    {
        match ($webhookRequestTransfer->getType()) {
            WebhookDataType::PAYMENT => $this->sendMessageForPayment($webhookRequestTransfer),
            WebhookDataType::REFUND => $this->sendMessageForPaymentRefund($webhookRequestTransfer),
            default => $this->getLogger()->warning(
                sprintf('Unhandled webhook type "%s".', $webhookRequestTransfer->getType()),
                [
                    PaymentTransfer::TRANSACTION_ID => $webhookRequestTransfer->getPaymentOrFail()->getTransactionIdOrFail(),
                    PaymentTransfer::TENANT_IDENTIFIER => $webhookRequestTransfer->getPaymentOrFail()->getTenantIdentifierOrFail(),
                ],
            ),
        };
    }

    protected function sendMessageForPayment(WebhookRequestTransfer $webhookRequestTransfer): void
    {
        $paymentTransfer = $webhookRequestTransfer->getPaymentOrFail();
        $paymentStatus = $paymentTransfer->getStatusOrFail();

        match ($paymentStatus) {
            PaymentStatus::STATUS_CAPTURED => $this->messageSender->sendPaymentCapturedMessage($paymentTransfer),
            PaymentStatus::STATUS_CAPTURE_FAILED => $this->messageSender->sendPaymentCaptureFailedMessage($paymentTransfer),
            PaymentStatus::STATUS_AUTHORIZED => $this->messageSender->sendPaymentAuthorizedMessage($paymentTransfer),
            PaymentStatus::STATUS_AUTHORIZATION_FAILED => $this->messageSender->sendPaymentAuthorizationFailedMessage($paymentTransfer),
            PaymentStatus::STATUS_CANCELED => $this->messageSender->sendPaymentCanceledMessage($paymentTransfer),
            PaymentStatus::STATUS_CANCELLATION_FAILED => $this->messageSender->sendPaymentCancellationFailedMessage($paymentTransfer),
            PaymentStatus::STATUS_OVERPAID => $this->handleOverpaidPayment($paymentTransfer),
            PaymentStatus::STATUS_UNDERPAID => $this->handleUnderpaidPayment($paymentTransfer),
            default => $this->getLogger()->warning(sprintf('Unhandled payment status "%s" for orderReference "%s" and tenantIdentifier "%s".', $paymentStatus, $paymentTransfer->getOrderReferenceOrFail(), $paymentTransfer->getTenantIdentifierOrFail()), [
                PaymentTransfer::TRANSACTION_ID => $paymentTransfer->getTransactionIdOrFail(),
                PaymentTransfer::TENANT_IDENTIFIER => $paymentTransfer->getTenantIdentifierOrFail(),
            ])
        };
    }

    protected function handleOverpaidPayment(PaymentTransfer $paymentTransfer): void
    {
        $this->messageSender->sendPaymentCapturedMessage($paymentTransfer);
        $this->messageSender->sendPaymentOverpaidMessage($paymentTransfer);
    }

    protected function handleUnderpaidPayment(PaymentTransfer $paymentTransfer): void
    {
        $this->messageSender->sendPaymentCaptureFailedMessage($paymentTransfer);
        $this->messageSender->sendPaymentUnderpaidMessage($paymentTransfer);
    }

    protected function sendMessageForPaymentRefund(WebhookRequestTransfer $webhookRequestTransfer): void
    {
        $paymentTransfer = $webhookRequestTransfer->getPaymentOrFail();
        $paymentRefundTransfer = $webhookRequestTransfer->getRefundOrFail();
        $paymentRefundStatus = $paymentRefundTransfer->getStatusOrFail();

        $messageContextTransfer = (new MessageContextTransfer())
            ->setAmount((string)$paymentRefundTransfer->getAmountOrFail())
            ->setOrderItemsIds($paymentRefundTransfer->getOrderItemIds());

        match ($paymentRefundStatus) {
            // no message for pending and processing statuses, they are initial phase of refund and OMS should still be in "refund pending" state
            PaymentRefundStatus::PENDING, PaymentRefundStatus::PROCESSING => null,
            PaymentRefundStatus::SUCCEEDED => $this->messageSender->sendPaymentRefundedMessage($paymentTransfer, $messageContextTransfer),
            PaymentRefundStatus::FAILED, PaymentRefundStatus::CANCELED => $this->messageSender->sendPaymentRefundFailedMessage($paymentTransfer, $messageContextTransfer),
            default => $this->getLogger()->warning(sprintf(
                'Unhandled payment refund status "%s" for orderReference "%s" and tenantIdentifier "%s".',
                $paymentRefundStatus,
                $paymentTransfer->getOrderReferenceOrFail(),
                $paymentTransfer->getTenantIdentifierOrFail(),
            ), [
                PaymentTransfer::TRANSACTION_ID => $paymentTransfer->getTransactionIdOrFail(),
                PaymentTransfer::TENANT_IDENTIFIER => $paymentTransfer->getTenantIdentifierOrFail(),
            ])
        };
    }
}
