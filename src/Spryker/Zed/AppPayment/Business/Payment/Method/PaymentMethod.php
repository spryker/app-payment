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
use Spryker\Zed\AppKernel\AppKernelConfig;
use Spryker\Zed\AppPayment\AppPaymentConfig;
use Spryker\Zed\AppPayment\Business\Payment\Message\PaymentMethodMessageSender;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPaymentMethodsPlatformPluginInterface;
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

    public function addPaymentMethods(AppConfigTransfer $appConfigTransfer): AppConfigTransfer
    {
        // Do not send the message(s) when App is in state "disconnected" or when the app is marked as inactive.
        if ($appConfigTransfer->getStatus() === AppKernelConfig::APP_STATUS_DISCONNECTED || $appConfigTransfer->getIsActive() === false) {
            return $appConfigTransfer;
        }

        if (!($this->appPaymentPlatformPlugin instanceof AppPaymentPaymentMethodsPlatformPluginInterface)) {
            $paymentMethodTransfer = $this->getDefaultPaymentMethodTransfer();

            $this->addPaymentMethod($paymentMethodTransfer, $appConfigTransfer);

            return $appConfigTransfer;
        }

        $tenantIdentifier = $appConfigTransfer->getTenantIdentifierOrFail();

        $paymentMethodConfigurationRequestTransfer = new PaymentMethodConfigurationRequestTransfer();
        $paymentMethodConfigurationRequestTransfer->setAppConfig($appConfigTransfer);

        $paymentMethodCollectionResponseTransfer = $this->appPaymentPlatformPlugin->configurePaymentMethods($paymentMethodConfigurationRequestTransfer);

        // Get current persisted Tenants payment methods
        $persistedPaymentMethodTransfers = $this->appPaymentRepository->getTenantPaymentMethods($tenantIdentifier);

        // Get current configured payment methods
        $configuredPaymentMethodTransfers = $paymentMethodCollectionResponseTransfer->getPaymentMethods()->getArrayCopy();

        $paymentMethodTransfersToAdd = $this->getPaymentMethodsToAdd($persistedPaymentMethodTransfers, $configuredPaymentMethodTransfers);
        $paymentMethodTransfersToDelete = $this->getPaymentMethodsToDelete($persistedPaymentMethodTransfers, $configuredPaymentMethodTransfers);

        foreach ($paymentMethodTransfersToAdd as $paymentMethodTransferToAdd) {
            $this->addPaymentMethod($paymentMethodTransferToAdd, $appConfigTransfer);
        }

        foreach ($paymentMethodTransfersToDelete as $paymentMethodTransferToDelete) {
            $this->deletePaymentMethod($paymentMethodTransferToDelete, $appConfigTransfer);
        }

        return $appConfigTransfer;
    }

    protected function addPaymentMethod(PaymentMethodTransfer $paymentMethodTransfer, AppConfigTransfer $appConfigTransfer): void
    {
        // Add the passed configuration to the default configuration.
        $paymentMethodAppConfigurationTransfer = $this->getDefaultPaymentMethodAppConfiguration();

        if ($paymentMethodTransfer->getPaymentMethodAppConfiguration() instanceof PaymentMethodAppConfigurationTransfer) {
            $paymentMethodAppConfigurationTransfer->setCheckoutConfiguration($paymentMethodTransfer->getPaymentMethodAppConfiguration()->getCheckoutConfiguration());
        }

        $addPaymentMethodTransfer = new AddPaymentMethodTransfer();
        $addPaymentMethodTransfer
            ->setName($paymentMethodTransfer->getNameOrFail())
            ->setProviderName($paymentMethodTransfer->getProviderNameOrFail())
            ->setPaymentAuthorizationEndpoint(sprintf('%s/private/initialize-payment', $this->appPaymentConfig->getGlueBaseUrl()))
            ->setPaymentMethodAppConfiguration($paymentMethodAppConfigurationTransfer);

        $this->appPaymentRepository->savePaymentMethod($paymentMethodTransfer, $appConfigTransfer->getTenantIdentifierOrFail());

        $this->paymentMethodMessageSender->sendAddPaymentMethodMessage($addPaymentMethodTransfer, $appConfigTransfer);
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
        if (!($this->appPaymentPlatformPlugin instanceof AppPaymentPaymentMethodsPlatformPluginInterface)) {
            $paymentMethodTransfer = $this->getDefaultPaymentMethodTransfer();

            $this->deletePaymentMethod($paymentMethodTransfer, $appConfigTransfer);

            return $appConfigTransfer;
        }

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

    protected function getDefaultPaymentMethodTransfer(): PaymentMethodTransfer
    {
        $paymentMethodTransfer = new PaymentMethodTransfer();
        $paymentMethodTransfer
            ->setName($this->appPaymentConfig->getPaymentMethodName())
            ->setProviderName($this->appPaymentConfig->getPaymentProviderName());

        return $paymentMethodTransfer;
    }

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
            ->setName('pre-order')
            ->setPath('/private/confirm-pre-order-payment'); // Defined in app_payment_openapi.yml

        $paymentMethodAppConfigurationTransfer->addEndpoint($authorizationEndpointTransfer);

        $transferEndpointTransfer = new EndpointTransfer();
        $transferEndpointTransfer
            ->setName('transfer')
            ->setPath('/private/payments/transfers'); // Defined in app_payment_openapi.yml

        $paymentMethodAppConfigurationTransfer->addEndpoint($transferEndpointTransfer);

        return $paymentMethodAppConfigurationTransfer;
    }
}
