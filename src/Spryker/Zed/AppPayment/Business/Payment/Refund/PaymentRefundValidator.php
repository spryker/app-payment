<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Refund;

use Generated\Shared\Transfer\QuoteItemTransfer;
use Generated\Shared\Transfer\RefundPaymentRequestTransfer;
use Generated\Shared\Transfer\RefundPaymentResponseTransfer;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatusTransitionValidator;
use Spryker\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface;

class PaymentRefundValidator
{
    public function __construct(
        protected PaymentStatusTransitionValidator $paymentStatusTransitionValidator,
        protected AppPaymentRepositoryInterface $appPaymentRepository
    ) {
    }

    public function validatePaymentRefundRequest(
        RefundPaymentRequestTransfer $refundPaymentRequestTransfer
    ): ?RefundPaymentResponseTransfer {
        $paymentStatus = $refundPaymentRequestTransfer->getPaymentOrFail()->getStatusOrFail();

        // only captured and succeeded payments can be refunded, everything else is cancellation process
        if ($paymentStatus !== PaymentStatus::STATUS_CAPTURED && $paymentStatus !== PaymentStatus::STATUS_PARTIALLY_REFUNDED) {
            return (new RefundPaymentResponseTransfer())
                ->setIsSuccessful(false)
                ->setMessage(sprintf(
                    'Payment is in status "%s" and cannot be refunded. Only payments that are in status "%s" can be refunded',
                    $paymentStatus,
                    implode(', ', [
                        PaymentStatus::STATUS_CAPTURED,
                        PaymentStatus::STATUS_PARTIALLY_REFUNDED,
                    ]),
                ))
                ->setStatus(PaymentRefundStatus::FAILED);
        }

        // foolproof check for skipping refund if there are order items that have been already refunded or are being in progress
        $orderItemIds = array_map(
            static fn (QuoteItemTransfer $quoteItemTransfer): string => $quoteItemTransfer->getIdSalesOrderItemOrFail(),
            iterator_to_array($refundPaymentRequestTransfer->getQuoteItems()),
        );

        $orderItemIdsBlockedForRefund = $this->getOrderItemIdsBlockedForRefund(
            $refundPaymentRequestTransfer,
            $orderItemIds,
        );

        if ($orderItemIdsBlockedForRefund !== []) {
            return (new RefundPaymentResponseTransfer())
                ->setIsSuccessful(false)
                ->setMessage(sprintf(
                    'Refund for order items with IDs [%s] cannot be started because they are already refunded or in progress.',
                    implode(', ', $orderItemIdsBlockedForRefund),
                ))
                ->setOrderItemIds(array_diff($orderItemIds, $orderItemIdsBlockedForRefund))
                ->setStatus(PaymentRefundStatus::FAILED);
        }

        return null;
    }

    /**
     * @param array<string> $orderItemIds
     *
     * @return array<string>
     */
    protected function getOrderItemIdsBlockedForRefund(
        RefundPaymentRequestTransfer $refundPaymentRequestTransfer,
        array $orderItemIds
    ): array {
        $refundedOrderItemIds = [];

        $transactionId = $refundPaymentRequestTransfer->getPaymentOrFail()->getTransactionIdOrFail();

        // Check if refunds for current order items exist and have positive scenario (pending, processing or succeeded statuses)
        $paymentRefundTransfers = $this->appPaymentRepository->getRefundsByTransactionIdAndOrderItemIdAndStatuses(
            $transactionId,
            $orderItemIds,
            [
                PaymentRefundStatus::PENDING,
                PaymentRefundStatus::PROCESSING,
                PaymentRefundStatus::SUCCEEDED,
            ],
        );

        foreach ($paymentRefundTransfers as $paymentRefundTransfer) {
            $refundedOrderItemIds = array_unique(array_merge(
                $refundedOrderItemIds,
                array_intersect($paymentRefundTransfer->getOrderItemIds(), $orderItemIds),
            ));
        }

        return $refundedOrderItemIds;
    }
}
