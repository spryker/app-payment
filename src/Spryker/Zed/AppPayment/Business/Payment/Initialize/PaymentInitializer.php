<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Initialize;

use Generated\Shared\Transfer\InitializePaymentRequestTransfer;
use Generated\Shared\Transfer\InitializePaymentResponseTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AppPayment\AppPaymentConfig;
use Spryker\Zed\AppPayment\Business\Payment\AppConfig\AppConfigLoader;
use Spryker\Zed\AppPayment\Business\Payment\Message\MessageSender;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;
use Spryker\Zed\AppPayment\Persistence\AppPaymentEntityManagerInterface;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use Throwable;

class PaymentInitializer
{
    use TransactionTrait;
    use LoggerTrait;

    public function __construct(
        protected AppPaymentPlatformPluginInterface $appPaymentPlatformPlugin,
        protected AppPaymentEntityManagerInterface $appPaymentEntityManager,
        protected MessageSender $messageSender,
        protected AppPaymentConfig $appPaymentConfig,
        protected AppConfigLoader $appConfigLoader
    ) {
    }

    public function initializePayment(InitializePaymentRequestTransfer $initializePaymentRequestTransfer): InitializePaymentResponseTransfer
    {
        try {
            $initializePaymentRequestTransfer->setAppConfigOrFail($this->appConfigLoader->loadAppConfig($initializePaymentRequestTransfer->getTenantIdentifierOrFail()));
            $initializePaymentResponseTransfer = $this->appPaymentPlatformPlugin->initializePayment($initializePaymentRequestTransfer);
        } catch (Throwable $throwable) {
            $this->getLogger()->error($throwable->getMessage(), [
                PaymentTransfer::TENANT_IDENTIFIER => $initializePaymentRequestTransfer->getTenantIdentifierOrFail(),
            ]);
            $initializePaymentResponseTransfer = new InitializePaymentResponseTransfer();
            $initializePaymentResponseTransfer
                ->setIsSuccessful(false)
                ->setMessage($throwable->getMessage());

            return $initializePaymentResponseTransfer;
        }

        if ($initializePaymentResponseTransfer->getIsSuccessful() !== true) {
            return $initializePaymentResponseTransfer;
        }

        if ($initializePaymentRequestTransfer->getOrderData()->getOrderReference()) {
            $initializePaymentResponseTransfer = $this->addRedirectUrl($initializePaymentRequestTransfer, $initializePaymentResponseTransfer);
        }

        /** @phpstan-var \Generated\Shared\Transfer\InitializePaymentResponseTransfer */
        return $this->getTransactionHandler()->handleTransaction(function () use ($initializePaymentRequestTransfer, $initializePaymentResponseTransfer) {
            $this->savePayment($initializePaymentRequestTransfer, $initializePaymentResponseTransfer);
            $this->messageSender->sendPaymentCreatedMessage($initializePaymentRequestTransfer, $initializePaymentResponseTransfer);

            return $initializePaymentResponseTransfer;
        });
    }

    /**
     * Add the URL the end-user gets redirected to fill out the platform payment page.
     * This URL will be the same for all Payment Platforms.
     */
    protected function addRedirectUrl(
        InitializePaymentRequestTransfer $initializePaymentRequestTransfer,
        InitializePaymentResponseTransfer $initializePaymentResponseTransfer
    ): InitializePaymentResponseTransfer {
        $initializePaymentResponseTransfer->setRedirectUrl(
            sprintf(
                '%s/app-payment?%s=%s&%s=%s',
                $this->appPaymentConfig->getZedBaseUrl(),
                PaymentTransfer::TRANSACTION_ID,
                $initializePaymentResponseTransfer->getTransactionId(),
                PaymentTransfer::TENANT_IDENTIFIER,
                $initializePaymentRequestTransfer->getTenantIdentifier(),
            ),
        );

        return $initializePaymentResponseTransfer;
    }

    protected function savePayment(
        InitializePaymentRequestTransfer $initializePaymentRequestTransfer,
        InitializePaymentResponseTransfer $initializePaymentResponseTransfer
    ): void {
        $quoteTransfer = $initializePaymentRequestTransfer->getOrderDataOrFail();

        $paymentTransfer = new PaymentTransfer();
        $paymentTransfer
            ->fromArray($initializePaymentResponseTransfer->toArray(), true)
            ->setTransactionId($initializePaymentResponseTransfer->getTransactionIdOrFail())
            ->setTenantIdentifier($initializePaymentRequestTransfer->getTenantIdentifierOrFail())
            ->setOrderReference($quoteTransfer->getOrderReference()) // Optional for the pre-order payment case.
            ->setQuote($quoteTransfer)
            ->setRedirectSuccessUrl($initializePaymentRequestTransfer->getRedirectSuccessUrl())
            ->setRedirectCancelUrl($initializePaymentRequestTransfer->getRedirectCancelUrl())
            ->setStatus(PaymentStatus::STATUS_NEW);

        $this->appPaymentEntityManager->createPayment($paymentTransfer);
    }
}
