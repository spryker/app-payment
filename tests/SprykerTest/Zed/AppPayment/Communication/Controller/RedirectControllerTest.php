<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Zed\AppPayment\Communication\Controller;

use Codeception\Stub;
use Codeception\Test\Unit;
use Exception;
use Generated\Shared\Transfer\PaymentStatusRequestTransfer;
use Generated\Shared\Transfer\PaymentStatusResponseTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;
use SprykerTest\Shared\Testify\Helper\DependencyHelperTrait;
use SprykerTest\Zed\AppPayment\AppPaymentCommunicationTester;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group AppPayment
 * @group Communication
 * @group Controller
 * @group RedirectControllerTest
 * Add your own group annotations below this line
 */
class RedirectControllerTest extends Unit
{
    use DependencyHelperTrait;

    protected AppPaymentCommunicationTester $tester;

    public function testGivenNoTransactionIdWhenICallTheContollerThenIGetA400BadRequestResponse(): void
    {
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'getPaymentStatus' => function (PaymentStatusRequestTransfer $paymentStatusRequestTransfer) use ($transactionId): PaymentStatusResponseTransfer {
                // Ensure that required data is passed to the PaymentPlatformPlugin
                $this->assertNotNull($paymentStatusRequestTransfer->getAppConfig());
                $this->assertSame($paymentStatusRequestTransfer->getTransactionId(), $transactionId);

                $paymentStatusResponseTransfer = new PaymentStatusResponseTransfer();
                $paymentStatusResponseTransfer
                    ->setIsSuccessful(true);

                return $paymentStatusResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        $this->tester->amOnPage(sprintf('/app-payment/redirect'));
        $this->tester->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
    }

    public function testGivenPaymentWasSuccessfulGetRedirectUrlReturnsSuccessPageUrl(): void
    {
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'getPaymentStatus' => function (PaymentStatusRequestTransfer $paymentStatusRequestTransfer) use ($transactionId): PaymentStatusResponseTransfer {
                // Ensure that required data is passed to the PaymentPlatformPlugin
                $this->assertNotNull($paymentStatusRequestTransfer->getAppConfig());
                $this->assertSame($paymentStatusRequestTransfer->getTransactionId(), $transactionId);

                $paymentStatusResponseTransfer = new PaymentStatusResponseTransfer();
                $paymentStatusResponseTransfer
                    ->setIsSuccessful(true);

                return $paymentStatusResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        $this->tester->amOnPage(sprintf('/app-payment/redirect?transactionId=%s', $transactionId));
        $this->tester->seeRedirectUrlEquals($paymentTransfer->getRedirectSuccessUrl());
    }

    public function testGivenPaymentWasNotSuccessfulGetRedirectUrlReturnsCancelPageUrl(): void
    {
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'getPaymentStatus' => function (PaymentStatusRequestTransfer $paymentStatusRequestTransfer) use ($transactionId): PaymentStatusResponseTransfer {
                // Ensure that required data is passed to the PaymentPlatformPlugin
                $this->assertNotNull($paymentStatusRequestTransfer->getAppConfig());
                $this->assertNotNull($paymentStatusRequestTransfer->getPayment());
                $this->assertSame($paymentStatusRequestTransfer->getTransactionId(), $transactionId);

                $paymentStatusResponseTransfer = new PaymentStatusResponseTransfer();
                $paymentStatusResponseTransfer
                    ->setIsSuccessful(false);

                return $paymentStatusResponseTransfer;
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        $this->tester->amOnPage(sprintf('/app-payment/redirect?transactionId=%s', $transactionId));
        $this->tester->seeRedirectUrlEquals(sprintf('/app-payment?transactionId=%s&tenantIdentifier=%s', $transactionId, $tenantIdentifier));
    }

    public function testWhenPaymentPagePluginThrowsExceptionGetRedirectUrlReturnsCancelPageUrl(): void
    {
        $tenantIdentifier = Uuid::uuid4()->toString();
        $transactionId = Uuid::uuid4()->toString();

        $this->tester->haveAppConfigForTenant($tenantIdentifier);
        $paymentTransfer = $this->tester->havePaymentForTransactionId($transactionId, $tenantIdentifier);

        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'getPaymentStatus' => function (PaymentStatusRequestTransfer $paymentStatusRequestTransfer) use ($transactionId): PaymentStatusResponseTransfer {
                // Ensure that required data is passed to the PaymentPlatformPlugin
                throw new Exception('Something went wrong');
            },
        ]);

        $this->getDependencyHelper()->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);

        $this->tester->amOnPage(sprintf('/app-payment/redirect?transactionId=%s', $transactionId));
        $this->tester->seeRedirectUrlEquals($paymentTransfer->getRedirectCancelUrl());
    }
}
