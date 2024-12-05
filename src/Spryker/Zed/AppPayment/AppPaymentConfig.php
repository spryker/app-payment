<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment;

use Spryker\Shared\Application\ApplicationConstants;
use Spryker\Shared\AppPayment\AppPaymentConstants;
use Spryker\Shared\GlueJsonApiConvention\GlueJsonApiConventionConstants;
use Spryker\Zed\Kernel\AbstractBundleConfig;

class AppPaymentConfig extends AbstractBundleConfig
{
    /**
     * @var string
     */
    public const CHECKOUT_STRATEGY_EXPRESS_CHECKOUT = 'express-checkout';

    /**
     * Using this prefix in transaction id will prevent sending payment created message.
     * This is for payment methods that will create a payment with a temporary transaction id which will be changed later in the process.
     *
     * @var string
     */
    public const IGNORE_PAYMENT_CREATED_MESSAGE_SENDING_TRANSACTION_ID_PREFIX = 'tmp-';

    /**
     * @api
     */
    public function getAppIdentifier(): string
    {
        return $this->getStringValue(AppPaymentConstants::APP_IDENTIFIER);
    }

    public function getZedBaseUrl(): string
    {
        return $this->getStringValue(ApplicationConstants::BASE_URL_ZED);
    }

    public function getGlueBaseUrl(): string
    {
        return $this->getStringValue(GlueJsonApiConventionConstants::GLUE_DOMAIN);
    }

    public function getIsTenantPaymentsDeletionAfterDisconnectionEnabled(): bool
    {
        /** @phpstan-var bool */
        return $this->get(AppPaymentConstants::IS_TENANT_PAYMENTS_DELETION_AFTER_DISCONNECTION_ENABLED, false);
    }

    protected function getStringValue(string $configKey): string
    {
        /** @phpstan-var string */
        return $this->get($configKey);
    }

    /**
     * @api
     *
     * @return array<string>
     */
    public function getHandleableWebhookTypes(): array
    {
        return [
            'payment',
            'refund',
        ];
    }
}
