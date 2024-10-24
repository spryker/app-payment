<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Method;

use Generated\Shared\Transfer\AddPaymentMethodTransfer;
use Generated\Shared\Transfer\AppConfigTransfer;
use Generated\Shared\Transfer\DeletePaymentMethodTransfer;
use Generated\Shared\Transfer\EndpointTransfer;
use Generated\Shared\Transfer\PaymentMethodAppConfigurationTransfer;
use Generated\Shared\Transfer\PaymentMethodConfigurationRequestTransfer;
use Generated\Shared\Transfer\PaymentMethodTransfer;
use Generated\Shared\Transfer\UpdatePaymentMethodTransfer;
use Spryker\Zed\AppKernel\AppKernelConfig;
use Spryker\Zed\AppPayment\AppPaymentConfig;
use Spryker\Zed\AppPayment\Business\Payment\Message\PaymentMethodMessageSender;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPaymentMethodsPluginInterface;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;
use Spryker\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface;

class PaymentMethod
{
    public function __construct(
        protected AppPaymentPlatformPluginInterface $appPaymentPlatformPlugin,
        protected AppPaymentConfig $appPaymentConfig,
        protected PaymentMethodMessageSender $paymentMethodMessageSender,
        protected AppPaymentRepositoryInterface $appPaymentRepository
    ) {
    }

    public function configurePaymentMethods(AppConfigTransfer $appConfigTransfer): AppConfigTransfer
    {
        // Do not send the message(s) when App is in state "disconnected" or when the app is marked as inactive.
        if ($appConfigTransfer->getStatus() === AppKernelConfig::APP_STATUS_DISCONNECTED || $appConfigTransfer->getIsActive() === false || !($this->appPaymentPlatformPlugin instanceof AppPaymentPlatformPaymentMethodsPluginInterface)) {
            return $appConfigTransfer;
        }

        $tenantIdentifier = $appConfigTransfer->getTenantIdentifierOrFail();

        $paymentMethodConfigurationRequestTransfer = new PaymentMethodConfigurationRequestTransfer();
        $paymentMethodConfigurationRequestTransfer->setAppConfig($appConfigTransfer);

        $paymentMethodCollectionResponseTransfer = $this->appPaymentPlatformPlugin->configurePaymentMethods($paymentMethodConfigurationRequestTransfer);

        // Get current persisted Tenants payment methods
        $persistedPaymentMethodTransfers = $this->appPaymentRepository->getTenantPaymentMethods($tenantIdentifier);

        // Get current configured payment methods
        /** @var array<\Generated\Shared\Transfer\PaymentMethodTransfer> $configuredPaymentMethodTransfers */
        $configuredPaymentMethodTransfers = $paymentMethodCollectionResponseTransfer->getPaymentMethods()->getArrayCopy();

        $paymentMethodTransfersToAdd = $this->getPaymentMethodsToAdd($persistedPaymentMethodTransfers, $configuredPaymentMethodTransfers);
        $paymentMethodTransfersToUpdate = $this->getPaymentMethodsToUpdate($persistedPaymentMethodTransfers, $configuredPaymentMethodTransfers);
        $paymentMethodTransfersToDelete = $this->getPaymentMethodsToDelete($persistedPaymentMethodTransfers, $configuredPaymentMethodTransfers);

        foreach ($paymentMethodTransfersToAdd as $paymentMethodTransferToAdd) {
            $this->addPaymentMethod($paymentMethodTransferToAdd, $appConfigTransfer);
        }

        foreach ($paymentMethodTransfersToUpdate as $paymentMethodTransferToUpdate) {
            $this->updatePaymentMethod($paymentMethodTransferToUpdate, $appConfigTransfer);
        }

        foreach ($paymentMethodTransfersToDelete as $paymentMethodTransferToDelete) {
            $this->deletePaymentMethod($paymentMethodTransferToDelete, $appConfigTransfer);
        }

        return $appConfigTransfer;
    }

