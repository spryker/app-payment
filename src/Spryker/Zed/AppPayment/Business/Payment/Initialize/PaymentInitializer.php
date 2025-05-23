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
use Spryker\Zed\AppPayment\Business\Payment\Method\Normalizer\PaymentMethodNormalizer;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;
use Spryker\Zed\AppPayment\Business\Payment\Writer\PaymentWriterInterface;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;
use Spryker\Zed\AppPayment\Persistence\AppPaymentEntityManagerInterface;
use Spryker\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use Throwable;

class PaymentInitializer
{
    use TransactionTrait;
    use LoggerTrait;

    public function __construct(
        protected AppPaymentPlatformPluginInterface $appPaymentPlatformPlugin,
        protected AppPaymentEntityManagerInterface $appPaymentEntityManager,
        protected AppPaymentRepositoryInterface $appPaymentRepository,
        protected PaymentWriterInterface $paymentWriter,
        protected PaymentMethodNormalizer $paymentMethodNormalizer,
        protected MessageSender $messageSender,
        protected AppPaymentConfig $appPaymentConfig,
        protected AppConfigLoader $appConfigLoader
    ) {
    }

    public function initializePayment(InitializePaymentRequestTransfer $initializePaymentRequestTransfer): InitializePaymentResponseTransfer
    {
        try {
            $initializePaymentRequestTransfer->setAppConfigOrFail($this->appConfigLoader->loadAppConfig($initializePaymentRequestTransfer->getTenantIdentifierOrFail()));
            $initializePaymentRequestTransfer = $this->normalizePaymentMethod($initializePaymentRequestTransfer);

            // In case of a pre-order payment, the payment provider data is already set, and we have to load the previously made payment and pass it to the platform implementation.
            if ($initializePaymentRequestTransfer->getPreOrderPaymentData() !== [] && isset($initializePaymentRequestTransfer->getPreOrderPaymentData()[PaymentTransfer::TRANSACTION_ID])) {
                $initializePaymentRequestTransfer->setPayment(
                    $this->appPaymentRepository->getPaymentByTransactionId($initializePaymentRequestTransfer->getPreOrderPaymentData()[PaymentTransfer::TRANSACTION_ID]),
                );
            }

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

        // Only add the redirect information when we are not doing a pre-order payment
        if ($initializePaymentRequestTransfer->getOrderDataOrFail()->getOrderReference()) {
            $initializePaymentResponseTransfer = $this->addRedirectUrl($initializePaymentRequestTransfer, $initializePaymentResponseTransfer);
        }

        /** @phpstan-var \Generated\Shared\Transfer\InitializePaymentResponseTransfer */
        return $this->getTransactionHandler()->handleTransaction(function () use ($initializePaymentRequestTransfer, $initializePaymentResponseTransfer): InitializePaymentResponseTransfer {
            // When we have already persisted a payment in the database and this method is called a second time the payment will be set in the request transfer
            // In this case we are in the pre-order payment process, and we don't want to save the payment again.
            // When the grandTotal has changed the response will contain a different transactionId as we have persisted in the database because so we need to update
            if ($initializePaymentRequestTransfer->getPayment() instanceof PaymentTransfer) {
                // Nothing has changed so we can step out here
                if ($initializePaymentRequestTransfer->getPayment()->getTransactionId() === $initializePaymentResponseTransfer->getTransactionId()) {
                    return $initializePaymentResponseTransfer;
                }

                $this->appPaymentEntityManager->updatePaymentTransactionId(
                    $initializePaymentRequestTransfer->getPaymentOrFail(),
                    $initializePaymentResponseTransfer->getTransactionIdOrFail(),
                );

                return $initializePaymentResponseTransfer;
            }

            $this->createPayment($initializePaymentRequestTransfer, $initializePaymentResponseTransfer);

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

    protected function createPayment(
        InitializePaymentRequestTransfer $initializePaymentRequestTransfer,
        InitializePaymentResponseTransfer $initializePaymentResponseTransfer
    ): void {
        $quoteTransfer = $initializePaymentRequestTransfer->getOrderDataOrFail();

        $paymentTransfer = new PaymentTransfer();
        $paymentTransfer
            ->fromArray($initializePaymentResponseTransfer->toArray(), true)
            ->setTransactionId($initializePaymentResponseTransfer->getTransactionId())
            ->setTenantIdentifier($initializePaymentRequestTransfer->getTenantIdentifierOrFail())
            ->setOrderReference($quoteTransfer->getOrderReference()) // Optional for the pre-order payment case.
            ->setQuote($quoteTransfer)
            ->setRedirectSuccessUrl($initializePaymentRequestTransfer->getRedirectSuccessUrl())
            ->setRedirectCancelUrl($initializePaymentRequestTransfer->getRedirectCancelUrl())
            ->setStatus(PaymentStatus::STATUS_NEW);

        $this->paymentWriter->createPayment($paymentTransfer);
    }

    protected function normalizePaymentMethod(InitializePaymentRequestTransfer $initializePaymentRequestTransfer): InitializePaymentRequestTransfer
    {
        $quoteTransfer = $initializePaymentRequestTransfer->getOrderDataOrFail();
        $quoteTransfer->setPaymentMethod($this->paymentMethodNormalizer->normalizePaymentMethodKey($quoteTransfer->getPaymentMethodOrFail()));

        return $initializePaymentRequestTransfer->setOrderData($quoteTransfer);
    }
}
