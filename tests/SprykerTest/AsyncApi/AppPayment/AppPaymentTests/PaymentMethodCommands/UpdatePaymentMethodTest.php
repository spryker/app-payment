<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\AsyncApi\AppPayment\PaymentTests\PaymentMethodCommands;

use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\AppConfigTransfer;
use Generated\Shared\Transfer\CheckoutConfigurationTransfer;
use Generated\Shared\Transfer\PaymentMethodConfigurationResponseTransfer;
use Generated\Shared\Transfer\PaymentMethodTransfer;
use Generated\Shared\Transfer\UpdatePaymentMethodTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Zed\AppKernel\AppKernelDependencyProvider;
use Spryker\Zed\AppKernel\Business\AppKernelFacade;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Communication\Plugin\AppKernel\ConfigurePaymentMethodsConfigurationAfterSavePlugin;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPaymentMethodsPlatformPluginInterface;
use SprykerTest\AsyncApi\AppPayment\AppPaymentAsyncApiTester;
use SprykerTest\Shared\Testify\Helper\DependencyHelperTrait;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group AsyncApi
 * @group AppPayment
 * @group PaymentTests
 * @group PaymentMethodCommands
 * @group UpdatePaymentMethodTest
 * Add your own group annotations below this line
 */
class UpdatePaymentMethodTest extends Unit
{
    use DependencyHelperTrait;

    protected AppPaymentAsyncApiTester $tester;

    public function testUpdatePaymentMethodMessageIsSendWhenPaymentMethodHasChanged(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $paymentMethodName = Uuid::uuid4()->toString();
        $paymentProviderName = Uuid::uuid4()->toString();

        $checkoutConfigurationTransfer = new CheckoutConfigurationTransfer();
        $checkoutConfigurationTransfer->setStrategy('embedded');

        $paymentMethodAppConfigurationTransfer = $this->tester->getDefaultPaymentMethodAppConfiguration();
        $paymentMethodAppConfigurationTransfer->setCheckoutConfiguration($checkoutConfigurationTransfer);

        $this->tester->havePaymentMethodPersisted([
            PaymentMethodTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            PaymentMethodTransfer::NAME => $paymentMethodName,
            PaymentMethodTransfer::PROVIDER_NAME => $paymentProviderName,
            PaymentMethodTransfer::PAYMENT_METHOD_APP_CONFIGURATION => $paymentMethodAppConfigurationTransfer,
        ]);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPaymentMethodsPlatformPluginInterface::class, [
            'configurePaymentMethods' => function () use ($paymentMethodName, $paymentProviderName, $paymentMethodAppConfigurationTransfer) {
                $checkoutConfigurationTransfer = new CheckoutConfigurationTransfer();
                $checkoutConfigurationTransfer->setStrategy('updated-strategy');

                // This changes the configuration and must not trigger the AddPaymentMethod message
                $paymentMethodAppConfigurationTransfer->setCheckoutConfiguration($checkoutConfigurationTransfer);

                $paymentMethodTransfer = new PaymentMethodTransfer();
                $paymentMethodTransfer
                    ->setName($paymentMethodName)
                    ->setProviderName($paymentProviderName)
                    ->setPaymentMethodAppConfiguration($paymentMethodAppConfigurationTransfer);

                $paymentMethodConfigurationResponseTransfer = new PaymentMethodConfigurationResponseTransfer();
                $paymentMethodConfigurationResponseTransfer->addPaymentMethod($paymentMethodTransfer);

                return $paymentMethodConfigurationResponseTransfer;
            },
        ]);

