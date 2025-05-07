<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Refund;

enum PaymentRefundStatus
{
    /**
     * @var string
     */
    public const PENDING = 'refund pending';

    /**
     * @var string
     */
    public const SUCCEEDED = 'refund succeeded';

    /**
     * @var string
     */
    public const FAILED = 'refund failed';

    /**
     * @var string
     */
    public const CANCELED = 'refund canceled';

    /**
     * @var string
     */
    public const PROCESSING = 'refund processing';

    /**
     * @var string
     */
    public const PARTIALLY = 'refunded partially';
}
