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
use Generated\Shared\Transfer\OrderItemTransfer;
use Generated\Shared\Transfer\PaymentsTransmissionsRequestTransfer;
use Generated\Shared\Transfer\PaymentsTransmissionsResponseTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\PaymentTransmissionTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Glue\AppPaymentBackendApi\Mapper\Payment\GlueRequestPaymentMapper;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Business\Message\MessageBuilder;
use Spryker\Zed\AppPayment\Dependency\Plugin\PaymentsTransmissionsRequestExtenderPluginInterface;
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
 * @group PaymentsTransfersApiTest
 * Add your own group annotations below this line
 */
class PaymentsTransfersApiTest extends Unit
{
    use DependencyHelperTrait;

    protected AppPaymentBackendApiTester $tester;

    public function testGivenPaymentsTransfersPostRequestForTransferWhenRequestIsValidThenAHttpResponseCode200IsReturnedAndTransfersArePersisted(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $transferId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();
        $orderReference = Uuid::uuid4()->toString();
        $merchantReference = Uuid::uuid4()->toString();

        $this->tester->haveTransferDefaults($tenantIdentifier, $merchantReference, $transactionId, $orderReference);

        $paymentsTransmissionsResponseTransfer = new PaymentsTransmissionsResponseTransfer();

        $platformPluginMock = Stub::makeEmpty(PlatformPluginInterface::class, [
            'transferPayments' => function (PaymentsTransmissionsRequestTransfer $paymentsTransmissionsRequestTransfer) use ($paymentsTransmissionsResponseTransfer, $transferId) {
                $paymentsTransmissionsResponseTransfer->setIsSuccessful(true);

                // Ensure that the AppConfig is always passed to the platform plugin.
                $this->assertInstanceOf(AppConfigTransfer::class, $paymentsTransmissionsRequestTransfer->getAppConfig());

                // Add transferId to each PaymentTransmissionTransfer.
                foreach ($paymentsTransmissionsRequestTransfer->getPaymentsTransmissions() as $paymentsTransmission) {
                    $paymentsTransmission->setTransferId($transferId)->setIsSuccessful(true);
                    $paymentsTransmissionsResponseTransfer->addPaymentTransmission($paymentsTransmission);
                }

                return $paymentsTransmissionsResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);
        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGINS_PAYMENTS_TRANSMISSIONS_REQUEST_EXPANDER, [
            new class implements PaymentsTransmissionsRequestExtenderPluginInterface {
                public function extendPaymentsTransmissionsRequest(
                    PaymentsTransmissionsRequestTransfer $paymentsTransmissionsRequestTransfer
                ): PaymentsTransmissionsRequestTransfer {
                    return $paymentsTransmissionsRequestTransfer;
                }
            },
        ]);

        $orderItems = [
            $this->tester->haveOrderItem([
                OrderItemTransfer::MERCHANT_REFERENCE => $merchantReference,
                OrderItemTransfer::ORDER_REFERENCE => $orderReference,
                OrderItemTransfer::ITEM_REFERENCE => Uuid::uuid4()->toString(),
                OrderItemTransfer::AMOUNT => 90,
            ]),
            $this->tester->haveOrderItem([
                OrderItemTransfer::MERCHANT_REFERENCE => $merchantReference,
                OrderItemTransfer::ORDER_REFERENCE => $orderReference,
                OrderItemTransfer::ITEM_REFERENCE => Uuid::uuid4()->toString(),
                OrderItemTransfer::AMOUNT => 90,
            ]),
        ];

        $requestOrderItems = [];

        foreach ($orderItems as $orderItemTransfer) {
            $requestOrderItems[] = $orderItemTransfer->toArray();
        }

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $tenantIdentifier);
        $this->tester->addHeader('content-type', 'application/json');

        $response = $this->tester->sendPost(
            $this->tester->buildPaymentsTransfersUrl(),
            ['orderItems' => $requestOrderItems],
        );

        $response = json_decode($response->getContent(), true);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);

        $expectedPaymentTransmissionTransfer = new PaymentTransmissionTransfer();
        $expectedPaymentTransmissionTransfer
            ->setTenantIdentifier($tenantIdentifier)
            ->setMerchantReference($merchantReference)
            ->setTransactionId($transactionId)
            ->setTransferId($transferId)
            ->setOrderReference($orderReference)
            ->setItemReferences([$orderItems[0]->getItemReferenceOrFail(), $orderItems[1]->getItemReferenceOrFail()])
            ->setAmount('180');