        $this->tester->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);
        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_BEFORE_SAVE_PLUGINS, []);
        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_AFTER_SAVE_PLUGINS, [new ConfigurePaymentMethodsConfigurationAfterSavePlugin()]);

        $appConfigTransfer = new AppConfigTransfer();
        $appConfigTransfer
            ->setConfig(['business_model' => 'foo', 'my' => 'app', 'configuration' => 'data', 'mode' => 'test'])
            ->setTenantIdentifier($tenantIdentifier)
            ->setIsActive(true);

        $appKernelFacade = new AppKernelFacade();
        $appKernelFacade->saveConfig($appConfigTransfer);

        $updatePaymentMethodTransfer = $this->tester->haveUpdatePaymentMethodTransfer();

        // Assert
        $this->tester->assertMessageWasEmittedOnChannel($updatePaymentMethodTransfer, 'payment-method-commands');
    }

    public function testUpdatePaymentMethodMessageIsSendWhenPersistedPaymentMethodHasNoPaymentMethodAppConfigurationButNewConfigurationHasPaymentMethodAppConfiguration(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $paymentMethodName = Uuid::uuid4()->toString();
        $paymentProviderName = Uuid::uuid4()->toString();

        $checkoutConfigurationTransfer = new CheckoutConfigurationTransfer();
        $checkoutConfigurationTransfer->setStrategy('embedded');

        $paymentMethodAppConfigurationTransfer = $this->tester->getDefaultPaymentMethodAppConfiguration();
        $paymentMethodAppConfigurationTransfer->setCheckoutConfiguration($checkoutConfigurationTransfer);

        $this->tester->havePaymentMethodPersisted([
            PaymentMethodTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            PaymentMethodTransfer::NAME => $paymentMethodName,
            PaymentMethodTransfer::PROVIDER_NAME => $paymentProviderName,
        ]);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPaymentMethodsPlatformPluginInterface::class, [
            'configurePaymentMethods' => function () use ($paymentMethodName, $paymentProviderName, $paymentMethodAppConfigurationTransfer) {
                $checkoutConfigurationTransfer = new CheckoutConfigurationTransfer();
                $checkoutConfigurationTransfer->setStrategy('updated-strategy');

                // This changes the configuration and must not trigger the AddPaymentMethod message
                $paymentMethodAppConfigurationTransfer->setCheckoutConfiguration($checkoutConfigurationTransfer);

                $paymentMethodTransfer = new PaymentMethodTransfer();
                $paymentMethodTransfer
                    ->setName($paymentMethodName)
                    ->setProviderName($paymentProviderName)
                    ->setPaymentMethodAppConfiguration($paymentMethodAppConfigurationTransfer);

                $paymentMethodConfigurationResponseTransfer = new PaymentMethodConfigurationResponseTransfer();
                $paymentMethodConfigurationResponseTransfer->addPaymentMethod($paymentMethodTransfer);

                return $paymentMethodConfigurationResponseTransfer;
            },
        ]);

        $this->tester->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);
        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_BEFORE_SAVE_PLUGINS, []);
        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_AFTER_SAVE_PLUGINS, [new ConfigurePaymentMethodsConfigurationAfterSavePlugin()]);

        $appConfigTransfer = new AppConfigTransfer();
        $appConfigTransfer
            ->setConfig(['business_model' => 'foo', 'my' => 'app', 'configuration' => 'data', 'mode' => 'test'])
            ->setTenantIdentifier($tenantIdentifier)
            ->setIsActive(true);

        $appKernelFacade = new AppKernelFacade();
        $appKernelFacade->saveConfig($appConfigTransfer);

        $updatePaymentMethodTransfer = $this->tester->haveUpdatePaymentMethodTransfer();

        // Assert
        $this->tester->assertMessageWasEmittedOnChannel($updatePaymentMethodTransfer, 'payment-method-commands');
    }

    public function testUpdatePaymentMethodMessageIsSendWhenPersistedPaymentMethodHasPaymentMethodAppConfigurationButNewConfigurationHasNoPaymentMethodAppConfiguration(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $paymentMethodName = Uuid::uuid4()->toString();
        $paymentProviderName = Uuid::uuid4()->toString();

        $checkoutConfigurationTransfer = new CheckoutConfigurationTransfer();
        $checkoutConfigurationTransfer->setStrategy('embedded');

        $paymentMethodAppConfigurationTransfer = $this->tester->getDefaultPaymentMethodAppConfiguration();
        $paymentMethodAppConfigurationTransfer->setCheckoutConfiguration($checkoutConfigurationTransfer);

        $this->tester->havePaymentMethodPersisted([
            PaymentMethodTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            PaymentMethodTransfer::NAME => $paymentMethodName,
            PaymentMethodTransfer::PROVIDER_NAME => $paymentProviderName,
            PaymentMethodTransfer::PAYMENT_METHOD_APP_CONFIGURATION => $paymentMethodAppConfigurationTransfer,
        ]);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPaymentMethodsPlatformPluginInterface::class, [
            'configurePaymentMethods' => function () use ($paymentMethodName, $paymentProviderName) {
                $paymentMethodTransfer = new PaymentMethodTransfer();
                $paymentMethodTransfer
                    ->setName($paymentMethodName)
                    ->setProviderName($paymentProviderName);

                $paymentMethodConfigurationResponseTransfer = new PaymentMethodConfigurationResponseTransfer();
                $paymentMethodConfigurationResponseTransfer->addPaymentMethod($paymentMethodTransfer);

                return $paymentMethodConfigurationResponseTransfer;
            },
        ]);

        $this->tester->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);
        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_BEFORE_SAVE_PLUGINS, []);
        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_AFTER_SAVE_PLUGINS, [new ConfigurePaymentMethodsConfigurationAfterSavePlugin()]);

        $appConfigTransfer = new AppConfigTransfer();
        $appConfigTransfer
            ->setConfig(['business_model' => 'foo', 'my' => 'app', 'configuration' => 'data', 'mode' => 'test'])
            ->setTenantIdentifier($tenantIdentifier)
            ->setIsActive(true);

        $appKernelFacade = new AppKernelFacade();
        $appKernelFacade->saveConfig($appConfigTransfer);

        $updatePaymentMethodTransfer = $this->tester->haveUpdatePaymentMethodTransfer();

        // Assert
        $this->tester->assertMessageWasEmittedOnChannel($updatePaymentMethodTransfer, 'payment-method-commands');
    }

    public function testUpdatePaymentMethodMessageIsNotSendWhenBothPaymentMethodsDoNotHaveAnPaymentMethodAppConfiguration(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $paymentMethodName = Uuid::uuid4()->toString();
        $paymentProviderName = Uuid::uuid4()->toString();

        $this->tester->havePaymentMethodPersisted([
            PaymentMethodTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            PaymentMethodTransfer::NAME => $paymentMethodName,
            PaymentMethodTransfer::PROVIDER_NAME => $paymentProviderName,
        ]);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPaymentMethodsPlatformPluginInterface::class, [
            'configurePaymentMethods' => function () use ($paymentMethodName, $paymentProviderName) {
                $paymentMethodTransfer = new PaymentMethodTransfer();
                $paymentMethodTransfer
                    ->setName($paymentMethodName)
                    ->setProviderName($paymentProviderName);

                $paymentMethodConfigurationResponseTransfer = new PaymentMethodConfigurationResponseTransfer();
                $paymentMethodConfigurationResponseTransfer->addPaymentMethod($paymentMethodTransfer);

                return $paymentMethodConfigurationResponseTransfer;
            },
        ]);

        $this->tester->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);
        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_BEFORE_SAVE_PLUGINS, []);
        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_AFTER_SAVE_PLUGINS, [new ConfigurePaymentMethodsConfigurationAfterSavePlugin()]);

        $appConfigTransfer = new AppConfigTransfer();
        $appConfigTransfer
            ->setConfig(['business_model' => 'foo', 'my' => 'app', 'configuration' => 'data', 'mode' => 'test'])
            ->setTenantIdentifier($tenantIdentifier)
            ->setIsActive(true);

        $appKernelFacade = new AppKernelFacade();
        $appKernelFacade->saveConfig($appConfigTransfer);

        // Assert
        $this->tester->assertMessageWasNotSent(UpdatePaymentMethodTransfer::class, 'payment-method-commands');
    }

    public function testUpdatePaymentMethodMessageIsNotSendWhenPaymentMethodHasNotChanged(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $paymentMethodName = Uuid::uuid4()->toString();
        $paymentProviderName = Uuid::uuid4()->toString();

        $checkoutConfigurationTransfer = new CheckoutConfigurationTransfer();
        $checkoutConfigurationTransfer->setStrategy('embedded');

        $paymentMethodAppConfigurationTransfer = $this->tester->getDefaultPaymentMethodAppConfiguration();
        $paymentMethodAppConfigurationTransfer->setCheckoutConfiguration($checkoutConfigurationTransfer);

        $this->tester->havePaymentMethodPersisted([
            PaymentMethodTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            PaymentMethodTransfer::NAME => $paymentMethodName,
            PaymentMethodTransfer::PROVIDER_NAME => $paymentProviderName,
            PaymentMethodTransfer::PAYMENT_METHOD_APP_CONFIGURATION => $paymentMethodAppConfigurationTransfer,
        ]);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPaymentMethodsPlatformPluginInterface::class, [
            'configurePaymentMethods' => function () use ($paymentMethodName, $paymentProviderName, $paymentMethodAppConfigurationTransfer) {
                $paymentMethodTransfer = new PaymentMethodTransfer();
                $paymentMethodTransfer
                    ->setName($paymentMethodName)
                    ->setProviderName($paymentProviderName)
                    ->setPaymentMethodAppConfiguration($paymentMethodAppConfigurationTransfer);

                $paymentMethodConfigurationResponseTransfer = new PaymentMethodConfigurationResponseTransfer();
                $paymentMethodConfigurationResponseTransfer->addPaymentMethod($paymentMethodTransfer);

                return $paymentMethodConfigurationResponseTransfer;
            },
        ]);

        $this->tester->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);
        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_BEFORE_SAVE_PLUGINS, []);
        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_AFTER_SAVE_PLUGINS, [new ConfigurePaymentMethodsConfigurationAfterSavePlugin()]);

        $appConfigTransfer = new AppConfigTransfer();
        $appConfigTransfer
            ->setConfig(['business_model' => 'foo', 'my' => 'app', 'configuration' => 'data', 'mode' => 'test'])
            ->setTenantIdentifier($tenantIdentifier)
            ->setIsActive(true);

        $appKernelFacade = new AppKernelFacade();
        $appKernelFacade->saveConfig($appConfigTransfer);

        // Assert
        $this->tester->assertMessageWasNotSent(UpdatePaymentMethodTransfer::class, 'payment-method-commands');
    }
}
