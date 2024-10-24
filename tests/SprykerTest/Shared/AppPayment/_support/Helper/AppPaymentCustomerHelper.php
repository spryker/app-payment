<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Shared\AppPayment\Helper;

use Codeception\Module;
use Generated\Shared\DataBuilder\CustomerRequestBuilder;
use Generated\Shared\Transfer\CustomerRequestTransfer;
use SprykerTest\Shared\AppKernel\Helper\AppConfigHelperTrait;
use SprykerTest\Shared\Testify\Helper\DataCleanupHelperTrait;

class AppPaymentCustomerHelper extends Module
{
    use DataCleanupHelperTrait;
    use AppConfigHelperTrait;

    public function haveCustomerRequestTransfer(array $seed = []): CustomerRequestTransfer
    {
        $seed[CustomerRequestTransfer::CUSTOMER_PAYMENT_SERVICE_PROVIDER_DATA] = $seed[CustomerRequestTransfer::CUSTOMER_PAYMENT_SERVICE_PROVIDER_DATA] ?? ['foo' => 'bar'];

        $customerRequestBuilder = new CustomerRequestBuilder($seed);

        $customerRequestTransfer = $customerRequestBuilder->withCustomer()->build();

        return $customerRequestTransfer;
    }
}
