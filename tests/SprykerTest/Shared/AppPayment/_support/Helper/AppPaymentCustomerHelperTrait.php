<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Shared\AppPayment\Helper;

use Codeception\Module;

trait AppPaymentCustomerHelperTrait
{
    protected function getPaymentCustomerHelper(): AppPaymentCustomerHelper
    {
        /** @var \SprykerTest\Shared\AppPayment\Helper\AppPaymentCustomerHelper $paymentCustomerHelper */
        $paymentCustomerHelper = $this->getModule('\\' . AppPaymentCustomerHelper::class);

        return $paymentCustomerHelper;
    }

    abstract protected function getModule(string $name): Module;
}
