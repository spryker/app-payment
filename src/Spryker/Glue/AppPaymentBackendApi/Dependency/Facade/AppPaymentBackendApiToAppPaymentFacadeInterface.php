<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Glue\AppPaymentBackendApi\Dependency\Facade;

use Generated\Shared\Transfer\ConfirmPreOrderPaymentRequestTransfer;
use Generated\Shared\Transfer\ConfirmPreOrderPaymentResponseTransfer;
use Generated\Shared\Transfer\InitializePaymentRequestTransfer;
use Generated\Shared\Transfer\InitializePaymentResponseTransfer;
use Generated\Shared\Transfer\PaymentTransmissionsRequestTransfer;
use Generated\Shared\Transfer\PaymentTransmissionsResponseTransfer;

interface AppPaymentBackendApiToAppPaymentFacadeInterface
{
    public function initializePayment(InitializePaymentRequestTransfer $initializePaymentRequestTransfer): InitializePaymentResponseTransfer;

    public function transferPayments(PaymentTransmissionsRequestTransfer $paymentTransmissionsRequestTransfer): PaymentTransmissionsResponseTransfer;

    public function confirmPreOrderPayment(
        ConfirmPreOrderPaymentRequestTransfer $confirmPreOrderPaymentRequestTransfer
    ): ConfirmPreOrderPaymentResponseTransfer;
}
