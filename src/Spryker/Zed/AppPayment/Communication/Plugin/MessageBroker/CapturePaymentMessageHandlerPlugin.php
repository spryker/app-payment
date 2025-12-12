<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Communication\Plugin\MessageBroker;

use Generated\Shared\Transfer\CapturePaymentTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\MessageBrokerExtension\Dependency\Plugin\MessageHandlerPluginInterface;

/**
 * {@inheritDoc}
 *
 * @api
 *
 * @deprecated Use {@link \Spryker\Zed\AppPayment\Communication\Plugin\MessageBroker\PaymentMessageHandlerPlugin} instead. This one will be removed with the next major
 *
 * @method \Spryker\Zed\AppPayment\Business\AppPaymentFacadeInterface getFacade()
 * @method \Spryker\Zed\AppPayment\AppPaymentConfig getConfig()
 */
class CapturePaymentMessageHandlerPlugin extends AbstractPlugin implements MessageHandlerPluginInterface
{
    public function onCapturePayment(CapturePaymentTransfer $capturePaymentTransfer): void
    {
        $this->getFacade()->handleCapturePayment($capturePaymentTransfer);
    }

    /**
     * {@inheritDoc}
     * Return an array where the key is the class name to be handled and the value is the callable that handles the message.
     *
     * @api
     *
     * @return \Generator<string, callable>
     */
    public function handles(): iterable
    {
        yield CapturePaymentTransfer::class => function (CapturePaymentTransfer $capturePaymentTransfer): void {
            $this->onCapturePayment($capturePaymentTransfer);
        };
    }
}
