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
use Generated\Shared\Transfer\ConfirmPreOrderPaymentRequestTransfer;
use Generated\Shared\Transfer\ConfirmPreOrderPaymentResponseTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Glue\AppPaymentBackendApi\Mapper\Payment\GlueRequestPaymentMapper;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPreOrderPaymentPlatformPluginInterface;
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
 * @group ConfirmPreOrderPaymentApiTest
 * Add your own group annotations below this line
 */
class ConfirmPreOrderPaymentApiTest extends Unit
{
    use DependencyHelperTrait;

    protected AppPaymentBackendApiTester $tester;

    public function testConfirmPreOrderPaymentPostRequestReturnsHttpResponseCode200WhenThePaymentCanBeUpdatedWithTheMissingOrderReference(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();

        $confirmPreOrderPaymentRequestTransfer = $this->tester->haveConfirmPreOrderPaymentRequestTransfer([
            ConfirmPreOrderPaymentRequestTransfer::TRANSACTION_ID => $transactionId,
        ]);

        $this->tester->haveAppConfigForTenant($confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->havePaymentForTransactionId($confirmPreOrderPaymentRequestTransfer->getTransactionId(), $confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());

        $confirmPreOrderPaymentResponseTransfer = new ConfirmPreOrderPaymentResponseTransfer();
        $confirmPreOrderPaymentResponseTransfer
            ->setIsSuccessful(true)
            ->setStatus(PaymentStatus::STATUS_CAPTURED);

        $platformPluginMock = Stub::makeEmpty(AppPreOrderPaymentPlatformPluginInterface::class, [
            'confirmPreOrderPayment' => function (ConfirmPreOrderPaymentRequestTransfer $confirmPreOrderPaymentRequestTransfer) use ($confirmPreOrderPaymentResponseTransfer) {
                // Ensure that the AppConfig is always passed to the platform plugin.
                $this->assertInstanceOf(AppConfigTransfer::class, $confirmPreOrderPaymentRequestTransfer->getAppConfig());
                $this->assertInstanceOf(PaymentTransfer::class, $confirmPreOrderPaymentRequestTransfer->getPayment());

                return $confirmPreOrderPaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $this->tester->sendPost($this->tester->buildConfirmPreOrderPaymentUrl(), $confirmPreOrderPaymentRequestTransfer->toArray());

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);
    }

    public function testConfirmPreOrderPaymentPostRequestReturnsHttpResponseCode200WhenThePaymentPlatformHasNotImplementedTheAppPreOrderPaymentPlatformPlugin(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();

        $confirmPreOrderPaymentRequestTransfer = $this->tester->haveConfirmPreOrderPaymentRequestTransfer([
            ConfirmPreOrderPaymentRequestTransfer::TRANSACTION_ID => $transactionId,
        ]);

        $this->tester->haveAppConfigForTenant($confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->havePaymentForTransactionId($confirmPreOrderPaymentRequestTransfer->getTransactionId(), $confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $this->tester->sendPost($this->tester->buildConfirmPreOrderPaymentUrl(), $confirmPreOrderPaymentRequestTransfer->toArray());

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);
    }

    public function testConfirmPreOrderPaymentPostRequestReturnsHttpResponseCode400WhenThePaymentCanNotBeUpdatedWithTheMissingOrderReference(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();

        $confirmPreOrderPaymentRequestTransfer = $this->tester->haveConfirmPreOrderPaymentRequestTransfer([
            ConfirmPreOrderPaymentRequestTransfer::TRANSACTION_ID => $transactionId,
        ]);

        $this->tester->haveAppConfigForTenant($confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->havePaymentForTransactionId($confirmPreOrderPaymentRequestTransfer->getTransactionId(), $confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());

        $confirmPreOrderPaymentResponseTransfer = new ConfirmPreOrderPaymentResponseTransfer();
        $confirmPreOrderPaymentResponseTransfer
            ->setIsSuccessful(false)
            ->setMessage('Payment confirmation failed')
            ->setStatus(PaymentStatus::STATUS_CAPTURED);

        $platformPluginMock = Stub::makeEmpty(AppPreOrderPaymentPlatformPluginInterface::class, [
            'confirmPreOrderPayment' => function (ConfirmPreOrderPaymentRequestTransfer $confirmPreOrderPaymentRequestTransfer) use ($confirmPreOrderPaymentResponseTransfer) {
                // Ensure that the AppConfig is always passed to the platform plugin.
                $this->assertInstanceOf(AppConfigTransfer::class, $confirmPreOrderPaymentRequestTransfer->getAppConfig());
                $this->assertInstanceOf(PaymentTransfer::class, $confirmPreOrderPaymentRequestTransfer->getPayment());

                return $confirmPreOrderPaymentResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $this->tester->sendPost($this->tester->buildConfirmPreOrderPaymentUrl(), $confirmPreOrderPaymentRequestTransfer->toArray());

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
    }

    public function testConfirmPreOrderPaymentPostRequestReturnsHttpResponseCode400WhenThePaymentPlatformImplemtationThrowsAnException(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();

        $confirmPreOrderPaymentRequestTransfer = $this->tester->haveConfirmPreOrderPaymentRequestTransfer([
            ConfirmPreOrderPaymentRequestTransfer::TRANSACTION_ID => $transactionId,
        ]);

        $this->tester->haveAppConfigForTenant($confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->havePaymentForTransactionId($confirmPreOrderPaymentRequestTransfer->getTransactionId(), $confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());

        $platformPluginMock = Stub::makeEmpty(AppPreOrderPaymentPlatformPluginInterface::class, [
            'confirmPreOrderPayment' => function (ConfirmPreOrderPaymentRequestTransfer $confirmPreOrderPaymentRequestTransfer): void {
                // Ensure that the AppConfig is always passed to the platform plugin.
                throw new Exception('Confirm pre-order payment failed');
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $confirmPreOrderPaymentRequestTransfer->getTenantIdentifier());
        $this->tester->addHeader('Content-Type', 'application/json');

        $this->tester->sendPost($this->tester->buildConfirmPreOrderPaymentUrl(), $confirmPreOrderPaymentRequestTransfer->toArray());

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
    }
}
