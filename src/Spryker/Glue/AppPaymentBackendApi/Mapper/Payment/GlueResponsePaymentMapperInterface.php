<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Glue\AppPaymentBackendApi\Mapper\Payment;

use Generated\Shared\Transfer\CancelPreOrderPaymentResponseTransfer;
use Generated\Shared\Transfer\ConfirmPreOrderPaymentResponseTransfer;
use Generated\Shared\Transfer\CustomerResponseTransfer;
use Generated\Shared\Transfer\GlueResponseTransfer;
use Generated\Shared\Transfer\InitializePaymentResponseTransfer;
use Generated\Shared\Transfer\PaymentTransmissionsResponseTransfer;

interface GlueResponsePaymentMapperInterface
{
    public function mapInitializePaymentResponseTransferToSingleResourceGlueResponseTransfer(
        InitializePaymentResponseTransfer $initializePaymentResponseTransfer
    ): GlueResponseTransfer;

    public function mapPaymentTransmissionsResponseTransferToSingleResourceGlueResponseTransfer(
        PaymentTransmissionsResponseTransfer $paymentTransmissionsResponseTransfer
    ): GlueResponseTransfer;

    public function mapConfirmPreOrderPaymentResponseTransferToSingleResourceGlueResponseTransfer(
        ConfirmPreOrderPaymentResponseTransfer $confirmPreOrderPaymentResponseTransfer
    ): GlueResponseTransfer;

    public function mapCancelPreOrderPaymentResponseTransferToSingleResourceGlueResponseTransfer(
        CancelPreOrderPaymentResponseTransfer $cancelPreOrderPaymentResponseTransfer
    ): GlueResponseTransfer;

    public function mapCustomerResponseTransferToSingleResourceGlueResponseTransfer(
        CustomerResponseTransfer $customerResponseTransfer
    ): GlueResponseTransfer;
}
