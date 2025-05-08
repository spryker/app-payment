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
    public const PENDING = 'pending';

    /**
     * @var string
     */
    public const SUCCEEDED = 'succeeded';

    /**
     * @var string
     */
    public const FAILED = 'failed';

    /**
     * @var string
     */
    public const CANCELED = 'canceled';

    /**
     * @var string
     */
    public const PROCESSING = 'processing';
}
