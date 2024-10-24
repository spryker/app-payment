<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Shared\AppPayment\Helper;

use Codeception\Module;

trait AppPaymentMethodHelperTrait
{
    protected function getPaymentMethodHelper(): AppPaymentMethodHelper
    {
        /** @var \SprykerTest\Shared\AppPayment\Helper\AppPaymentMethodHelper $paymentMethodHelper */
        $paymentMethodHelper = $this->getModule('\\' . AppPaymentMethodHelper::class);

        return $paymentMethodHelper;
    }

    abstract protected function getModule(string $name): Module;
}
