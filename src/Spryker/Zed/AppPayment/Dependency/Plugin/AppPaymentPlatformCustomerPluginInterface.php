<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Dependency\Plugin;

use Generated\Shared\Transfer\CustomerRequestTransfer;
use Generated\Shared\Transfer\CustomerResponseTransfer;

interface AppPaymentPlatformCustomerPluginInterface extends AppPaymentPlatformPluginInterface
{
    /**
     * Specification:
     * - Receives a `CustomerRequestTransfer` with:
     *   - `appConfig`
     *   - `customer`
     *   - `customerPaymentServiceProviderData`
     * - Returns a `CustomerResponseTransfer`.
     * - Requires `CustomerResponseTransfer::isSuccessful`to be set.
     * - Requires `CustomerResponseTransfer::message` to be set when the 3rd party provider could not process the request or any other issue occurs.
     *
     * @api
     */
    public function customer(
        CustomerRequestTransfer $customerRequestTransfer
    ): CustomerResponseTransfer;
}
