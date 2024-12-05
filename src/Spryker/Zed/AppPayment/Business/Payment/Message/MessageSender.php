<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Message;

use ArrayObject;
use Generated\Shared\Transfer\InitializePaymentRequestTransfer;
use Generated\Shared\Transfer\InitializePaymentResponseTransfer;
use Generated\Shared\Transfer\MessageContextTransfer;
use Generated\Shared\Transfer\PaymentAuthorizationFailedTransfer;
use Generated\Shared\Transfer\PaymentAuthorizedTransfer;
use Generated\Shared\Transfer\PaymentCanceledTransfer;
use Generated\Shared\Transfer\PaymentCancellationFailedTransfer;
use Generated\Shared\Transfer\PaymentCapturedTransfer;
use Generated\Shared\Transfer\PaymentCaptureFailedTransfer;
use Generated\Shared\Transfer\PaymentCreatedTransfer;
use Generated\Shared\Transfer\PaymentRefundedTransfer;
use Generated\Shared\Transfer\PaymentRefundFailedTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\PaymentUpdatedTransfer;
use Generated\Shared\Transfer\QuoteItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Shared\Kernel\Transfer\TransferInterface;
use Spryker\Zed\AppPayment\AppPaymentConfig;

class MessageSender extends AbstractMessageSender
{
    public function sendPaymentCapturedMessage(PaymentTransfer $paymentTransfer, ?MessageContextTransfer $messageContextTransfer = null): void
    {
        $paymentCapturedTransfer = $this->mapPaymentTransferToPaymentMessageTransfer($paymentTransfer, new PaymentCapturedTransfer(), $messageContextTransfer);

        $paymentCapturedTransfer->setMessageAttributes($this->getMessageAttributes(
            $paymentTransfer->getTenantIdentifierOrFail(),
            $paymentCapturedTransfer::class,
        ));

        $this->appPaymentToMessageBrokerFacade->sendMessage($paymentCapturedTransfer);
    }

    public function sendPaymentCaptureFailedMessage(PaymentTransfer $paymentTransfer, ?MessageContextTransfer $messageContextTransfer = null): void
    {
        $paymentCaptureFailedTransfer = $this->mapPaymentTransferToPaymentMessageTransfer($paymentTransfer, new PaymentCaptureFailedTransfer(), $messageContextTransfer);

        $paymentCaptureFailedTransfer->setMessageAttributes($this->getMessageAttributes(
            $paymentTransfer->getTenantIdentifierOrFail(),
            $paymentCaptureFailedTransfer::class,
        ));

        $this->appPaymentToMessageBrokerFacade->sendMessage($paymentCaptureFailedTransfer);
    }

    public function sendPaymentAuthorizedMessage(PaymentTransfer $paymentTransfer): void
    {
        $paymentAuthorizedTransfer = $this->mapPaymentTransferToPaymentMessageTransfer($paymentTransfer, new PaymentAuthorizedTransfer());

        $paymentAuthorizedTransfer->setMessageAttributes($this->getMessageAttributes(
            $paymentTransfer->getTenantIdentifierOrFail(),
            $paymentAuthorizedTransfer::class,
        ));

        $this->appPaymentToMessageBrokerFacade->sendMessage($paymentAuthorizedTransfer);
    }

    public function sendPaymentAuthorizationFailedMessage(PaymentTransfer $paymentTransfer): void
    {
        $paymentAuthorizationFailedTransfer = $this->mapPaymentTransferToPaymentMessageTransfer($paymentTransfer, new PaymentAuthorizationFailedTransfer());

        $paymentAuthorizationFailedTransfer->setMessageAttributes($this->getMessageAttributes(
            $paymentTransfer->getTenantIdentifierOrFail(),
            $paymentAuthorizationFailedTransfer::class,
        ));

        $this->appPaymentToMessageBrokerFacade->sendMessage($paymentAuthorizationFailedTransfer);
    }

    public function sendPaymentCanceledMessage(PaymentTransfer $paymentTransfer, ?MessageContextTransfer $messageContextTransfer = null): void
    {
        $paymentCanceledTransfer = $this->mapPaymentTransferToPaymentMessageTransfer($paymentTransfer, new PaymentCanceledTransfer(), $messageContextTransfer);

        $paymentCanceledTransfer->setMessageAttributes($this->getMessageAttributes(
            $paymentTransfer->getTenantIdentifierOrFail(),
            $paymentCanceledTransfer::class,
        ));

        $this->appPaymentToMessageBrokerFacade->sendMessage($paymentCanceledTransfer);
    }

    public function sendPaymentCancellationFailedMessage(PaymentTransfer $paymentTransfer, ?MessageContextTransfer $messageContextTransfer = null): void
    {
        $paymentCancellationFailedTransfer = $this->mapPaymentTransferToPaymentMessageTransfer($paymentTransfer, new PaymentCancellationFailedTransfer(), $messageContextTransfer);

        $paymentCancellationFailedTransfer->setMessageAttributes($this->getMessageAttributes(
            $paymentTransfer->getTenantIdentifierOrFail(),
            $paymentCancellationFailedTransfer::class,
        ));

        $this->appPaymentToMessageBrokerFacade->sendMessage($paymentCancellationFailedTransfer);
    }

