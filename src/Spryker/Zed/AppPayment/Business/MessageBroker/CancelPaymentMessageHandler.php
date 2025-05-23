<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\MessageBroker;

use Generated\Shared\Transfer\CancelPaymentRequestTransfer;
use Generated\Shared\Transfer\CancelPaymentResponseTransfer;
use Generated\Shared\Transfer\CancelPaymentTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AppPayment\Business\MessageBroker\TenantIdentifier\TenantIdentifierExtractor;
use Spryker\Zed\AppPayment\Business\Payment\Cancel\CancelPayment;
use Spryker\Zed\AppPayment\Business\Payment\Message\MessageSender;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;
use Spryker\Zed\AppPayment\Dependency\Facade\AppPaymentToAppKernelFacadeInterface;
use Spryker\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface;

class CancelPaymentMessageHandler extends AbstractPaymentMessageHandler implements CancelPaymentMessageHandlerInterface
{
    use LoggerTrait;

    public function __construct(
        protected AppPaymentRepositoryInterface $appPaymentRepository,
        protected TenantIdentifierExtractor $tenantIdentifierExtractor,
        protected AppPaymentToAppKernelFacadeInterface $appPaymentToAppKernelFacade,
        protected CancelPayment $cancelPayment,
        protected MessageSender $messageSender
    ) {
        parent::__construct($appPaymentRepository, $tenantIdentifierExtractor, $this->appPaymentToAppKernelFacade);
    }

    public function handleCancelPayment(
        CancelPaymentTransfer $cancelPaymentTransfer
    ): void {
        $paymentTransfer = $this->getPayment($cancelPaymentTransfer);

        if (!$paymentTransfer instanceof PaymentTransfer) {
            return;
        }

        $cancelPaymentRequestTransfer = (new CancelPaymentRequestTransfer())
            ->setTransactionId($paymentTransfer->getTransactionIdOrFail())
            ->setPayment($paymentTransfer);

        $cancelPaymentResponseTransfer = $this->cancelPayment->cancelPayment($cancelPaymentRequestTransfer);

        $this->determineAndSendMessage($paymentTransfer, $cancelPaymentResponseTransfer);
    }

    protected function determineAndSendMessage(
        PaymentTransfer $paymentTransfer,
        CancelPaymentResponseTransfer $cancelPaymentResponseTransfer
    ): void {
        $paymentStatus = $cancelPaymentResponseTransfer->getStatusOrFail();

        match ($paymentStatus) {
            PaymentStatus::STATUS_CANCELED => $this->messageSender->sendPaymentCanceledMessage($paymentTransfer),
            PaymentStatus::STATUS_CANCELLATION_FAILED => $this->messageSender->sendPaymentCancellationFailedMessage($paymentTransfer),
            default => 'Status is not handled',
        };
    }
}
