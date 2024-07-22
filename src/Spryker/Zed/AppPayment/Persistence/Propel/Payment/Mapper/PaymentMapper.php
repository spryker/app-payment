<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Persistence\Propel\Payment\Mapper;

use Generated\Shared\Transfer\PaymentRefundTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\PaymentTransmissionTransfer;
use Orm\Zed\AppPayment\Persistence\SpyPayment;
use Orm\Zed\AppPayment\Persistence\SpyPaymentRefund;
use Orm\Zed\AppPayment\Persistence\SpyPaymentTransfer;
use Propel\Runtime\Collection\Collection;

class PaymentMapper
{
    public function mapPaymentTransferToPaymentEntity(PaymentTransfer $paymentTransfer, SpyPayment $spyPayment): SpyPayment
    {
        $quoteTransfer = $paymentTransfer->getQuoteOrFail();
        $quoteJson = json_encode($quoteTransfer->toArray());
        $details = json_encode($paymentTransfer->getDetails() ?? []);

        $paymentData = $paymentTransfer->modifiedToArray();
        $paymentData[PaymentTransfer::QUOTE] = $quoteJson;
        $paymentData[PaymentTransfer::DETAILS] = $details;

        return $spyPayment->fromArray($paymentData);
    }

    public function mapPaymentEntityToPaymentTransfer(SpyPayment $spyPayment, PaymentTransfer $paymentTransfer): PaymentTransfer
    {
        $quoteData = json_decode((string)$spyPayment->getQuote(), true);
        $details = json_decode((string)$spyPayment->getDetails(), true);

        $paymentData = $spyPayment->toArray();
        $paymentData[PaymentTransfer::QUOTE] = $quoteData;
        $paymentData[PaymentTransfer::DETAILS] = $details;

        return $paymentTransfer->fromArray($paymentData, true);
    }

    /**
     * @param \Propel\Runtime\Collection\Collection<\Orm\Zed\AppPayment\Persistence\SpyPaymentRefund> $paymentRefundEntityCollection
     *
     * @return list<\Generated\Shared\Transfer\PaymentRefundTransfer>
     */
    public function mapPaymentRefundEntityCollectionToPaymentRefundTransfers(Collection $paymentRefundEntityCollection): array
    {
        $paymentRefundTransfers = [];
        foreach ($paymentRefundEntityCollection as $paymentRefundEntity) {
            $paymentRefundTransfers[] = $this->mapPaymentRefundEntityToPaymentRefundTransfer($paymentRefundEntity, new PaymentRefundTransfer());
        }

        return $paymentRefundTransfers;
    }

    public function mapPaymentRefundEntityToPaymentRefundTransfer(
        SpyPaymentRefund $spyPaymentRefund,
        PaymentRefundTransfer $paymentRefundTransfer
    ): PaymentRefundTransfer {
        return $paymentRefundTransfer->fromArray($spyPaymentRefund->toArray(), true);
    }

    public function mapPaymentRefundTransferToPaymentRefundEntity(
        PaymentRefundTransfer $paymentRefundTransfer,
        SpyPaymentRefund $spyPaymentRefund
    ): SpyPaymentRefund {
        return $spyPaymentRefund->fromArray($paymentRefundTransfer->modifiedToArray());
    }

    public function mapPaymentTransmissionTransferToPaymentTransferEntity(
        PaymentTransmissionTransfer $paymentTransmissionTransfer,
        SpyPaymentTransfer $spyPaymentTransfer
    ): SpyPaymentTransfer {
        $paymentTransmissionData = $paymentTransmissionTransfer->modifiedToArray();
        $paymentTransmissionData['item_references'] = implode(',', $paymentTransmissionTransfer->getItemReferences());

        return $spyPaymentTransfer->fromArray($paymentTransmissionData);
    }

    public function mapPaymentTransmissionEntityToPaymentTransmissionTransfer(
        SpyPaymentTransfer $spyPaymentTransfer,
        PaymentTransmissionTransfer $paymentTransmissionTransfer
    ): PaymentTransmissionTransfer {
        $paymentTransmissionData = $spyPaymentTransfer->toArray();
        $paymentTransmissionData[PaymentTransmissionTransfer::ITEM_REFERENCES] = explode(',', (string)$spyPaymentTransfer->getItemReferences());

        return $paymentTransmissionTransfer->fromArray($paymentTransmissionData, true);
    }
}
