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
use Generated\Shared\Transfer\CancelPreOrderPaymentRequestTransfer;
use Generated\Shared\Transfer\CancelPreOrderPaymentResponseTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\WebhookRequestTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Glue\AppPaymentBackendApi\Mapper\Payment\GlueRequestPaymentMapper;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformCancelPreOrderPluginInterface;
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
 * @group CancelPreOrderPaymentApiTest
 * Add your own group annotations below this line
 */
class CancelPreOrderPaymentApiTest extends Unit
{
    use DependencyHelperTrait;

    protected AppPaymentBackendApiTester $tester;

    public function testCancelPreOrderPaymentPostRequestReturnsHttpResponseCode200WhenThePaymentCanBeCanceled(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();

        $cancelPreOrderPaymentRequestTransfer = $this->tester->haveCancelPreOrderPaymentRequestTransfer([
            CancelPreOrderPaymentRequestTransfer::TRANSACTION_ID => $transactionId,
        ]);

        $this->tester->haveAppConfigForTenant($cancelPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->havePaymentForTransactionId($cancelPreOrderPaymentRequestTransfer->getTransactionId(), $cancelPreOrderPaymentRequestTransfer->getTenantIdentifier());

        $cancelPreOrderPaymentResponseTransfer = new CancelPreOrderPaymentResponseTransfer();
        $cancelPreOrderPaymentResponseTransfer
            ->setIsSuccessful(true);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformCancelPreOrderPluginInterface::class, [
            'cancelPreOrderPayment' => function (CancelPreOrderPaymentRequestTransfer $cancelPreOrderPaymentRequestTransfer) use ($cancelPreOrderPaymentResponseTransfer) {
                // Ensure that the AppConfig is always passed to the platform plugin.
                $this->assertInstanceOf(AppConfigTransfer::class, $cancelPreOrderPaymentRequestTransfer->getAppConfig());
                $this->assertInstanceOf(PaymentTransfer::class, $cancelPreOrderPaymentRequestTransfer->getPayment());

                return $cancelPreOrderPaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $cancelPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $this->tester->sendPost($this->tester->buildCancelPreOrderPaymentUrl(), $cancelPreOrderPaymentRequestTransfer->toArray());

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);

        // The canceled pre-order payment should no longer exists in the database.
        $this->tester->assertPaymentWithTransactionIdDoesNotExists($transactionId);
    }

    public function testCancelPreOrderPaymentPostRequestReturnsHttpResponseCode200WhenThePaymentPlatformHasNotImplementedTheAppCancelPreOrderPaymentPlatformPlugin(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();

        $cancelPreOrderPaymentRequestTransfer = $this->tester->haveCancelPreOrderPaymentRequestTransfer([
            CancelPreOrderPaymentRequestTransfer::TRANSACTION_ID => $transactionId,
        ]);

        $this->tester->haveAppConfigForTenant($cancelPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->havePaymentForTransactionId($cancelPreOrderPaymentRequestTransfer->getTransactionId(), $cancelPreOrderPaymentRequestTransfer->getTenantIdentifier());

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $cancelPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $this->tester->sendPost($this->tester->buildCancelPreOrderPaymentUrl(), $cancelPreOrderPaymentRequestTransfer->toArray());

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);
    }

    public function testCancelPreOrderPaymentPostRequestReturnsHttpResponseCode200AndDeletesUnprocessedWebhooks(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();

        $webhookRequestTransfer = new WebhookRequestTransfer();
        $webhookRequestTransfer->setIdentifier($transactionId);

        $this->tester->haveWebhookRequestPersisted($webhookRequestTransfer);
        $this->tester->assertWebhookIsPersisted($transactionId);

        $cancelPreOrderPaymentRequestTransfer = $this->tester->haveCancelPreOrderPaymentRequestTransfer([
            CancelPreOrderPaymentRequestTransfer::TRANSACTION_ID => $transactionId,
        ]);

        $this->tester->haveAppConfigForTenant($cancelPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->havePaymentForTransactionId($cancelPreOrderPaymentRequestTransfer->getTransactionId(), $cancelPreOrderPaymentRequestTransfer->getTenantIdentifier());

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $cancelPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $this->tester->sendPost($this->tester->buildCancelPreOrderPaymentUrl(), $cancelPreOrderPaymentRequestTransfer->toArray());

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);

        $this->tester->assertWebhookIsNotPersisted($transactionId);
    }

    public function testCancelPreOrderPaymentPostRequestReturnsHttpResponseCode400WhenThePaymentCanNotBeCanceled(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();

        $cancelPreOrderPaymentRequestTransfer = $this->tester->haveCancelPreOrderPaymentRequestTransfer([
            CancelPreOrderPaymentRequestTransfer::TRANSACTION_ID => $transactionId,
        ]);

        $this->tester->haveAppConfigForTenant($cancelPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->havePaymentForTransactionId($cancelPreOrderPaymentRequestTransfer->getTransactionId(), $cancelPreOrderPaymentRequestTransfer->getTenantIdentifier());

        $cancelPreOrderPaymentResponseTransfer = new CancelPreOrderPaymentResponseTransfer();
        $cancelPreOrderPaymentResponseTransfer
            ->setIsSuccessful(false)
            ->setMessage('Payment cancellation failed');

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformCancelPreOrderPluginInterface::class, [
            'cancelPreOrderPayment' => function (CancelPreOrderPaymentRequestTransfer $cancelPreOrderPaymentRequestTransfer) use ($cancelPreOrderPaymentResponseTransfer) {
                // Ensure that the AppConfig is always passed to the platform plugin.
                $this->assertInstanceOf(AppConfigTransfer::class, $cancelPreOrderPaymentRequestTransfer->getAppConfig());
                $this->assertInstanceOf(PaymentTransfer::class, $cancelPreOrderPaymentRequestTransfer->getPayment());

                return $cancelPreOrderPaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $cancelPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $this->tester->sendPost($this->tester->buildCancelPreOrderPaymentUrl(), $cancelPreOrderPaymentRequestTransfer->toArray());

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
    }

    public function testCancelPreOrderPaymentPostRequestReturnsHttpResponseCode422WhenTheValidationOfTheRequestFailsinThePluginImplementation(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();

        $cancelPreOrderPaymentRequestTransfer = $this->tester->haveCancelPreOrderPaymentRequestTransfer([
            CancelPreOrderPaymentRequestTransfer::TRANSACTION_ID => $transactionId,
        ]);

        $this->tester->haveAppConfigForTenant($cancelPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->havePaymentForTransactionId($cancelPreOrderPaymentRequestTransfer->getTransactionId(), $cancelPreOrderPaymentRequestTransfer->getTenantIdentifier());

        $cancelPreOrderPaymentResponseTransfer = new CancelPreOrderPaymentResponseTransfer();
        $cancelPreOrderPaymentResponseTransfer
            ->setIsSuccessful(false)
            ->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->setMessage('Payment cancellation failed');

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformCancelPreOrderPluginInterface::class, [
            'cancelPreOrderPayment' => function (CancelPreOrderPaymentRequestTransfer $cancelPreOrderPaymentRequestTransfer) use ($cancelPreOrderPaymentResponseTransfer) {
                // Ensure that the AppConfig is always passed to the platform plugin.
                $this->assertInstanceOf(AppConfigTransfer::class, $cancelPreOrderPaymentRequestTransfer->getAppConfig());
                $this->assertInstanceOf(PaymentTransfer::class, $cancelPreOrderPaymentRequestTransfer->getPayment());

                return $cancelPreOrderPaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $cancelPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $this->tester->sendPost($this->tester->buildCancelPreOrderPaymentUrl(), $cancelPreOrderPaymentRequestTransfer->toArray());

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCancelPreOrderPaymentPostRequestReturnsHttpResponseCode400WhenThePaymentPlatformImplemtationThrowsAnException(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();

        $cancelPreOrderPaymentRequestTransfer = $this->tester->haveCancelPreOrderPaymentRequestTransfer([
            CancelPreOrderPaymentRequestTransfer::TRANSACTION_ID => $transactionId,
        ]);

        $this->tester->haveAppConfigForTenant($cancelPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->havePaymentForTransactionId($cancelPreOrderPaymentRequestTransfer->getTransactionId(), $cancelPreOrderPaymentRequestTransfer->getTenantIdentifier());

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformCancelPreOrderPluginInterface::class, [
            'cancelPreOrderPayment' => function (CancelPreOrderPaymentRequestTransfer $cancelPreOrderPaymentRequestTransfer): void {
                // Ensure that the AppConfig is always passed to the platform plugin.
                throw new Exception('Cancel pre-order payment failed');
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $cancelPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $this->tester->sendPost($this->tester->buildCancelPreOrderPaymentUrl(), $cancelPreOrderPaymentRequestTransfer->toArray());

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);

        // The failed payment should still exist for further investigations.
        $this->tester->assertPaymentWithTransactionIdExists($transactionId);
    }
}
