<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Glue\AppPaymentBackendApi\Mapper\Payment;

use Generated\Shared\Transfer\ConfirmPreOrderPaymentRequestTransfer;
use Generated\Shared\Transfer\GlueRequestTransfer;
use Generated\Shared\Transfer\InitializePaymentRequestTransfer;
use Generated\Shared\Transfer\PaymentTransmissionsRequestTransfer;

interface GlueRequestPaymentMapperInterface
{
    public function mapGlueRequestTransferToInitializePaymentRequestTransfer(
        GlueRequestTransfer $glueRequestTransfer
    ): InitializePaymentRequestTransfer;

    public function mapGlueRequestTransferToPaymentTransmissionsRequestTransfer(
        GlueRequestTransfer $glueRequestTransfer
    ): PaymentTransmissionsRequestTransfer;

    public function mapGlueRequestTransferToConfirmPreOrderPaymentRequestTransfer(
        GlueRequestTransfer $glueRequestTransfer
    ): ConfirmPreOrderPaymentRequestTransfer;
}
