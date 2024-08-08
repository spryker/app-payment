<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Dependency\Plugin;

use Generated\Shared\Transfer\PaymentMethodConfigurationRequestTransfer;
use Generated\Shared\Transfer\PaymentMethodConfigurationResponseTransfer;

interface AppPaymentPaymentMethodsPlatformPluginInterface extends AppPaymentPlatformPluginInterface
{
    /**
     * Specification:
     * - Receives a `PaymentMethodConfigurationRequestTransfer` with:
     *   - `PaymentMethodConfigurationRequestTransfer::appConfig`
     * - Returns a `PaymentMethodConfigurationRequestTransfer`.
     * - Each PaymentMethod which is listed in PaymentMethodConfigurationRequestTransfer::paymentMethodsToAdd will trigger a `AddPaymentMethod` message.
     * - Each PaymentMethod which is listed in PaymentMethodConfigurationRequestTransfer::paymentMethodsToDelete will trigger a `DeletePaymentMethod` message.
     *
     * @api
     */
    public function configurePaymentMethods(
        PaymentMethodConfigurationRequestTransfer $paymentMethodConfigurationRequestTransfer
    ): PaymentMethodConfigurationResponseTransfer;
}
