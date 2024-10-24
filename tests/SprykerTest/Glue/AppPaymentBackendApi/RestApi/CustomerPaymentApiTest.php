<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Glue\AppPaymentBackendApi\RestApi;

use Codeception\Stub;
use Codeception\Test\Unit;
use Exception;
use Generated\Shared\Transfer\AppConfigTransfer;
use Generated\Shared\Transfer\CustomerRequestTransfer;
use Generated\Shared\Transfer\CustomerResponseTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Glue\AppPaymentBackendApi\Mapper\Payment\GlueRequestPaymentMapper;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Business\Message\MessageBuilder;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformCustomerPluginInterface;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;
use SprykerTest\Glue\AppPaymentBackendApi\AppPaymentBackendApiTester;
use SprykerTest\Shared\Testify\Helper\DependencyHelperTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Glue
 * @group AppPaymentBackendApi
 * @group RestApi
 * @group CustomerPaymentApiTest
 * Add your own group annotations below this line
 */
class CustomerPaymentApiTest extends Unit
{
    use DependencyHelperTrait;

    protected AppPaymentBackendApiTester $tester;

    public function testCustomerGetRequestReturnsHttpResponseCode200WhenTheCustomerIsReturnedFromThePluginImplementation(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();

        $customerRequestTransfer = $this->tester->haveCustomerRequestTransfer([
            CustomerRequestTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
        ]);

        $this->tester->haveAppConfigForTenant($customerRequestTransfer->getTenantIdentifier());

        $customerResponseTransfer = new CustomerResponseTransfer();
        $customerResponseTransfer
            ->setIsSuccessful(true)
            ->setCustomer($customerRequestTransfer->getCustomer());

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformCustomerPluginInterface::class, [
            'customer' => function (CustomerRequestTransfer $customerRequestTransfer) use ($customerResponseTransfer) {
                // Ensure that the AppConfig is always passed to the platform plugin.
                $this->assertInstanceOf(AppConfigTransfer::class, $customerRequestTransfer->getAppConfig());

                return $customerResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $customerRequestTransfer->getTenantIdentifier());

        $this->tester->sendPost($this->tester->buildCustomerUrl(), $customerRequestTransfer->toArray());

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);
    }

    public function testCustomerGetRequestReturnsHttpResponseCode400WhenThePluginImplementationThrowsAnException(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();

        $customerRequestTransfer = $this->tester->haveCustomerRequestTransfer([
            CustomerRequestTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
        ]);

        $this->tester->haveAppConfigForTenant($customerRequestTransfer->getTenantIdentifier());

        $customerResponseTransfer = new CustomerResponseTransfer();
        $customerResponseTransfer
            ->setIsSuccessful(true)
            ->setCustomer($customerRequestTransfer->getCustomer());

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformCustomerPluginInterface::class, [
            'customer' => function (CustomerRequestTransfer $customerRequestTransfer) use ($customerResponseTransfer): void {
                throw new Exception('Bad request');
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $customerRequestTransfer->getTenantIdentifier());

        $this->tester->sendPost($this->tester->buildCustomerUrl(), $customerRequestTransfer->toArray());

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
        $this->tester->seeResponseContainsErrorMessage('Bad request');
    }

    public function testCustomerGetRequestReturnsHttpResponseCode400WhenThePluginImplementationDoesNotProvideCustomerFeatures(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();

        $customerRequestTransfer = $this->tester->haveCustomerRequestTransfer([
            CustomerRequestTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
        ]);

        $this->tester->haveAppConfigForTenant($customerRequestTransfer->getTenantIdentifier());

        $customerResponseTransfer = new CustomerResponseTransfer();
        $customerResponseTransfer
            ->setIsSuccessful(true)
            ->setCustomer($customerRequestTransfer->getCustomer());

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $customerRequestTransfer->getTenantIdentifier());

        $this->tester->sendPost($this->tester->buildCustomerUrl(), $customerRequestTransfer->toArray());

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
        $this->tester->seeResponseContainsErrorMessage(MessageBuilder::getPlatformPluginDoesNotProvideCustomerFeatures());
    }

    public function testCustomerGetRequestReturnsHttpResponseCode422WhenNeitherACustomerNorCustomerPaymentServiceProviderDataIsPresent(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();

        $customerRequestTransfer = $this->tester->haveCustomerRequestTransfer([
            CustomerRequestTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
        ]);

        $customerRequestTransfer
            ->setCustomer(null)
            ->setCustomerPaymentServiceProviderData(null);

        $this->tester->haveAppConfigForTenant($customerRequestTransfer->getTenantIdentifier());

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformCustomerPluginInterface::class);
        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $customerRequestTransfer->getTenantIdentifier());

        $this->tester->sendPost($this->tester->buildCustomerUrl(), $customerRequestTransfer->toArray());

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->tester->seeResponseContainsErrorMessage(MessageBuilder::getNeitherACustomerNorCustomerPaymentProviderDataIsPresent());
    }
}
