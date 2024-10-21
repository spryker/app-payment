<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Customer;

use Generated\Shared\Transfer\CustomerRequestTransfer;
use Generated\Shared\Transfer\CustomerResponseTransfer;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Zed\AppPayment\Business\Message\MessageBuilder;
use Spryker\Zed\AppPayment\Business\Payment\AppConfig\AppConfigLoader;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformCustomerPluginInterface;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;
use Throwable;

class Customer
{
    use LoggerTrait;

    public function __construct(protected AppPaymentPlatformPluginInterface $appPaymentPlatformPlugin, protected AppConfigLoader $appConfigLoader)
    {
    }

    public function customer(CustomerRequestTransfer $customerRequestTransfer): CustomerResponseTransfer
    {
        if (!$this->appPaymentPlatformPlugin instanceof AppPaymentPlatformCustomerPluginInterface) {
            $this->getLogger()->error(MessageBuilder::getPlatformPluginDoesNotProvideCustomerFeatures(), [
                CustomerRequestTransfer::TENANT_IDENTIFIER => $customerRequestTransfer->getTenantIdentifier(),
            ]);

            $customerResponseTransfer = new CustomerResponseTransfer();
            $customerResponseTransfer
                ->setIsSuccessful(false)
                ->setMessage(MessageBuilder::getPlatformPluginDoesNotProvideCustomerFeatures());

            return $customerResponseTransfer;
        }

        try {
            $customerRequestTransfer->setAppConfigOrFail($this->appConfigLoader->loadAppConfig($customerRequestTransfer->getTenantIdentifierOrFail()));
            $customerResponseTransfer = $this->appPaymentPlatformPlugin->customer($customerRequestTransfer);
        } catch (Throwable $throwable) {
            $this->getLogger()->error($throwable->getMessage(), [
                CustomerRequestTransfer::TENANT_IDENTIFIER => $customerRequestTransfer->getTenantIdentifierOrFail(),
            ]);
            $customerResponseTransfer = new CustomerResponseTransfer();
            $customerResponseTransfer
                ->setIsSuccessful(false)
                ->setMessage($throwable->getMessage());

            return $customerResponseTransfer;
        }

        return $customerResponseTransfer;
    }
}