    public function sendPaymentCreatedMessage(
        InitializePaymentRequestTransfer $initializePaymentRequestTransfer,
        InitializePaymentResponseTransfer $initializePaymentResponseTransfer
    ): void {
        $quoteTransfer = $initializePaymentRequestTransfer->getOrderDataOrFail();

        // It may be that payment gets created without having either of the following. (PreOrderPayment)
        // In this case, we do send a PaymentUpdated message later. E.g. when ConfirmPreOrderPayment is made
        if (!$quoteTransfer->getOrderReference() && ($initializePaymentResponseTransfer->getTransactionId() === null || $initializePaymentResponseTransfer->getTransactionId() === '' || $initializePaymentResponseTransfer->getTransactionId() === '0')) {
            return;
        }

        $transactionId = $initializePaymentResponseTransfer->getTransactionIdOrFail();

        if (str_starts_with($transactionId, AppPaymentConfig::IGNORE_PAYMENT_CREATED_MESSAGE_SENDING_TRANSACTION_ID_PREFIX)) {
            return;
        }

        $paymentCreatedTransfer = new PaymentCreatedTransfer();
        $paymentCreatedTransfer->fromArray($initializePaymentResponseTransfer->toArray(), true);
        $paymentCreatedTransfer
            ->setEntityReference($quoteTransfer->getOrderReference())
            ->setPaymentReference($initializePaymentResponseTransfer->getTransactionId());

        $paymentCreatedTransfer->setMessageAttributes($this->getMessageAttributes(
            $initializePaymentRequestTransfer->getTenantIdentifierOrFail(),
            $paymentCreatedTransfer::class,
        ));

        $this->appPaymentToMessageBrokerFacade->sendMessage($paymentCreatedTransfer);
    }

    public function sendPaymentUpdatedMessage(PaymentTransfer $paymentTransfer): void
    {
        $paymentUpdatedTransfer = new PaymentUpdatedTransfer();
        $paymentUpdatedTransfer
            ->setEntityReference($paymentTransfer->getOrderReference())
            ->setPaymentReference($paymentTransfer->getTransactionIdOrFail())
            ->setDetails($paymentTransfer->getDetailsOrFail());

        $paymentUpdatedTransfer->setMessageAttributes($this->getMessageAttributes(
            $paymentTransfer->getTenantIdentifierOrFail(),
            $paymentUpdatedTransfer::class,
        ));

        $this->appPaymentToMessageBrokerFacade->sendMessage($paymentUpdatedTransfer);
    }

    public function sendPaymentRefundedMessage(
        PaymentTransfer $paymentTransfer,
        ?MessageContextTransfer $messageContextTransfer = null
    ): void {
        $paymentRefundedTransfer = $this->mapPaymentTransferToPaymentMessageTransfer(
            $paymentTransfer,
            new PaymentRefundedTransfer(),
            $messageContextTransfer,
        );

        $paymentRefundedTransfer->setMessageAttributes($this->getMessageAttributes(
            $paymentTransfer->getTenantIdentifierOrFail(),
            $paymentRefundedTransfer::class,
        ));

        $this->appPaymentToMessageBrokerFacade->sendMessage($paymentRefundedTransfer);
    }

    public function sendPaymentRefundFailedMessage(
        PaymentTransfer $paymentTransfer,
        ?MessageContextTransfer $messageContextTransfer = null
    ): void {
        $paymentRefundFailedTransfer = $this->mapPaymentTransferToPaymentMessageTransfer(
            $paymentTransfer,
            new PaymentRefundFailedTransfer(),
            $messageContextTransfer,
        );

        $paymentRefundFailedTransfer->setMessageAttributes($this->getMessageAttributes(
            $paymentTransfer->getTenantIdentifierOrFail(),
            $paymentRefundFailedTransfer::class,
        ));

        $this->appPaymentToMessageBrokerFacade->sendMessage($paymentRefundFailedTransfer);
    }

    /**
     * @template T of \Spryker\Shared\Kernel\Transfer\TransferInterface
     *
     * @param T $transfer
     *
     * @return T
     */
    protected function mapPaymentTransferToPaymentMessageTransfer(
        PaymentTransfer $paymentTransfer,
        TransferInterface $transfer,
        ?MessageContextTransfer $messageContextTransfer = null
    ): TransferInterface {
        $quoteTransfer = $paymentTransfer->getQuoteOrFail();

        $paymentData = [];
        $paymentData['orderReference'] = $paymentTransfer->getOrderReferenceOrFail();
        $paymentData['currencyIsoCode'] = $quoteTransfer->getCurrencyCodeOrFail();
        $paymentData['amount'] = $this->getAmount($quoteTransfer, $messageContextTransfer);
        $paymentData['orderItemIds'] = $this->getOrderItemIds($quoteTransfer->getItems(), $messageContextTransfer);

        return $transfer->fromArray($paymentData, true);
    }

    protected function getAmount(QuoteTransfer $quoteTransfer, ?MessageContextTransfer $messageContextTransfer): string
    {
        if ($messageContextTransfer instanceof MessageContextTransfer && $messageContextTransfer->getAmount()) {
            return $messageContextTransfer->getAmount();
        }

        return $quoteTransfer->getGrandTotalOrFail();
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\QuoteItemTransfer> $arrayObject
     *
     * @return array<int, mixed>
     */
    protected function getOrderItemIds(ArrayObject $arrayObject, ?MessageContextTransfer $messageContextTransfer): array
    {
        if ($messageContextTransfer instanceof MessageContextTransfer && $messageContextTransfer->getOrderItemsIds()) {
            return $messageContextTransfer->getOrderItemsIds();
        }

        return array_map(static function (QuoteItemTransfer $quoteItemTransfer): ?string {
            return $quoteItemTransfer->getIdSalesOrderItem();
        }, $arrayObject->getArrayCopy());
    }
}
