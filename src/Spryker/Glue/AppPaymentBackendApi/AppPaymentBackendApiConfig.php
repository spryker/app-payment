<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Glue\AppPaymentBackendApi;

use Spryker\Glue\Kernel\AbstractBundleConfig;

class AppPaymentBackendApiConfig extends AbstractBundleConfig
{
    /**
     * @var string
     */
    public const ERROR_CODE_PAYMENT_DISCONNECTION_CANNOT_BE_PROCEEDED = '21000';

    /**
     * @var string
     */
    public const ERROR_CODE_PAYMENT_DISCONNECTION_FORBIDDEN = '21001';
}
