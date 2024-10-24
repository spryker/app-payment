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
use Spryker\Glue\AppPaymentBackendApi\Mapper\Payment\GlueRequestPaymentMapper;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
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

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'initializePayment' => function (InitializePaymentRequestTransfer $initializePaymentRequestTransfer) use ($initializePaymentResponseTransfer) {
                // Ensure that the AppConfig is always passed to the platform plugin.
                $this->assertInstanceOf(AppConfigTransfer::class, $initializePaymentRequestTransfer->getAppConfig());

                return $initializePaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $initializePaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $response = $this->tester->sendPost($this->tester->buildPaymentUrl(), [RequestOptions::FORM_PARAMS => $initializePaymentRequestTransfer->toArray()]);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);
        $this->tester->seeResponseIsJson();
        $this->tester->seeResponseJsonContainsPayment($response);

        $this->tester->assertPaymentWithTransactionIdExists($transactionId);
        $this->tester->assertSamePaymentQuoteAndRequestQuote($transactionId, $initializePaymentRequestTransfer->getOrderData());
    }

    public function testInitializePaymentPostRequestReturnsHttpResponseCode200AndForwardsAdditionalPaymentDataToThePlatformImplementation(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();

        $additionalPaymentData = [
            'internalId' => Uuid::uuid4()->toString(),
            'externalId' => Uuid::uuid4()->toString(),
        ];

        $initializePaymentRequestTransfer = $this->tester->haveInitializePaymentRequestTransfer([], $additionalPaymentData);
        $this->tester->haveAppConfigForTenant($initializePaymentRequestTransfer->getTenantIdentifier());

        $initializePaymentResponseTransfer = new InitializePaymentResponseTransfer();
        $initializePaymentResponseTransfer
            ->setIsSuccessful(true)
            ->setTransactionId($transactionId);

        $forwardedAdditionalPaymentData = [];

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'initializePayment' => function (InitializePaymentRequestTransfer $initializePaymentRequestTransfer) use ($initializePaymentResponseTransfer, &$forwardedAdditionalPaymentData) {
                $forwardedAdditionalPaymentData = $initializePaymentRequestTransfer->getOrderData()->getPayment()->getAdditionalPaymentData();

                // Ensure that the AppConfig is always passed to the platform plugin.
                $this->assertInstanceOf(AppConfigTransfer::class, $initializePaymentRequestTransfer->getAppConfig());

                return $initializePaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $initializePaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $response = $this->tester->sendPost($this->tester->buildPaymentUrl(), [RequestOptions::FORM_PARAMS => $initializePaymentRequestTransfer->toArray()]);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);
        $this->tester->assertSame($forwardedAdditionalPaymentData, $additionalPaymentData);
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

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'initializePayment' => function (InitializePaymentRequestTransfer $initializePaymentRequestTransfer) use ($initializePaymentResponseTransfer) {
                // Ensure that the AppConfig is always passed to the platform plugin.
                $this->assertInstanceOf(AppConfigTransfer::class, $initializePaymentRequestTransfer->getAppConfig());

                return $initializePaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $initializePaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $response = $this->tester->sendPost($this->tester->buildPaymentUrl(), [RequestOptions::FORM_PARAMS => $initializePaymentRequestTransfer->toArray()]);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);
        $this->tester->seeResponseIsJson();
        $this->tester->seeResponseJsonContainsPayment($response);

        $this->tester->assertPaymentWithTransactionIdExists($transactionId);
        $this->tester->assertSamePaymentQuoteAndRequestQuote($transactionId, $initializePaymentRequestTransfer->getOrderData());
    }

    public function testInitializePaymentPostRequestReturnsHttpResponseCode400WhenAnExceptionOccurs(): void
    {
        // Arrange
        $initializePaymentRequestTransfer = $this->tester->haveInitializePaymentRequestTransfer();
        $url = $this->tester->buildPaymentUrl();

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'initializePayment' => static function (): never {
                throw new Exception();
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $initializePaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $this->tester->sendPost($url, [RequestOptions::FORM_PARAMS => $initializePaymentRequestTransfer->toArray()]);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
    }

    public function testInitializePaymentPostRequestReturnsHttpResponseCode422WhenTheValidationInThePluginImplementationFails(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();

        $initializePaymentRequestTransfer = $this->tester->haveInitializePaymentRequestTransfer();
        $this->tester->haveAppConfigForTenant($initializePaymentRequestTransfer->getTenantIdentifier());

        $url = $this->tester->buildPaymentUrl();

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'initializePayment' => static function (): InitializePaymentResponseTransfer {
                $initializePaymentResponseTransfer = new InitializePaymentResponseTransfer();
                $initializePaymentResponseTransfer
                    ->setIsSuccessful(false)
                    ->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
                    ->setMessage('Validation failed in the platform plugin.');

                return $initializePaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $initializePaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $this->tester->sendPost($url, [RequestOptions::FORM_PARAMS => $initializePaymentRequestTransfer->toArray()]);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testInitializePaymentPostRequestReturnsHttpResponseCode400WhenPlatformPaymentInitializationFailed(): void
    {
        // Arrange
        $initializePaymentRequestTransfer = $this->tester->haveInitializePaymentRequestTransfer();
        $this->tester->haveAppConfigForTenant($initializePaymentRequestTransfer->getTenantIdentifier());

        $initializePaymentResponseTransfer = new InitializePaymentResponseTransfer();
        $initializePaymentResponseTransfer
            ->setIsSuccessful(false);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'initializePayment' => $initializePaymentResponseTransfer,
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $initializePaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $this->tester->sendPost($this->tester->buildPaymentUrl(), [RequestOptions::FORM_PARAMS => $initializePaymentRequestTransfer->toArray()]);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
    }

    public function testInitializePaymentPostRequestReturnsHttpResponseWithErrorMessageWhenAnExceptionOccurs(): void
    {
        // Arrange
        $initializePaymentRequestTransfer = $this->tester->haveInitializePaymentRequestTransfer();
        $this->tester->haveAppConfigForTenant($initializePaymentRequestTransfer->getTenantIdentifier());
        $url = $this->tester->buildPaymentUrl();

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'initializePayment' => static function (): never {
                throw new Exception('An Error occurred in the platform plugin.');
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $initializePaymentRequestTransfer->getTenantIdentifier());

        $response = $this->tester->sendPost($url, [RequestOptions::FORM_PARAMS => $initializePaymentRequestTransfer->toArray()]);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
        $this->tester->seeResponseContainsErrorMessage('An Error occurred in the platform plugin.');
    }
}
