<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Refund;

use Generated\Shared\Transfer\PaymentRefundTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\QuoteItemTransfer;
use Generated\Shared\Transfer\RefundPaymentRequestTransfer;
use Generated\Shared\Transfer\RefundPaymentResponseTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AppPayment\AppPaymentConfig;
use Spryker\Zed\AppPayment\Business\Payment\AppConfig\AppConfigLoader;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;
use Spryker\Zed\AppPayment\Business\Payment\Writer\PaymentWriterInterface;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;
use Spryker\Zed\AppPayment\Persistence\AppPaymentEntityManagerInterface;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use Throwable;

class PaymentRefunder
{
    use TransactionTrait;
    use LoggerTrait;

    public function __construct(
        protected AppPaymentPlatformPluginInterface $appPaymentPlatformPlugin,
        protected PaymentRefundValidator $paymentRefundValidator,
        protected AppPaymentEntityManagerInterface $appPaymentEntityManager,
        protected PaymentWriterInterface $paymentWriter,
        protected AppPaymentConfig $appPaymentConfig,
        protected AppConfigLoader $appConfigLoader
    ) {
    }

    public function refundPayment(RefundPaymentRequestTransfer $refundPaymentRequestTransfer): RefundPaymentResponseTransfer
    {
        $refundPaymentResponseTransfer = $this->paymentRefundValidator->validatePaymentRefundRequest(
            $refundPaymentRequestTransfer,
        );

        if ($refundPaymentResponseTransfer instanceof RefundPaymentResponseTransfer) {
            return $refundPaymentResponseTransfer;
        }

        try {
            $refundPaymentRequestTransfer->setAppConfigOrFail(
                $this->appConfigLoader->loadAppConfig(
                    $refundPaymentRequestTransfer->getPaymentOrFail()->getTenantIdentifierOrFail(),
                ),
            );
            $refundPaymentResponseTransfer = $this->appPaymentPlatformPlugin->refundPayment($refundPaymentRequestTransfer);
        } catch (Throwable $throwable) {
            $this->getLogger()->error($throwable->getMessage(), [
                PaymentTransfer::TRANSACTION_ID => $refundPaymentRequestTransfer->getPaymentOrFail()->getTransactionIdOrFail(),
                PaymentTransfer::TENANT_IDENTIFIER => $refundPaymentRequestTransfer->getPaymentOrFail()->getTenantIdentifierOrFail(),
            ]);

            $refundPaymentResponseTransfer = (new RefundPaymentResponseTransfer())
                ->setIsSuccessful(false)
                ->setMessage($throwable->getMessage())
                ->setStatus(PaymentRefundStatus::FAILED);
        }

        /** @phpstan-var \Generated\Shared\Transfer\RefundPaymentResponseTransfer */
        return $this->getTransactionHandler()->handleTransaction(function () use ($refundPaymentRequestTransfer, $refundPaymentResponseTransfer) {
            $this->appPaymentEntityManager->createPaymentRefund(
                $this->mapRefundPaymentResponseTransferToPaymentRefundTransfer(
                    $refundPaymentRequestTransfer,
                    $refundPaymentResponseTransfer,
                ),
            );

            $paymentTransfer = $refundPaymentResponseTransfer->getPayment() ?? $refundPaymentRequestTransfer->getPaymentOrFail();

            $paymentStatus = $this->mapRefundStatusToPaymentStatus($refundPaymentResponseTransfer->getStatusOrFail());
            $this->savePayment($paymentTransfer, $paymentStatus);

            return $refundPaymentResponseTransfer;
        });
    }

    protected function mapRefundStatusToPaymentStatus(string $refundStatus): string
    {
        return match ($refundStatus) {
            PaymentRefundStatus::SUCCEEDED => PaymentStatus::STATUS_REFUNDED,
            PaymentRefundStatus::PARTIALLY => PaymentStatus::STATUS_PARTIALLY_REFUNDED,
            default => PaymentStatus::STATUS_REFUNDED,
        };
    }

    protected function savePayment(PaymentTransfer $paymentTransfer, string $status): void
    {
        $paymentTransfer->setStatus($status);
        $this->paymentWriter->updatePayment($paymentTransfer);
    }

    protected function mapRefundPaymentResponseTransferToPaymentRefundTransfer(
        RefundPaymentRequestTransfer $refundPaymentRequestTransfer,
        RefundPaymentResponseTransfer $refundPaymentResponseTransfer
    ): PaymentRefundTransfer {
        $orderItemIds = array_map(
            static fn (QuoteItemTransfer $quoteItemTransfer): string => $quoteItemTransfer->getIdSalesOrderItemOrFail(),
            iterator_to_array($refundPaymentRequestTransfer->getQuoteItems()),
        );

        return (new PaymentRefundTransfer())
            ->setTransactionId($refundPaymentRequestTransfer->getPaymentOrFail()->getTransactionIdOrFail())
            ->setAmount($refundPaymentRequestTransfer->getAmountOrFail())
            ->setCurrencyCode($refundPaymentRequestTransfer->getCurrencyCodeOrFail())
            ->setRefundId($refundPaymentResponseTransfer->getRefundId())
            ->setOrderItemIds($orderItemIds)
            ->setStatus($refundPaymentResponseTransfer->getStatusOrFail());
    }
}
