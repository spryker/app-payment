<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Dependency\Plugin;

use Generated\Shared\Transfer\ConfirmPreOrderPaymentRequestTransfer;
use Generated\Shared\Transfer\ConfirmPreOrderPaymentResponseTransfer;

interface AppPreOrderPaymentPlatformPluginInterface extends AppPaymentPlatformPluginInterface
{
    /**
     * Specification:
     * - Receives a `ConfirmPreOrderPaymentRequestTransfer` with:
     *   - `ConfirmPreOrderPaymentRequestTransfer::appConfig`
     *   - `ConfirmPreOrderPaymentRequestTransfer::payment`
     *   - `ConfirmPreOrderPaymentRequestTransfer::orderReference`
     *   - `ConfirmPreOrderPaymentRequestTransfer::paymentServiceProviderData`
     * - Returns a `ConfirmPreOrderPaymentResponseTransfer`.
     * - Requires `ConfirmPreOrderPaymentResponseTransfer::isSuccessful`to be set.
     * - Requires `ConfirmPreOrderPaymentResponseTransfer::message` to be set when the 3rd party provider could not process the request or any other issue occurs.
     *
     * @api
     */
    public function confirmPreOrderPayment(
        ConfirmPreOrderPaymentRequestTransfer $confirmPreOrderPaymentRequestTransfer
    ): ConfirmPreOrderPaymentResponseTransfer;
}
