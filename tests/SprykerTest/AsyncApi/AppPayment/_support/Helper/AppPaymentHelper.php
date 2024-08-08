<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\AsyncApi\AppPayment\Helper;

use Codeception\Module;
use Generated\Shared\DataBuilder\AddPaymentMethodBuilder;
use Generated\Shared\DataBuilder\CancelPaymentBuilder;
use Generated\Shared\DataBuilder\CapturePaymentBuilder;
use Generated\Shared\DataBuilder\DeletePaymentMethodBuilder;
use Generated\Shared\DataBuilder\OrderItemBuilder;
use Generated\Shared\DataBuilder\PaymentAuthorizationFailedBuilder;
use Generated\Shared\DataBuilder\PaymentAuthorizedBuilder;
use Generated\Shared\DataBuilder\PaymentCanceledBuilder;
use Generated\Shared\DataBuilder\PaymentCancellationFailedBuilder;
use Generated\Shared\DataBuilder\PaymentCapturedBuilder;
use Generated\Shared\DataBuilder\PaymentCaptureFailedBuilder;
use Generated\Shared\DataBuilder\PaymentCreatedBuilder;
use Generated\Shared\DataBuilder\PaymentRefundedBuilder;
use Generated\Shared\DataBuilder\PaymentRefundFailedBuilder;
use Generated\Shared\DataBuilder\PaymentUpdatedBuilder;
use Generated\Shared\DataBuilder\RefundPaymentBuilder;
use Generated\Shared\DataBuilder\UpdatePaymentMethodBuilder;
use Generated\Shared\Transfer\AddPaymentMethodTransfer;
use Generated\Shared\Transfer\CancelPaymentTransfer;
use Generated\Shared\Transfer\CapturePaymentTransfer;
use Generated\Shared\Transfer\DeletePaymentMethodTransfer;
use Generated\Shared\Transfer\OrderItemTransfer;
use Generated\Shared\Transfer\PaymentAuthorizationFailedTransfer;
use Generated\Shared\Transfer\PaymentAuthorizedTransfer;
use Generated\Shared\Transfer\PaymentCanceledTransfer;
use Generated\Shared\Transfer\PaymentCancellationFailedTransfer;
use Generated\Shared\Transfer\PaymentCapturedTransfer;
use Generated\Shared\Transfer\PaymentCaptureFailedTransfer;
use Generated\Shared\Transfer\PaymentCreatedTransfer;
use Generated\Shared\Transfer\PaymentRefundedTransfer;
use Generated\Shared\Transfer\PaymentRefundFailedTransfer;
use Generated\Shared\Transfer\PaymentUpdatedTransfer;
use Generated\Shared\Transfer\RefundPaymentTransfer;
use Generated\Shared\Transfer\UpdatePaymentMethodTransfer;

class AppPaymentHelper extends Module
{
    public function haveCancelPaymentTransfer(array $seed = []): CancelPaymentTransfer
    {
        return (new CancelPaymentBuilder($seed))->withMessageAttributes()->build();
    }

    public function haveCapturePaymentTransfer(array $seed = []): CapturePaymentTransfer
    {
        return (new CapturePaymentBuilder($seed))->withMessageAttributes()->build();
    }

    public function haveRefundPaymentTransfer(array $seed = [], array $orderItemIds = []): RefundPaymentTransfer
    {
        $refundPaymentBuilder = (new RefundPaymentBuilder($seed))->withMessageAttributes();

        if (!$orderItemIds) {
            return $refundPaymentBuilder->withOrderItem()->build();
        }

        foreach ($orderItemIds as $orderItemId) {
            $refundPaymentBuilder->withOrderItem(
                new OrderItemBuilder([OrderItemTransfer::ORDER_ITEM_ID => (int)$orderItemId]),
            );
        }

        return $refundPaymentBuilder->build();
    }

    public function havePaymentAuthorizedTransfer(array $seed = []): PaymentAuthorizedTransfer
    {
        return (new PaymentAuthorizedBuilder($seed))->build();
    }

    public function havePaymentAuthorizationFailedTransfer(array $seed = []): PaymentAuthorizationFailedTransfer
    {
        return (new PaymentAuthorizationFailedBuilder($seed))->build();
    }

    public function havePaymentCapturedTransfer(array $seed = []): PaymentCapturedTransfer
    {
        return (new PaymentCapturedBuilder($seed))->build();
    }

    public function havePaymentCaptureFailedTransfer(array $seed = []): PaymentCaptureFailedTransfer
    {
        return (new PaymentCaptureFailedBuilder($seed))->build();
    }

    public function havePaymentRefundedTransfer(array $seed = []): PaymentRefundedTransfer
    {
        return (new PaymentRefundedBuilder($seed))->build();
    }

    public function havePaymentRefundFailedTransfer(array $seed = []): PaymentRefundFailedTransfer
    {
        return (new PaymentRefundFailedBuilder($seed))->build();
    }

    public function havePaymentCanceledTransfer(array $seed = []): PaymentCanceledTransfer
    {
        return (new PaymentCanceledBuilder($seed))->build();
    }

    public function havePaymentCancellationFailedTransfer(array $seed = []): PaymentCancellationFailedTransfer
    {
        return (new PaymentCancellationFailedBuilder($seed))->build();
    }

    public function haveAddPaymentMethodTransfer(array $seed = []): AddPaymentMethodTransfer
    {
        return (new AddPaymentMethodBuilder($seed))->build();
    }

    public function haveUpdatePaymentMethodTransfer(array $seed = []): UpdatePaymentMethodTransfer
    {
        return (new UpdatePaymentMethodBuilder($seed))->build();
    }

    public function haveDeletePaymentMethodTransfer(array $seed = []): DeletePaymentMethodTransfer
    {
        return (new DeletePaymentMethodBuilder($seed))->build();
    }

    public function havePaymentCreatedTransfer(array $seed = []): PaymentCreatedTransfer
    {
        return (new PaymentCreatedBuilder($seed))->build();
    }

    public function havePaymentUpdatedTransfer(array $seed = []): PaymentUpdatedTransfer
    {
        return (new PaymentUpdatedBuilder($seed))->build();
    }
}
