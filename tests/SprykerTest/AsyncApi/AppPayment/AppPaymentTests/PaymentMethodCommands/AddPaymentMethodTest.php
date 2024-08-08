<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\AsyncApi\AppPayment\PaymentTests\PaymentMethodCommands;

use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\AddPaymentMethodTransfer;
use Generated\Shared\Transfer\AppConfigTransfer;
use Generated\Shared\Transfer\CheckoutConfigurationTransfer;
use Generated\Shared\Transfer\PaymentMethodAppConfigurationTransfer;
use Generated\Shared\Transfer\PaymentMethodConfigurationResponseTransfer;
use Generated\Shared\Transfer\PaymentMethodTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Zed\AppKernel\AppKernelConfig;
use Spryker\Zed\AppKernel\AppKernelDependencyProvider;
use Spryker\Zed\AppKernel\Business\AppKernelFacade;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Communication\Plugin\AppKernel\ConfigurePaymentMethodsConfigurationAfterSavePlugin;
use Spryker\Zed\AppPayment\Communication\Plugin\AppKernel\SendAddPaymentMethodMessageConfigurationAfterSavePlugin;
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
 * @group AddPaymentMethodTest
 * Add your own group annotations below this line
 */
class AddPaymentMethodTest extends Unit
{
    use DependencyHelperTrait;

    protected AppPaymentAsyncApiTester $tester;

