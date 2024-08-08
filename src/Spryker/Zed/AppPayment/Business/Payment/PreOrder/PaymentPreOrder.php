<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\PreOrder;

use Generated\Shared\Transfer\ConfirmPreOrderPaymentRequestTransfer;
use Generated\Shared\Transfer\ConfirmPreOrderPaymentResponseTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AppPayment\AppPaymentConfig;
use Spryker\Zed\AppPayment\Business\Payment\AppConfig\AppConfigLoader;
use Spryker\Zed\AppPayment\Business\Payment\Message\MessageSender;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPreOrderPaymentPlatformPluginInterface;
use Spryker\Zed\AppPayment\Persistence\AppPaymentEntityManagerInterface;
use Spryker\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use Throwable;

class PaymentPreOrder
{
    use TransactionTrait;
    use LoggerTrait;

    public function __construct(
        protected AppPaymentPlatformPluginInterface $appPaymentPlatformPlugin,
        protected AppPaymentRepositoryInterface $appPaymentRepository,
        protected AppPaymentEntityManagerInterface $appPaymentEntityManager,
        protected MessageSender $messageSender,
        protected AppPaymentConfig $appPaymentConfig,
        protected AppConfigLoader $appConfigLoader
    ) {
    }

    public function confirmPreOrderPayment(
        ConfirmPreOrderPaymentRequestTransfer $confirmPreOrderPaymentRequestTransfer
    ): ConfirmPreOrderPaymentResponseTransfer {
        try {
            $confirmPreOrderPaymentRequestTransfer->setAppConfigOrFail($this->appConfigLoader->loadAppConfig($confirmPreOrderPaymentRequestTransfer->getTenantIdentifierOrFail()));
            $confirmPreOrderPaymentRequestTransfer->setPayment($this->appPaymentRepository->getPaymentByTransactionId($confirmPreOrderPaymentRequestTransfer->getTransactionIdOrFail()));

            // When the payment platform plugin does not implement the `AppPreOrderPaymentPlatformPluginInterface` we assume there is no further action from the PSP implementation
            // needed, and we can simply update the Payment with the missing orderReference.
            $confirmPreOrderPaymentResponseTransfer = new ConfirmPreOrderPaymentResponseTransfer();
            $confirmPreOrderPaymentResponseTransfer
                ->setIsSuccessful(true)
                ->setStatus(PaymentStatus::STATUS_CAPTURED);

            if ($this->appPaymentPlatformPlugin instanceof AppPreOrderPaymentPlatformPluginInterface) {
                $confirmPreOrderPaymentResponseTransfer = $this->appPaymentPlatformPlugin->confirmPreOrderPayment($confirmPreOrderPaymentRequestTransfer);
            }
        } catch (Throwable $throwable) {
            $this->getLogger()->error($throwable->getMessage(), [
                PaymentTransfer::TENANT_IDENTIFIER => $confirmPreOrderPaymentRequestTransfer->getTenantIdentifierOrFail(),
            ]);
            $confirmPreOrderPaymentResponseTransfer = new ConfirmPreOrderPaymentResponseTransfer();
            $confirmPreOrderPaymentResponseTransfer
                ->setIsSuccessful(false)
                ->setMessage($throwable->getMessage());

            return $confirmPreOrderPaymentResponseTransfer;
        }

        /** @phpstan-var \Generated\Shared\Transfer\ConfirmPreOrderPaymentResponseTransfer */
        return $this->getTransactionHandler()->handleTransaction(function () use ($confirmPreOrderPaymentRequestTransfer, $confirmPreOrderPaymentResponseTransfer) {
            $this->savePayment($confirmPreOrderPaymentRequestTransfer, $confirmPreOrderPaymentResponseTransfer);
            $this->sendMessages($confirmPreOrderPaymentRequestTransfer, $confirmPreOrderPaymentResponseTransfer);

            return $confirmPreOrderPaymentResponseTransfer;
        });
    }

    protected function savePayment(
        ConfirmPreOrderPaymentRequestTransfer $confirmPreOrderPaymentRequestTransfer,
        ConfirmPreOrderPaymentResponseTransfer $confirmPreOrderPaymentResponseTransfer
    ): void {
        $paymentTransfer = $confirmPreOrderPaymentRequestTransfer->getPaymentOrFail();
        $paymentTransfer
            ->setOrderReference($confirmPreOrderPaymentRequestTransfer->getOrderReference())
            ->setStatus($confirmPreOrderPaymentResponseTransfer->getStatus());

        $this->appPaymentEntityManager->savePayment($paymentTransfer);
    }

    protected function sendMessages(
        ConfirmPreOrderPaymentRequestTransfer $confirmPreOrderPaymentRequestTransfer,
        ConfirmPreOrderPaymentResponseTransfer $confirmPreOrderPaymentResponseTransfer
    ): void {
        $paymentTransfer = $confirmPreOrderPaymentRequestTransfer->getPaymentOrFail();

        $this->messageSender->sendPaymentUpdatedMessage($paymentTransfer);

        // TODO send message based on the status of the returned response of the PSP Implementation
        if ($confirmPreOrderPaymentResponseTransfer->getIsSuccessful() === true) {
            $this->messageSender->sendPaymentCapturedMessage($paymentTransfer);

            return;
        }

        $this->messageSender->sendPaymentCaptureFailedMessage($paymentTransfer);
    }
}
