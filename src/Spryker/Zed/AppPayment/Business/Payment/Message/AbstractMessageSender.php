<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Message;

use Generated\Shared\Transfer\MessageAttributesTransfer;
use Spryker\Zed\AppPayment\AppPaymentConfig;
use Spryker\Zed\AppPayment\Dependency\Facade\AppPaymentToMessageBrokerFacadeInterface;

abstract class AbstractMessageSender
{
    public function __construct(
        protected AppPaymentConfig $appPaymentConfig,
        protected AppPaymentToMessageBrokerFacadeInterface $appPaymentToMessageBrokerFacade
    ) {
    }

    protected function getMessageAttributes(string $tenantIdentifier, string $transferName): MessageAttributesTransfer
    {
        $messageAttributesTransfer = new MessageAttributesTransfer();
        $messageAttributesTransfer
        ->setActorId($this->appPaymentConfig->getAppIdentifier())
        ->setEmitter($this->appPaymentConfig->getAppIdentifier())
        ->setTenantIdentifier($tenantIdentifier)
        ->setStoreReference($tenantIdentifier)
        ->setTransferName($transferName);

        return $messageAttributesTransfer;
    }
}
