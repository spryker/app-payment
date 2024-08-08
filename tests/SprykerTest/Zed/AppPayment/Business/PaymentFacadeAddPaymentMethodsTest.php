<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Zed\AppPayment\Business;

use Codeception\Stub;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\CheckoutConfigurationTransfer;
use Generated\Shared\Transfer\PaymentMethodAppConfigurationTransfer;
use Generated\Shared\Transfer\PaymentMethodConfigurationResponseTransfer;
use Generated\Shared\Transfer\PaymentMethodTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPaymentMethodsPlatformPluginInterface;
use SprykerTest\Zed\AppPayment\AppPaymentBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group AppPayment
 * @group Business
 * @group Facade
 * @group PaymentFacadeAddPaymentMethodsTest
 * Add your own group annotations below this line
 */
class PaymentFacadeAddPaymentMethodsTest extends Unit
{
    protected AppPaymentBusinessTester $tester;

    /**
     * @return void
     */
    public function testGivenAnAppHasMultiplePaymentMethodsAvailableWhenIConfigureTheAppThenEachPaymentMethodThatIsReturnedFromTheAppPaymentPaymentMethodsPlatformPluginIsPersisted(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $paymentMethodNameFoo = Uuid::uuid4()->toString();
        $paymentMethodNameBar = Uuid::uuid4()->toString();

        $appConfigTransfer = $this->tester->haveAppConfigForTenant($tenantIdentifier, [
            'paymentMethods' => [ // PayOne uses "selectedPaymentMethodSettings" instead of "paymentMethods"
                $paymentMethodNameFoo,
                $paymentMethodNameBar,
            ],
        ]);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPaymentMethodsPlatformPluginInterface::class, [
            'configurePaymentMethods' => function () use ($paymentMethodNameFoo, $paymentMethodNameBar) {
                $paymentMethodConfigurationResponseTransfer = new PaymentMethodConfigurationResponseTransfer();

                $checkoutConfigurationTransfer = new CheckoutConfigurationTransfer();
                $checkoutConfigurationTransfer->setStrategy('embedded');

                $paymentMethodTransfer = new PaymentMethodTransfer();
                $paymentMethodTransfer
                    ->setName($paymentMethodNameFoo)
                    ->setProviderName('psp provider name')
                    ->setPaymentMethodAppConfiguration((new PaymentMethodAppConfigurationTransfer())->setCheckoutConfiguration($checkoutConfigurationTransfer));

                $paymentMethodConfigurationResponseTransfer->addPaymentMethod($paymentMethodTransfer);

                $paymentMethodTransfer = new PaymentMethodTransfer();
                $paymentMethodTransfer
                    ->setName($paymentMethodNameBar)
                    ->setProviderName('psp provider name')
                    ->setPaymentMethodAppConfiguration((new PaymentMethodAppConfigurationTransfer())->setCheckoutConfiguration($checkoutConfigurationTransfer));

                $paymentMethodConfigurationResponseTransfer->addPaymentMethod($paymentMethodTransfer);

                return $paymentMethodConfigurationResponseTransfer;
            },
        ]);
        $this->tester->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->getFacade()->configurePaymentMethods($appConfigTransfer);

        // Assert
        $this->tester->seePaymentMethodForTenant($paymentMethodNameFoo, $tenantIdentifier);
        $this->tester->seePaymentMethodForTenant($paymentMethodNameBar, $tenantIdentifier);
    }

    /**
     * @return void
     */
    public function testGivenTwoPaymentMethodsArePersistedWhenOnlyOneIsConfiguredToBeUsedThenOnePaymentMethodIsPersisted(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $paymentMethodNameFoo = Uuid::uuid4()->toString();
        $paymentMethodNameBar = Uuid::uuid4()->toString();

        $this->tester->havePaymentMethodPersisted([
            PaymentMethodTransfer::NAME => $paymentMethodNameFoo,
            PaymentMethodTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
        ]);

        $this->tester->havePaymentMethodPersisted([
            PaymentMethodTransfer::NAME => $paymentMethodNameBar,
            PaymentMethodTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
        ]);

        // Ensure payment methods are persisted
        $this->tester->seePaymentMethodForTenant($paymentMethodNameFoo, $tenantIdentifier);
        $this->tester->seePaymentMethodForTenant($paymentMethodNameBar, $tenantIdentifier);

        $appConfigTransfer = $this->tester->haveAppConfigForTenant($tenantIdentifier, [
            'paymentMethods' => [ // PayOne uses "selectedPaymentMethodSettings" instead of "paymentMethods"
                $paymentMethodNameFoo,
            ],
        ]);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPaymentMethodsPlatformPluginInterface::class, [
            'configurePaymentMethods' => function () use ($paymentMethodNameFoo) {
                $paymentMethodConfigurationResponseTransfer = new PaymentMethodConfigurationResponseTransfer();

                $checkoutConfigurationTransfer = new CheckoutConfigurationTransfer();
                $checkoutConfigurationTransfer->setStrategy('embedded');

                $paymentMethodTransfer = new PaymentMethodTransfer();
                $paymentMethodTransfer
                    ->setName($paymentMethodNameFoo)
                    ->setProviderName('psp provider name')
                    ->setPaymentMethodAppConfiguration((new PaymentMethodAppConfigurationTransfer())->setCheckoutConfiguration($checkoutConfigurationTransfer));

                $paymentMethodConfigurationResponseTransfer->addPaymentMethod($paymentMethodTransfer);

                return $paymentMethodConfigurationResponseTransfer;
            },
        ]);
        $this->tester->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->getFacade()->configurePaymentMethods($appConfigTransfer);

        // Assert
        $this->tester->seePaymentMethodForTenant($paymentMethodNameFoo, $tenantIdentifier);
    }
}