    protected function addPaymentMethod(PaymentMethodTransfer $paymentMethodTransfer, AppConfigTransfer $appConfigTransfer): void
    {
        $paymentMethodAppConfigurationTransfer = $this->getDefaultPaymentMethodAppConfiguration();

        // In case of the PSP App configured a custom checkout configuration, we will use it here and pass it as such to the configuration.
        if ($paymentMethodTransfer->getPaymentMethodAppConfiguration() instanceof PaymentMethodAppConfigurationTransfer) {
            $paymentMethodAppConfigurationTransfer->setCheckoutConfiguration($paymentMethodTransfer->getPaymentMethodAppConfiguration()->getCheckoutConfiguration());
        }

        $addPaymentMethodTransfer = new AddPaymentMethodTransfer();
        $addPaymentMethodTransfer
            ->setName($paymentMethodTransfer->getNameOrFail())
            ->setProviderName($paymentMethodTransfer->getProviderNameOrFail())
            // @deprecated This line can be removed when all PSP Apps are updated.
            ->setPaymentAuthorizationEndpoint(sprintf('%s/private/initialize-payment', $this->appPaymentConfig->getGlueBaseUrl()))
            ->setPaymentMethodAppConfiguration($paymentMethodAppConfigurationTransfer);

        $paymentMethodTransfer->setPaymentMethodAppConfiguration($paymentMethodAppConfigurationTransfer);

        $this->appPaymentRepository->savePaymentMethod($paymentMethodTransfer, $appConfigTransfer->getTenantIdentifierOrFail());

        $this->paymentMethodMessageSender->sendAddPaymentMethodMessage($addPaymentMethodTransfer, $appConfigTransfer);
    }

    protected function updatePaymentMethod(PaymentMethodTransfer $paymentMethodTransfer, AppConfigTransfer $appConfigTransfer): void
    {
        // Add the passed configuration to the default configuration.
        $paymentMethodAppConfigurationTransfer = $this->getDefaultPaymentMethodAppConfiguration();

        if ($paymentMethodTransfer->getPaymentMethodAppConfiguration() instanceof PaymentMethodAppConfigurationTransfer) {
            $paymentMethodAppConfigurationTransfer->setCheckoutConfiguration($paymentMethodTransfer->getPaymentMethodAppConfiguration()->getCheckoutConfiguration());
        }

        $updatePaymentMethodTransfer = new UpdatePaymentMethodTransfer();
        $updatePaymentMethodTransfer
            ->setName($paymentMethodTransfer->getNameOrFail())
            ->setProviderName($paymentMethodTransfer->getProviderNameOrFail())
            ->setPaymentAuthorizationEndpoint(sprintf('%s/private/initialize-payment', $this->appPaymentConfig->getGlueBaseUrl()))
            ->setPaymentMethodAppConfiguration($paymentMethodAppConfigurationTransfer);

        $paymentMethodTransfer->setPaymentMethodAppConfiguration($paymentMethodAppConfigurationTransfer);

        $this->appPaymentRepository->savePaymentMethod($paymentMethodTransfer, $appConfigTransfer->getTenantIdentifierOrFail());

        $this->paymentMethodMessageSender->sendUpdatePaymentMethodMessage($updatePaymentMethodTransfer, $appConfigTransfer);
    }

    /**
     * @param array<\Generated\Shared\Transfer\PaymentMethodTransfer> $persistedPaymentMethodTransfers
     * @param array<\Generated\Shared\Transfer\PaymentMethodTransfer> $configuredPaymentMethodTransfers
     *
     * @return array<\Generated\Shared\Transfer\PaymentMethodTransfer>
     */
    protected function getPaymentMethodsToAdd(array $persistedPaymentMethodTransfers, array $configuredPaymentMethodTransfers): array
    {
        $methodsToAdd = [];

        foreach ($configuredPaymentMethodTransfers as $configuredPaymentMethodTransfer) {
            if (!$this->isPaymentMethodPersisted($configuredPaymentMethodTransfer->getNameOrFail(), $persistedPaymentMethodTransfers)) {
                $methodsToAdd[] = $configuredPaymentMethodTransfer;
            }
        }

        return $methodsToAdd;
    }

