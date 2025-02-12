<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Shared\AppPayment\Helper;

use Codeception\Module;

trait AppPaymentRefundHelperTrait
{
    protected function getPaymentRefundHelper(): AppPaymentRefundHelper
    {
        /** @var \SprykerTest\Shared\AppPayment\Helper\AppPaymentRefundHelper $appPaymentRefundHelper */
        $appPaymentRefundHelper = $this->getModule('\\' . AppPaymentRefundHelper::class);

        return $appPaymentRefundHelper;
    }

    abstract protected function getModule(string $name): Module;
}
