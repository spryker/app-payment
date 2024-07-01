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
use Generated\Shared\Transfer\InitializePaymentRequestTransfer;
use Generated\Shared\Transfer\InitializePaymentResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use GuzzleHttp\RequestOptions;
use Ramsey\Uuid\Uuid;
use Spryker\Zed\AppPayment\AppPaymentConfig;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Dependency\Plugin\PlatformPluginInterface;
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
 * @group InitializePaymentApiTest
 * Add your own group annotations below this line
 */
class InitializePaymentApiTest extends Unit
{
    use DependencyHelperTrait;

    protected AppPaymentBackendApiTester $tester;

    public function testInitializePaymentPostRequestReturnsHttpResponseCode200AndPersistsQuoteWithTransactionId(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();

        $initializePaymentRequestTransfer = $this->tester->haveInitializePaymentRequestTransfer();
        $this->tester->haveAppConfigForTenant($initializePaymentRequestTransfer->getTenantIdentifier());

        $initializePaymentResponseTransfer = new InitializePaymentResponseTransfer();
        $initializePaymentResponseTransfer
            ->setIsSuccessful(true)
            ->setTransactionId($transactionId);

        $platformPluginMock = Stub::makeEmpty(PlatformPluginInterface::class, [
            'initializePayment' => function (InitializePaymentRequestTransfer $initializePaymentRequestTransfer) use ($initializePaymentResponseTransfer) {
                // Ensure that the AppConfig is always passed to the platform plugin.
                $this->assertInstanceOf(AppConfigTransfer::class, $initializePaymentRequestTransfer->getAppConfig());

                return $initializePaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(AppPaymentConfig::HEADER_TENANT_IDENTIFIER, $initializePaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $response = $this->tester->sendPost($this->tester->buildPaymentUrl(), [RequestOptions::FORM_PARAMS => $initializePaymentRequestTransfer->toArray()]);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);
        $this->tester->seeResponseIsJson();
        $this->tester->seeResponseJsonContainsPayment($response);

        $this->tester->assertPaymentWithTransactionIdExists($transactionId);
        $this->tester->assertSamePaymentQuoteAndRequestQuote($transactionId, $initializePaymentRequestTransfer->getOrderData());
    }

    public function testGivenAPaymentWithOrderReferenceIsAlreadyUsedByOneTenantWhenAnotherTenantInitializesPaymentWithTheSameOrderReferenceThenThePaymentForTheNewTenantIsPersisted(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();

        $initializePaymentRequestTransfer = $this->tester->haveInitializePaymentRequestTransfer();
        $this->tester->haveAppConfigForTenant($initializePaymentRequestTransfer->getTenantIdentifier());

        // Create a payment with the same order reference for another tenant.
        $this->tester->havePayment([QuoteTransfer::ORDER_REFERENCE => $initializePaymentRequestTransfer->getOrderData()->getOrderReference()]);

        $initializePaymentResponseTransfer = new InitializePaymentResponseTransfer();
        $initializePaymentResponseTransfer
            ->setIsSuccessful(true)
            ->setTransactionId($transactionId);

        $platformPluginMock = Stub::makeEmpty(PlatformPluginInterface::class, [
            'initializePayment' => function (InitializePaymentRequestTransfer $initializePaymentRequestTransfer) use ($initializePaymentResponseTransfer) {
                // Ensure that the AppConfig is always passed to the platform plugin.
                $this->assertInstanceOf(AppConfigTransfer::class, $initializePaymentRequestTransfer->getAppConfig());

                return $initializePaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(AppPaymentConfig::HEADER_TENANT_IDENTIFIER, $initializePaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $response = $this->tester->sendPost($this->tester->buildPaymentUrl(), [RequestOptions::FORM_PARAMS => $initializePaymentRequestTransfer->toArray()]);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);
        $this->tester->seeResponseIsJson();
        $this->tester->seeResponseJsonContainsPayment($response);

        $this->tester->assertPaymentWithTransactionIdExists($transactionId);
        $this->tester->assertSamePaymentQuoteAndRequestQuote($transactionId, $initializePaymentRequestTransfer->getOrderData());
    }

    public function testInitializePaymentPostRequestReturnsHttpResponseCode200WhenAnExceptionOccurs(): void
    {
        // Arrange
        $initializePaymentRequestTransfer = $this->tester->haveInitializePaymentRequestTransfer();
        $url = $this->tester->buildPaymentUrl();

        $platformPluginMock = Stub::makeEmpty(PlatformPluginInterface::class, [
            'initializePayment' => static function (): never {
                throw new Exception();
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(AppPaymentConfig::HEADER_TENANT_IDENTIFIER, $initializePaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $this->tester->sendPost($url, [RequestOptions::FORM_PARAMS => $initializePaymentRequestTransfer->toArray()]);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);
    }

    public function testInitializePaymentPostRequestReturnsHttpResponseCode200WhenPlatformPaymentInitializationFailed(): void
    {
        // Arrange
        $initializePaymentRequestTransfer = $this->tester->haveInitializePaymentRequestTransfer();
        $this->tester->haveAppConfigForTenant($initializePaymentRequestTransfer->getTenantIdentifier());

        $initializePaymentResponseTransfer = new InitializePaymentResponseTransfer();
        $initializePaymentResponseTransfer
            ->setIsSuccessful(false);

        $platformPluginMock = Stub::makeEmpty(PlatformPluginInterface::class, [
            'initializePayment' => $initializePaymentResponseTransfer,
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(AppPaymentConfig::HEADER_TENANT_IDENTIFIER, $initializePaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $this->tester->sendPost($this->tester->buildPaymentUrl(), [RequestOptions::FORM_PARAMS => $initializePaymentRequestTransfer->toArray()]);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);
    }

    public function testInitializePaymentPostRequestReturnsHttpResponseWithErrorMessageWhenAnExceptionOccurs(): void
    {
        // Arrange
        $initializePaymentRequestTransfer = $this->tester->haveInitializePaymentRequestTransfer();
        $this->tester->haveAppConfigForTenant($initializePaymentRequestTransfer->getTenantIdentifier());
        $url = $this->tester->buildPaymentUrl();

        $platformPluginMock = Stub::makeEmpty(PlatformPluginInterface::class, [
            'initializePayment' => static function (): never {
                throw new Exception('An Error occurred in the platform plugin.');
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(AppPaymentConfig::HEADER_TENANT_IDENTIFIER, $initializePaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $response = $this->tester->sendPost($url, [RequestOptions::FORM_PARAMS => $initializePaymentRequestTransfer->toArray()]);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);
        $this->tester->assertResponseHasErrorMessage($response, 'An Error occurred in the platform plugin.');
    }
}