    /**
     * @param array<\Generated\Shared\Transfer\PaymentMethodTransfer> $persistedPaymentMethodTransfers
     */
    protected function isPaymentMethodPersisted(string $paymentMethodName, array $persistedPaymentMethodTransfers): bool
    {
        foreach ($persistedPaymentMethodTransfers as $persistedPaymentMethodTransfer) {
            if ($paymentMethodName === $persistedPaymentMethodTransfer->getName()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<\Generated\Shared\Transfer\PaymentMethodTransfer> $persistedPaymentMethodTransfers
     * @param array<\Generated\Shared\Transfer\PaymentMethodTransfer> $configuredPaymentMethodTransfers
     *
     * @return array<\Generated\Shared\Transfer\PaymentMethodTransfer>
     */
    protected function getPaymentMethodsToUpdate(array $persistedPaymentMethodTransfers, array $configuredPaymentMethodTransfers): array
    {
        $methodsToUpdate = [];

        foreach ($configuredPaymentMethodTransfers as $configuredPaymentMethodTransfer) {
            $persistedPaymentMethodTransfer = $this->getPersistedPaymentMethodByName($configuredPaymentMethodTransfer->getNameOrFail(), $persistedPaymentMethodTransfers);
            if (!$persistedPaymentMethodTransfer instanceof PaymentMethodTransfer) {
                continue;
            }

            if (!$this->isPaymentMethodUpdateRequired($configuredPaymentMethodTransfer, $persistedPaymentMethodTransfer)) {
                continue;
            }

            $methodsToUpdate[] = $configuredPaymentMethodTransfer;
        }

        return $methodsToUpdate;
    }

    /**
     * @param array<\Generated\Shared\Transfer\PaymentMethodTransfer> $persistedPaymentMethodTransfers
     */
    protected function getPersistedPaymentMethodByName(string $paymentMethodName, array $persistedPaymentMethodTransfers): ?PaymentMethodTransfer
    {
        foreach ($persistedPaymentMethodTransfers as $persistedPaymentMethodTransfer) {
            if ($paymentMethodName === $persistedPaymentMethodTransfer->getName()) {
                return $persistedPaymentMethodTransfer;
            }
        }

        return null;
    }

    protected function isPaymentMethodUpdateRequired(
        PaymentMethodTransfer $configuredPaymentMethodTransfer,
        PaymentMethodTransfer $persistedPaymentMethodTransfer
    ): bool {
        if (!$configuredPaymentMethodTransfer->getPaymentMethodAppConfiguration() instanceof PaymentMethodAppConfigurationTransfer && !$persistedPaymentMethodTransfer->getPaymentMethodAppConfiguration() instanceof PaymentMethodAppConfigurationTransfer) {
            return false;
        }

        if ($configuredPaymentMethodTransfer->getPaymentMethodAppConfiguration() instanceof PaymentMethodAppConfigurationTransfer && !$persistedPaymentMethodTransfer->getPaymentMethodAppConfiguration() instanceof PaymentMethodAppConfigurationTransfer) {
            return true;
        }

        if ($persistedPaymentMethodTransfer->getPaymentMethodAppConfiguration() instanceof PaymentMethodAppConfigurationTransfer && !$configuredPaymentMethodTransfer->getPaymentMethodAppConfiguration() instanceof PaymentMethodAppConfigurationTransfer) {
            return true;
        }

        return $configuredPaymentMethodTransfer->getPaymentMethodAppConfigurationOrFail()->toArray() !== $persistedPaymentMethodTransfer->getPaymentMethodAppConfigurationOrFail()->toArray();
    }

    /**
     * @param array<\Generated\Shared\Transfer\PaymentMethodTransfer> $persistedPaymentMethodTransfers
     * @param array<\Generated\Shared\Transfer\PaymentMethodTransfer> $configuredPaymentMethodTransfers
     *
     * @return array<\Generated\Shared\Transfer\PaymentMethodTransfer>
     */
    protected function getPaymentMethodsToDelete(array $persistedPaymentMethodTransfers, array $configuredPaymentMethodTransfers): array
    {
        $methodsToDelete = [];

        foreach ($persistedPaymentMethodTransfers as $persistedPaymentMethodTransfer) {
            if (!$this->isPaymentMethodConfigured($persistedPaymentMethodTransfer->getNameOrFail(), $configuredPaymentMethodTransfers)) {
                $methodsToDelete[] = $persistedPaymentMethodTransfer;
            }
        }

        return $methodsToDelete;
    }

    /**
     * @param array<\Generated\Shared\Transfer\PaymentMethodTransfer> $configuredPaymentMethodTransfers
     */
    protected function isPaymentMethodConfigured(string $paymentMethodName, array $configuredPaymentMethodTransfers): bool
    {
        foreach ($configuredPaymentMethodTransfers as $configuredPaymentMethodTransfer) {
            if ($paymentMethodName === $configuredPaymentMethodTransfer->getName()) {
                return true;
            }
        }

        return false;
    }

    public function deletePaymentMethods(AppConfigTransfer $appConfigTransfer): AppConfigTransfer
    {
        $tenantIdentifier = $appConfigTransfer->getTenantIdentifierOrFail();

        // Get current persisted Tenants payment methods
        $persistedPaymentMethodTransfers = $this->appPaymentRepository->getTenantPaymentMethods($tenantIdentifier);

        foreach ($persistedPaymentMethodTransfers as $persistedPaymentMethodTransfer) {
            $this->deletePaymentMethod($persistedPaymentMethodTransfer, $appConfigTransfer);
        }

        return $appConfigTransfer;
    }

    protected function deletePaymentMethod(PaymentMethodTransfer $paymentMethodTransfer, AppConfigTransfer $appConfigTransfer): void
    {
        $deletePaymentMethodTransfer = new DeletePaymentMethodTransfer();
        $deletePaymentMethodTransfer
            ->setName($paymentMethodTransfer->getNameOrFail())
            ->setProviderName($paymentMethodTransfer->getProviderNameOrFail());

        $this->paymentMethodMessageSender->sendDeletePaymentMethodMessage($deletePaymentMethodTransfer, $appConfigTransfer);
        $this->appPaymentRepository->deletePaymentMethod($paymentMethodTransfer, $appConfigTransfer->getTenantIdentifierOrFail());
    }

    /**
     * Returns the default payment method app configuration which contains known endpoints for each PSP.
     * These defaults will not be needed to be configured by the PSP App itself
     */
    protected function getDefaultPaymentMethodAppConfiguration(): PaymentMethodAppConfigurationTransfer
    {
        $paymentMethodAppConfigurationTransfer = new PaymentMethodAppConfigurationTransfer();
        $paymentMethodAppConfigurationTransfer->setBaseUrl($this->appPaymentConfig->getGlueBaseUrl());

        $authorizationEndpointTransfer = new EndpointTransfer();
        $authorizationEndpointTransfer
            ->setName('authorization')
            ->setPath('/private/initialize-payment'); // Defined in app_payment_openapi.yml

        $paymentMethodAppConfigurationTransfer->addEndpoint($authorizationEndpointTransfer);

        $authorizationEndpointTransfer = new EndpointTransfer();
        $authorizationEndpointTransfer
            ->setName('pre-order-confirmation')
            ->setPath('/private/confirm-pre-order-payment'); // Defined in app_payment_openapi.yml

        $paymentMethodAppConfigurationTransfer->addEndpoint($authorizationEndpointTransfer);

        $authorizationEndpointTransfer = new EndpointTransfer();
        $authorizationEndpointTransfer
            ->setName('pre-order-cancellation')
            ->setPath('/private/cancel-pre-order-payment'); // Defined in app_payment_openapi.yml

        $paymentMethodAppConfigurationTransfer->addEndpoint($authorizationEndpointTransfer);

        $transferEndpointTransfer = new EndpointTransfer();
        $transferEndpointTransfer
            ->setName('transfer')
            ->setPath('/private/payments/transfers'); // Defined in app_payment_openapi.yml

        $paymentMethodAppConfigurationTransfer->addEndpoint($transferEndpointTransfer);

        $transferEndpointTransfer = new EndpointTransfer();
        $transferEndpointTransfer
            ->setName('customer')
            ->setPath('/private/customer'); // Defined in app_payment_openapi.yml

        $paymentMethodAppConfigurationTransfer->addEndpoint($transferEndpointTransfer);

        return $paymentMethodAppConfigurationTransfer;
    }
}
