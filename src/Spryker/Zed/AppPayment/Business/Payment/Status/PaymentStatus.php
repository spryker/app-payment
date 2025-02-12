<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Status;

enum PaymentStatus
{
    /**
     * @var string
     */
    public const STATUS_NEW = 'new';

    /**
     * @var string
     */
    public const STATUS_CANCELED = 'canceled';

    /**
     * @var string
     */
    public const STATUS_CANCELLATION_FAILED = 'cancellation_failed';

    /**
     * @var string
     */
    public const STATUS_CAPTURED = 'captured';

    /**
     * @var string
     */
    public const STATUS_CAPTURE_FAILED = 'capture_failed';

    /**
     * @var string
     */
    public const STATUS_CAPTURE_REQUESTED = 'capture_requested';

    /**
     * @var string
     */
    public const STATUS_AUTHORIZED = 'authorized';

    /**
     * @var string
     */
    public const STATUS_AUTHORIZATION_FAILED = 'authorization_failed';

    /**
     * @var string
     */
    public const STATUS_OVERPAID = 'overpaid';

    /**
     * @var string
     */
    public const STATUS_UNDERPAID = 'underpaid';

    /**
     * @var array<string, array<string>>
     */
    public const ALLOWED_TRANSITIONS = [
        PaymentStatus::STATUS_NEW => [
            PaymentStatus::STATUS_AUTHORIZED,
            PaymentStatus::STATUS_AUTHORIZATION_FAILED,
            PaymentStatus::STATUS_CANCELED,
            PaymentStatus::STATUS_CANCELLATION_FAILED,
            PaymentStatus::STATUS_CAPTURED,
            PaymentStatus::STATUS_CAPTURE_FAILED,
            PaymentStatus::STATUS_OVERPAID,
            PaymentStatus::STATUS_UNDERPAID,
        ],
        PaymentStatus::STATUS_AUTHORIZED => [
            PaymentStatus::STATUS_CAPTURED,
            PaymentStatus::STATUS_CAPTURE_FAILED,
            PaymentStatus::STATUS_CAPTURE_REQUESTED,
            PaymentStatus::STATUS_CANCELED,
            PaymentStatus::STATUS_CANCELLATION_FAILED,
            PaymentStatus::STATUS_OVERPAID,
            PaymentStatus::STATUS_UNDERPAID,
        ],
        PaymentStatus::STATUS_CAPTURE_REQUESTED => [
            PaymentStatus::STATUS_CAPTURED,
            PaymentStatus::STATUS_CAPTURE_FAILED,
            PaymentStatus::STATUS_CANCELED,
            PaymentStatus::STATUS_CANCELLATION_FAILED,
            PaymentStatus::STATUS_OVERPAID,
            PaymentStatus::STATUS_UNDERPAID,
        ],
        PaymentStatus::STATUS_CAPTURE_FAILED => [
            PaymentStatus::STATUS_CAPTURED,
            PaymentStatus::STATUS_CAPTURE_REQUESTED,
            PaymentStatus::STATUS_OVERPAID,
            PaymentStatus::STATUS_UNDERPAID,
        ],
        PaymentStatus::STATUS_CANCELLATION_FAILED => [
            PaymentStatus::STATUS_CANCELED,
        ],
    ];
}
