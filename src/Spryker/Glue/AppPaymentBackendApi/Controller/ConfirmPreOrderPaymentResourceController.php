<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Glue\AppPaymentBackendApi\Controller;

use Generated\Shared\Transfer\GlueRequestTransfer;
use Generated\Shared\Transfer\GlueResponseTransfer;
use Spryker\Glue\Kernel\Backend\Controller\AbstractController;

/**
 * @method \Spryker\Glue\AppPaymentBackendApi\AppPaymentBackendApiFactory getFactory()
 */
class ConfirmPreOrderPaymentResourceController extends AbstractController
{
    public function postAction(GlueRequestTransfer $glueRequestTransfer): GlueResponseTransfer
    {
        $confirmPreOrderPaymentRequestTransfer = $this->getFactory()->createGlueRequestPaymentMapper()->mapGlueRequestTransferToConfirmPreOrderPaymentRequestTransfer($glueRequestTransfer);
        $confirmPreOrderPaymentResponseTransfer = $this->getFactory()->getAppPaymentFacade()->confirmPreOrderPayment($confirmPreOrderPaymentRequestTransfer);

        return $this->getFactory()->createGlueResponsePaymentMapper()->mapConfirmPreOrderPaymentResponseTransferToSingleResourceGlueResponseTransfer($confirmPreOrderPaymentResponseTransfer);
    }
}