        $this->assertCount(1, $paymentsTransmissionsResponseTransfer->getPaymentsTransmissions());
        $paymentTransmissionTransfer = $paymentsTransmissionsResponseTransfer->getPaymentsTransmissions()[0];

        $this->tester->assertPaymentTransferEqualsPaymentTransmission($paymentTransmissionTransfer->getTransferIdOrFail(), $expectedPaymentTransmissionTransfer);

        $this->assertSame($expectedPaymentTransmissionTransfer->getTransferId(), $response['transfers'][0]['transferId']);
    }

    public function testGivenPaymentsTransfersPostRequestForReverseTransferWhenRequestIsValidThenAHttpResponseCode200IsReturnedAndTransfersArePersisted(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $transferId = Uuid::uuid4()->toString();
        $reverseTransferId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();
        $orderReference = Uuid::uuid4()->toString();
        $merchantReference = Uuid::uuid4()->toString();

        $this->tester->haveTransferDefaults($tenantIdentifier, $merchantReference, $transactionId, $orderReference);

        $this->tester->havePaymentTransmissionPersisted([PaymentTransmissionTransfer::TRANSFER_ID => $transferId]);

        $paymentsTransmissionsResponseTransfer = new PaymentsTransmissionsResponseTransfer();

        $platformPluginMock = Stub::makeEmpty(PlatformPluginInterface::class, [
            'transferPayments' => function (PaymentsTransmissionsRequestTransfer $paymentsTransmissionsRequestTransfer) use ($paymentsTransmissionsResponseTransfer, $reverseTransferId) {
                $paymentsTransmissionsResponseTransfer->setIsSuccessful(true);

                // Ensure that the AppConfig is always passed to the platform plugin.
                $this->assertInstanceOf(AppConfigTransfer::class, $paymentsTransmissionsRequestTransfer->getAppConfig());

                // Add transferId to each PaymentTransmissionTransfer.
                foreach ($paymentsTransmissionsRequestTransfer->getPaymentsTransmissions() as $paymentsTransmission) {
                    $paymentsTransmission->setTransferId($reverseTransferId)->setIsSuccessful(true);
                    $paymentsTransmissionsResponseTransfer->addPaymentTransmission($paymentsTransmission);
                }

                return $paymentsTransmissionsResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        $orderItems = [
            $this->tester->haveOrderItem([
                OrderItemTransfer::MERCHANT_REFERENCE => $merchantReference,
                OrderItemTransfer::ORDER_REFERENCE => $orderReference,
                OrderItemTransfer::ITEM_REFERENCE => Uuid::uuid4()->toString(),
                OrderItemTransfer::AMOUNT => -90,
            ]),
            $this->tester->haveOrderItem([
                OrderItemTransfer::MERCHANT_REFERENCE => $merchantReference,
                OrderItemTransfer::ORDER_REFERENCE => $orderReference,
                OrderItemTransfer::ITEM_REFERENCE => $transferId,
                OrderItemTransfer::AMOUNT => -90,
            ]),
        ];

        $requestOrderItems = [];

        foreach ($orderItems as $orderItemTransfer) {
            $requestOrderItems[] = $orderItemTransfer->toArray();
        }

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $tenantIdentifier);
        $this->tester->addHeader('content-type', 'application/json');

        $response = $this->tester->sendPost(
            $this->tester->buildPaymentsTransfersUrl(),
            ['orderItems' => $requestOrderItems, 'transferId' => $transferId],
        );

        $response = json_decode($response->getContent(), true);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);

        $expectedPaymentTransmissionTransfer = new PaymentTransmissionTransfer();
        $expectedPaymentTransmissionTransfer
            ->setTenantIdentifier($tenantIdentifier)
            ->setMerchantReference($merchantReference)
            ->setTransactionId($transactionId)
            ->setTransferId($reverseTransferId)
            ->setOrderReference($orderReference)
            ->setItemReferences([$orderItems[0]->getItemReferenceOrFail(), $orderItems[1]->getItemReferenceOrFail()])
            ->setAmount('-180');

        $this->assertCount(1, $paymentsTransmissionsResponseTransfer->getPaymentsTransmissions());
        $paymentTransmissionTransfer = $paymentsTransmissionsResponseTransfer->getPaymentsTransmissions()[0];

        $this->tester->assertPaymentTransferEqualsPaymentTransmission($paymentTransmissionTransfer->getTransferIdOrFail(), $expectedPaymentTransmissionTransfer);

        $this->assertSame($expectedPaymentTransmissionTransfer->getTransferId(), $response['transfers'][0]['transferId']);
    }

    public function testGivenPaymentsTransfersPostRequestForReverseTransferWithTwoItemsEachWasTransferredSeparatelyWhenRequestIsValidThenAHttpResponseCode200IsReturnedAndTwoReverseTransfersArePersisted(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $transferId1 = Uuid::uuid4()->toString();
        $transferId2 = Uuid::uuid4()->toString();
        $reverseTransferId1 = Uuid::uuid4()->toString();
        $reverseTransferId2 = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();
        $orderReference = Uuid::uuid4()->toString();
        $merchantReference = Uuid::uuid4()->toString();

        $this->tester->haveTransferDefaults($tenantIdentifier, $merchantReference, $transactionId, $orderReference);

        $this->tester->havePaymentTransmissionPersisted([PaymentTransmissionTransfer::TRANSFER_ID => $transferId1]);
        $this->tester->havePaymentTransmissionPersisted([PaymentTransmissionTransfer::TRANSFER_ID => $transferId2]);

        $paymentsTransmissionsResponseTransfer = new PaymentsTransmissionsResponseTransfer();

        $platformPluginMock = Stub::makeEmpty(PlatformPluginInterface::class, [
            'transferPayments' => function (PaymentsTransmissionsRequestTransfer $paymentsTransmissionsRequestTransfer) use ($paymentsTransmissionsResponseTransfer, $reverseTransferId1, $reverseTransferId2) {
                $paymentsTransmissionsResponseTransfer->setIsSuccessful(true);

                // Ensure that the AppConfig is always passed to the platform plugin.
                $this->assertInstanceOf(AppConfigTransfer::class, $paymentsTransmissionsRequestTransfer->getAppConfig());

                // Add transferId to each PaymentTransmissionTransfer.
                $paymentTransmissionTransfers = $paymentsTransmissionsRequestTransfer->getPaymentsTransmissions();
                $paymentTransmissionTransfers[0]->setTransferId($reverseTransferId1)->setIsSuccessful(true);
                $paymentTransmissionTransfers[1]->setTransferId($reverseTransferId2)->setIsSuccessful(true);

                $paymentsTransmissionsResponseTransfer->setPaymentsTransmissions($paymentTransmissionTransfers);

                return $paymentsTransmissionsResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        $requestOrderItems = $this->tester->haveOrderItemsForReverseTransfer($orderReference, $merchantReference, $merchantReference, $transferId1, $transferId2);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $tenantIdentifier);
        $this->tester->addHeader('content-type', 'application/json');

        $response = $this->tester->sendPost(
            $this->tester->buildPaymentsTransfersUrl(),
            ['orderItems' => $requestOrderItems],
        );

        $response = json_decode($response->getContent(), true);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);

        $this->assertSame($reverseTransferId1, $response['transfers'][0]['transferId']);
        $this->assertSame($reverseTransferId2, $response['transfers'][1]['transferId']);
    }

    public function testGivenPaymentsTransfersPostRequestForReverseTransferWhenNoPreviousTransactionExistsThenAHttpResponseCode200IsReturnedWithAFailureResponseMessage(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $transferId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();
        $orderReference = Uuid::uuid4()->toString();
        $merchantReference = Uuid::uuid4()->toString();

        $this->tester->haveTransferDefaults($tenantIdentifier, $merchantReference, $transactionId, $orderReference);
        $requestOrderItems = $this->tester->haveOrderItemsForReverseTransfer($orderReference, $merchantReference, $merchantReference, $transferId, $transferId);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $tenantIdentifier);
        $this->tester->addHeader('content-type', 'application/json');

        $response = $this->tester->sendPost(
            $this->tester->buildPaymentsTransfersUrl(),
            ['orderItems' => $requestOrderItems, 'transferId' => $transferId],
        );

        $response = json_decode($response->getContent(), true);

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);

        $this->assertSame(MessageBuilder::paymentTransferByTransferIdNotFound($transferId), $response['transfers'][0]['failureMessage']);
    }

    public function testWhenThePlatformPluginThrowsAnExceptionWeExpectAFailedResultWithTheExceptionMessageForwardedInTheResponse(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();
        $orderReference = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);

        $this->tester->havePayment([
            PaymentTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            PaymentTransfer::TRANSACTION_ID => $transactionId,
            PaymentTransfer::ORDER_REFERENCE => $orderReference,
        ]);

        $platformPluginMock = Stub::makeEmpty(PlatformPluginInterface::class, [
            'transferPayments' => function (): void {
                throw new Exception('There was an error in the PlatformPlugin implementation');
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        $requestOrderItems = $this->tester->haveOrderItemsForTransfer($orderReference);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $tenantIdentifier);
        $this->tester->sendPost(
            $this->tester->buildPaymentsTransfersUrl(),
            ['orderItems' => $requestOrderItems],
        );

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
        $this->tester->seeResponseContainsErrorMessage('There was an error in the PlatformPlugin implementation');
    }

    public function testWhenThePlatformPluginReturnsAFailedResponseWithTheExceptionMessageForwardedInTheResponse(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();
        $orderReference = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);

        $this->tester->havePayment([
            PaymentTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            PaymentTransfer::TRANSACTION_ID => $transactionId,
            PaymentTransfer::ORDER_REFERENCE => $orderReference,
        ]);

        $platformPluginMock = Stub::makeEmpty(PlatformPluginInterface::class, [
            'transferPayments' => function () {
                return (new PaymentsTransmissionsResponseTransfer())
                    ->setIsSuccessful(false)
                    ->setMessage('There was an error in the PlatformPlugin implementation');
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        $requestOrderItems = $this->tester->haveOrderItemsForTransfer($orderReference);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $tenantIdentifier);
        $this->tester->sendPost(
            $this->tester->buildPaymentsTransfersUrl(),
            ['orderItems' => $requestOrderItems],
        );

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
        $this->tester->seeResponseContainsErrorMessage('There was an error in the PlatformPlugin implementation');
    }

    public function testWhenThePlatformPluginReturnsAFailedTransmissionInTheCollectionThenTheTransmissionIsNotPersistedAndAResponseWithTheFailureMessageIsSent(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();
        $orderReference = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);

        $this->tester->havePayment([
            PaymentTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            PaymentTransfer::TRANSACTION_ID => $transactionId,
            PaymentTransfer::ORDER_REFERENCE => $orderReference,
        ]);

        $platformPluginMock = Stub::makeEmpty(PlatformPluginInterface::class, [
            'transferPayments' => function () {
                $paymentTransmissionResponseTransfer = (new PaymentsTransmissionsResponseTransfer())
                    ->setIsSuccessful(true);

                $paymentTransmissionTransfer = new PaymentTransmissionTransfer();
                $paymentTransmissionTransfer->setIsSuccessful(false);
                $paymentTransmissionTransfer->setMessage('Transmission failed');

                $paymentTransmissionResponseTransfer->addPaymentTransmission($paymentTransmissionTransfer);

                return $paymentTransmissionResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        $requestOrderItems = $this->tester->haveOrderItemsForTransfer($orderReference);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $tenantIdentifier);
        $this->tester->addHeader('content-type', 'application/json');

        $response = $this->tester->sendPost(
            $this->tester->buildPaymentsTransfersUrl(),
            ['orderItems' => $requestOrderItems],
        );

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_OK);

        $transfers = json_decode($response->getContent(), true)['transfers'];
        $this->assertSame('Transmission failed', $transfers[0]['failureMessage']);
    }

    public function testWhenThereIsNoPaymentFoundForTheTenantTheAFailedResponseIsReturnedWithTheExceptionMessageForwardedInTheResponse(): void
    {
        // Arrange
        $transactionId = Uuid::uuid4()->toString();
        $tenantIdentifier = Uuid::uuid4()->toString();
        $orderReference = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);

        $this->tester->havePayment([
            PaymentTransfer::TENANT_IDENTIFIER => 'not known tenant identifier',
            PaymentTransfer::TRANSACTION_ID => $transactionId,
            PaymentTransfer::ORDER_REFERENCE => $orderReference,
        ]);

        $requestOrderItems = $this->tester->haveOrderItemsForTransfer($orderReference);

        // Act
        $this->tester->addHeader(GlueRequestPaymentMapper::HEADER_TENANT_IDENTIFIER, $tenantIdentifier);
        $this->tester->sendPost(
            $this->tester->buildPaymentsTransfersUrl(),
            ['orderItems' => $requestOrderItems],
        );

        // Assert
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
        $this->tester->seeResponseContainsErrorMessage(MessageBuilder::paymentByTenantIdentifierAndOrderReferenceNotFound($tenantIdentifier, $orderReference));
    }
}
