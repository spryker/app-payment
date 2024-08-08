<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Shared\AppPayment\Helper;

use Codeception\Module;
use Generated\Shared\DataBuilder\PaymentMethodBuilder;
use Generated\Shared\Transfer\EndpointTransfer;
use Generated\Shared\Transfer\PaymentMethodAppConfigurationTransfer;
use Generated\Shared\Transfer\PaymentMethodTransfer;
use Orm\Zed\AppPayment\Persistence\SpyPaymentMethod;
use Orm\Zed\AppPayment\Persistence\SpyPaymentMethodQuery;
use Spryker\Zed\AppPayment\AppPaymentConfig;
use SprykerTest\Shared\Testify\Helper\DataCleanupHelperTrait;

class AppPaymentMethodHelper extends Module
{
    use DataCleanupHelperTrait;

    public function havePaymentMethod(array $seed = []): PaymentMethodTransfer
    {
        $paymentMethodTransfer = (new PaymentMethodBuilder($seed))->build();

        return $paymentMethodTransfer;
    }

    public function havePaymentMethodPersisted(array $seed = []): PaymentMethodTransfer
    {
        $paymentMethodTransfer = $this->havePaymentMethod($seed);

        $paymentMethodData = $paymentMethodTransfer->modifiedToArray();

        if (isset($paymentMethodData['payment_method_app_configuration'])) {
            $paymentMethodData['payment_method_app_configuration'] = json_encode($paymentMethodData['payment_method_app_configuration']);
        }

        $paymentMethodEntity = new SpyPaymentMethod();
        $paymentMethodEntity->fromArray($paymentMethodData);
        $paymentMethodEntity->save();

        $this->getDataCleanupHelper()->_addCleanup(function () use ($paymentMethodEntity): void {
            $paymentMethodEntity->delete();
        });

        return $paymentMethodTransfer;
    }

    public function seePaymentMethodForTenant(string $paymentMethodName, string $tenantIdentifier): void
    {
        $paymentMethodEntity = (new SpyPaymentMethodQuery())
            ->filterByName($paymentMethodName)
            ->filterByTenantIdentifier($tenantIdentifier)
            ->findOne();

        $this->assertNotNull($paymentMethodEntity, sprintf('Expected to find a payment method "%s" for the Tenant "%s" but none found!', $paymentMethodName, $tenantIdentifier));
    }

    public function dontSeePaymentMethodForTenant(string $paymentMethodName, string $tenantIdentifier): void
    {
        $paymentMethodEntity = (new SpyPaymentMethodQuery())
            ->filterByName($paymentMethodName)
            ->filterByTenantIdentifier($tenantIdentifier)
            ->findOne();

        $this->assertNull($paymentMethodEntity, sprintf('Expected not to find a payment method "%s" for the Tenant "%s" but found one!', $paymentMethodName, $tenantIdentifier));
    }

    /**
     * @see {@link PaymentMethod::getDefaultPaymentMethodAppConfiguration()}
     */
    public function getDefaultPaymentMethodAppConfiguration(): PaymentMethodAppConfigurationTransfer
    {
        $appPaymentConfig = new AppPaymentConfig();

        $paymentMethodAppConfigurationTransfer = new PaymentMethodAppConfigurationTransfer();
        $paymentMethodAppConfigurationTransfer->setBaseUrl($appPaymentConfig->getGlueBaseUrl());

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
