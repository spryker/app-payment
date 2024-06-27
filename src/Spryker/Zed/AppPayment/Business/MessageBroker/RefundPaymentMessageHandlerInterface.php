<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\MessageBroker;

use Generated\Shared\Transfer\RefundPaymentTransfer;

interface RefundPaymentMessageHandlerInterface
{
    public function handleRefundPayment(RefundPaymentTransfer $refundPaymentTransfer): void;
}