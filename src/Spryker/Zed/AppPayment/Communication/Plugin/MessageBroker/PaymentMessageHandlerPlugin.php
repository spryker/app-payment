<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Communication\Plugin\MessageBroker;

use Generated\Shared\Transfer\CancelPaymentTransfer;
use Generated\Shared\Transfer\CapturePaymentTransfer;
use Generated\Shared\Transfer\RefundPaymentTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\MessageBrokerExtension\Dependency\Plugin\MessageHandlerPluginInterface;

/**
 * {@inheritDoc}
 *
 * @api
 *
 * @method \Spryker\Zed\AppPayment\Business\AppPaymentFacadeInterface getFacade()
 * @method \Spryker\Zed\AppPayment\AppPaymentConfig getConfig()
 */
class PaymentMessageHandlerPlugin extends AbstractPlugin implements MessageHandlerPluginInterface
{
    public function onCapturePayment(CapturePaymentTransfer $capturePaymentTransfer): void
    {
        $this->getFacade()->handleCapturePayment($capturePaymentTransfer);
    }

    public function onCancelPayment(CancelPaymentTransfer $cancelPaymentTransfer): void
    {
        $this->getFacade()->handleCancelPayment($cancelPaymentTransfer);
    }

    public function onRefundPayment(RefundPaymentTransfer $refundPaymentTransfer): void
    {
        $this->getFacade()->handleRefundPayment($refundPaymentTransfer);
    }

    /**
     * {@inheritDoc}
     * Return an array where the key is the class name to be handled and the value is the callable that handles the message.
     *
     * @api
     *
     * @return iterable<string, callable>
     */
    public function handles(): iterable
    {
        yield CapturePaymentTransfer::class => function (CapturePaymentTransfer $capturePaymentTransfer): void {
            $this->onCapturePayment($capturePaymentTransfer);
        };

        yield CancelPaymentTransfer::class => function (CancelPaymentTransfer $cancelPaymentTransfer): void {
            $this->onCancelPayment($cancelPaymentTransfer);
        };

        yield RefundPaymentTransfer::class => function (RefundPaymentTransfer $refundPaymentTransfer): void {
            $this->onRefundPayment($refundPaymentTransfer);
        };
    }
}
