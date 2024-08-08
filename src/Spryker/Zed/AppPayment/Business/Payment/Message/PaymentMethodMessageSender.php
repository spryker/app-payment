<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Message;

use Generated\Shared\Transfer\AddPaymentMethodTransfer;
use Generated\Shared\Transfer\AppConfigTransfer;
use Generated\Shared\Transfer\DeletePaymentMethodTransfer;
use Generated\Shared\Transfer\UpdatePaymentMethodTransfer;
use Spryker\Zed\AppPayment\AppPaymentConfig;
use Spryker\Zed\AppPayment\Dependency\Facade\AppPaymentToMessageBrokerFacadeInterface;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;

class PaymentMethodMessageSender extends AbstractMessageSender
{
    public function __construct(
        protected AppPaymentConfig $appPaymentConfig,
        protected AppPaymentToMessageBrokerFacadeInterface $appPaymentToMessageBrokerFacade,
        protected AppPaymentPlatformPluginInterface $appPaymentPlatformPlugin
    ) {
        parent::__construct($appPaymentConfig, $appPaymentToMessageBrokerFacade);
    }

    public function sendAddPaymentMethodMessage(AddPaymentMethodTransfer $addPaymentMethodTransfer, AppConfigTransfer $appConfigTransfer): void
    {
        $addPaymentMethodTransfer->setMessageAttributes($this->getMessageAttributes(
            $appConfigTransfer->getTenantIdentifierOrFail(),
            $addPaymentMethodTransfer::class,
        ));

        $this->appPaymentToMessageBrokerFacade->sendMessage($addPaymentMethodTransfer);
    }

    public function sendUpdatePaymentMethodMessage(UpdatePaymentMethodTransfer $updatePaymentMethodTransfer, AppConfigTransfer $appConfigTransfer): void
    {
        $updatePaymentMethodTransfer->setMessageAttributes($this->getMessageAttributes(
            $appConfigTransfer->getTenantIdentifierOrFail(),
            $updatePaymentMethodTransfer::class,
        ));

        $this->appPaymentToMessageBrokerFacade->sendMessage($updatePaymentMethodTransfer);
    }

    public function sendDeletePaymentMethodMessage(DeletePaymentMethodTransfer $deletePaymentMethodTransfer, AppConfigTransfer $appConfigTransfer): void
    {
        $deletePaymentMethodTransfer->setMessageAttributes($this->getMessageAttributes(
            $appConfigTransfer->getTenantIdentifierOrFail(),
            $deletePaymentMethodTransfer::class,
        ));

        $this->appPaymentToMessageBrokerFacade->sendMessage($deletePaymentMethodTransfer);
    }
}