    public function testAddPaymentMethodMessageIsSendWhenAppConfigIsNewAndPlatformPluginCanConfigurePaymentMethods(): void
    {
        // Arrange
        $platformPluginMock = Stub::makeEmpty(AppPaymentPaymentMethodsPlatformPluginInterface::class, [
            'configurePaymentMethods' => function ($paymentMethodConfigurationRequestTransfer) {
                $checkoutConfigurationTransfer = new CheckoutConfigurationTransfer();
                $checkoutConfigurationTransfer->setStrategy('embedded');

                $paymentMethodTransfer = new PaymentMethodTransfer();
                $paymentMethodTransfer
                    ->setName('foo')
                    ->setProviderName('bar')
                    ->setPaymentMethodAppConfiguration((new PaymentMethodAppConfigurationTransfer())->setCheckoutConfiguration($checkoutConfigurationTransfer));

                $paymentMethodConfigurationResponseTransfer = new PaymentMethodConfigurationResponseTransfer();
                $paymentMethodConfigurationResponseTransfer->addPaymentMethod($paymentMethodTransfer);

                return $paymentMethodConfigurationResponseTransfer;
            },
        ]);

        $this->tester->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);
        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_BEFORE_SAVE_PLUGINS, []);
        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_AFTER_SAVE_PLUGINS, [new ConfigurePaymentMethodsConfigurationAfterSavePlugin()]);

        $addPaymentMethodTransfer = $this->tester->haveAddPaymentMethodTransfer();

        $appConfigTransfer = new AppConfigTransfer();
        $appConfigTransfer
            ->setConfig(['business_model' => 'foo', 'my' => 'app', 'configuration' => 'data', 'mode' => 'test'])
            ->setTenantIdentifier(Uuid::uuid4()->toString())
            ->setIsActive(true);

        $appKernelFacade = new AppKernelFacade();
        $appKernelFacade->saveConfig($appConfigTransfer);

        // Assert
        $this->tester->assertMessageWasEmittedOnChannel($addPaymentMethodTransfer, 'payment-method-commands');
    }

    public function testAddPaymentMethodMessageIsNotSendWhenPaymentMethodHasChanged(): void
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

        // Assert
        $this->tester->assertMessageWasNotSent(AddPaymentMethodTransfer::class, 'payment-method-commands');
    }

    public function testAddPaymentMethodMessageIsSendWhenAppConfigIsConnected(): void
    {
        // Arrange
        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_BEFORE_SAVE_PLUGINS, []);
        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_AFTER_SAVE_PLUGINS, [new ConfigurePaymentMethodsConfigurationAfterSavePlugin()]);

        $addPaymentMethodTransfer = $this->tester->haveAddPaymentMethodTransfer();

        $appConfigTransfer = new AppConfigTransfer();
        $appConfigTransfer
            ->setConfig(['business_model' => 'foo', 'my' => 'app', 'configuration' => 'data', 'mode' => 'test'])
            ->setTenantIdentifier(Uuid::uuid4()->toString())
            ->setIsActive(true)
            ->setStatus(AppKernelConfig::APP_STATUS_CONNECTED);

        $appKernelFacade = new AppKernelFacade();
        $appKernelFacade->saveConfig($appConfigTransfer);

        // Assert
        $this->tester->assertMessageWasEmittedOnChannel($addPaymentMethodTransfer, 'payment-method-commands');
    }

    public function testAddPaymentMethodMessageIsNotSendWhenAppConfigStateIsDisconnected(): void
    {
        // Arrange
        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_BEFORE_SAVE_PLUGINS, []);
        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_AFTER_SAVE_PLUGINS, [new ConfigurePaymentMethodsConfigurationAfterSavePlugin()]);

        $appConfigTransfer = new AppConfigTransfer();
        $appConfigTransfer
            ->setConfig(['business_model' => 'foo', 'my' => 'app', 'configuration' => 'data', 'mode' => 'test'])
            ->setTenantIdentifier(Uuid::uuid4()->toString())
            ->setStatus(AppKernelConfig::APP_STATUS_DISCONNECTED);

        $appKernelFacade = new AppKernelFacade();
        $appKernelFacade->saveConfig($appConfigTransfer);

        // Assert
        $this->tester->assertMessageWasNotSent(AddPaymentMethodTransfer::class);
    }

    /**
     * @deprecated Can be removed with next major release.
     */
    public function testAddPaymentMethodMessageIsSendWhenAppConfigIsNew(): void
    {
        // Arrange
        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_BEFORE_SAVE_PLUGINS, []);
        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_AFTER_SAVE_PLUGINS, [new SendAddPaymentMethodMessageConfigurationAfterSavePlugin()]);

        $addPaymentMethodTransfer = $this->tester->haveAddPaymentMethodTransfer();

        $appConfigTransfer = new AppConfigTransfer();
        $appConfigTransfer
            ->setConfig(['business_model' => 'foo', 'my' => 'app', 'configuration' => 'data', 'mode' => 'test'])
            ->setTenantIdentifier(Uuid::uuid4()->toString())
            ->setIsActive(true);

        $appKernelFacade = new AppKernelFacade();
        $appKernelFacade->saveConfig($appConfigTransfer);

        // Assert
        $this->tester->assertMessageWasEmittedOnChannel($addPaymentMethodTransfer, 'payment-method-commands');
    }

    /**
     * @deprecated Can be removed with next major release.
     */
    public function testAddPaymentMethodMessageIsSendWhenAppConfigIsNewDeprecated(): void
    {
        // Arrange
        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_BEFORE_SAVE_PLUGINS, []);
        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_AFTER_SAVE_PLUGINS, [new SendAddPaymentMethodMessageConfigurationAfterSavePlugin()]);

        $addPaymentMethodTransfer = $this->tester->haveAddPaymentMethodTransfer();

        $appConfigTransfer = new AppConfigTransfer();
        $appConfigTransfer
            ->setConfig(['business_model' => 'foo', 'my' => 'app', 'configuration' => 'data', 'mode' => 'test'])
            ->setTenantIdentifier(Uuid::uuid4()->toString())
            ->setIsActive(true);

        $appKernelFacade = new AppKernelFacade();
        $appKernelFacade->saveConfig($appConfigTransfer);

        // Assert
        $this->tester->assertMessageWasEmittedOnChannel($addPaymentMethodTransfer, 'payment-method-commands');
    }

    /**
     * @deprecated Can be removed with next major release.
     */
    public function testAddPaymentMethodMessageIsSendWhenAppConfigIsConnectedDeprecated(): void
    {
        // Arrange
        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_BEFORE_SAVE_PLUGINS, []);
        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_AFTER_SAVE_PLUGINS, [new SendAddPaymentMethodMessageConfigurationAfterSavePlugin()]);

        $addPaymentMethodTransfer = $this->tester->haveAddPaymentMethodTransfer();

        $appConfigTransfer = new AppConfigTransfer();
        $appConfigTransfer
            ->setConfig(['business_model' => 'foo', 'my' => 'app', 'configuration' => 'data', 'mode' => 'test'])
            ->setTenantIdentifier(Uuid::uuid4()->toString())
            ->setIsActive(true)
            ->setStatus(AppKernelConfig::APP_STATUS_CONNECTED);

        $appKernelFacade = new AppKernelFacade();
        $appKernelFacade->saveConfig($appConfigTransfer);

        // Assert
        $this->tester->assertMessageWasEmittedOnChannel($addPaymentMethodTransfer, 'payment-method-commands');
    }

    /**
     * @deprecated Can be removed with next major release.
     */
    public function testAddPaymentMethodMessageIsNotSendWhenAppConfigStateIsDisconnectedDeprecated(): void
    {
        // Arrange
        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_BEFORE_SAVE_PLUGINS, []);
        $this->tester->setDependency(AppKernelDependencyProvider::PLUGIN_CONFIGURATION_AFTER_SAVE_PLUGINS, [new SendAddPaymentMethodMessageConfigurationAfterSavePlugin()]);

        $appConfigTransfer = new AppConfigTransfer();
        $appConfigTransfer
            ->setConfig(['business_model' => 'foo', 'my' => 'app', 'configuration' => 'data', 'mode' => 'test'])
            ->setTenantIdentifier(Uuid::uuid4()->toString())
            ->setStatus(AppKernelConfig::APP_STATUS_DISCONNECTED);

        $appKernelFacade = new AppKernelFacade();
        $appKernelFacade->saveConfig($appConfigTransfer);

        // Assert
        $this->tester->assertMessageWasNotSent(AddPaymentMethodTransfer::class);
    }
}
