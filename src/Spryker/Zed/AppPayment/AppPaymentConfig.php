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
     * @api
     *
     * @var string
     */
    public const HEADER_TENANT_IDENTIFIER = 'x-tenant-identifier';

    public function getAppIdentifier(): string
    {
        return $this->getStringValue(AppPaymentConstants::APP_IDENTIFIER);
    }

    public function getPaymentProviderName(): string
    {
        // When extracting into a standalone package we should throw an exception here when this method is not overriden on project level
        return 'You need to configure the PaymentProviderName omn the project level';
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