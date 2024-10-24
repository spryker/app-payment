<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Zed\AppPayment\Business;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\PaymentCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\PaymentMethodTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Shared\Kernel\Transfer\Exception\NullValueException;
use Spryker\Zed\AppPayment\Business\Exception\PaymentMethodNotFoundException;
use SprykerTest\Zed\AppPayment\AppPaymentBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group AppPayment
 * @group Business
 * @group Facade
 * @group PaymentFacadeTest
 * Add your own group annotations below this line
 */
class PaymentFacadeTest extends Unit
{
    protected AppPaymentBusinessTester $tester;

    /**
     * @return void
     */
    public function testDeletePaymentCollectionRemovesAllTheTenantPaymentsWithTenantIdentifierSpecifiedInPaymentCollectionDeleteCriteria(): void
    {
        // Arrange
        $tenantIdentifierToDelete = Uuid::uuid4()->toString();
        $tenantIdentifierToCheck = Uuid::uuid4()->toString();

        $this->tester->havePayment([
            PaymentTransfer::TENANT_IDENTIFIER => $tenantIdentifierToDelete,
        ]);
        $this->tester->havePayment([
            PaymentTransfer::TENANT_IDENTIFIER => $tenantIdentifierToCheck,
        ]);

        $paymentCollectionDeleteCriteriaTransfer = (new PaymentCollectionDeleteCriteriaTransfer())
            ->setTenantIdentifier($tenantIdentifierToDelete);

        // Act
        $this->tester->getFacade()
            ->deletePaymentCollection($paymentCollectionDeleteCriteriaTransfer);

        // Assert
        $this->tester->dontSeePaymentByTenantIdentifier($tenantIdentifierToDelete);
        $this->tester->seePaymentByTenantIdentifier($tenantIdentifierToCheck);
    }

    /**
     * @return void
     */
    public function testDeletePaymentCollectionThrowsAnExceptionWhenPaymentCollectionDeleteCriteriaDoesNotContainAtLeastOneTenantIdentifier(): void
    {
        // Arrange
        $paymentCollectionDeleteCriteriaTransfer = (new PaymentCollectionDeleteCriteriaTransfer());

        // Assert
        $this->expectException(NullValueException::class);

        // Act
        $this->tester->getFacade()
            ->deletePaymentCollection($paymentCollectionDeleteCriteriaTransfer);
    }

    /**
     * @return void
     */
    public function testGetPaymentMethodByTenantIdentifierAndPaymentMethodKeyReturnsPaymentMethodTransfer(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $paymentMethodKey = 'test-payment-method-key';

        $this->tester->havePaymentMethodPersisted([
            PaymentMethodTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            PaymentMethodTransfer::PAYMENT_METHOD_KEY => $paymentMethodKey,
        ]);

        // Act
        $paymentMethodTransfer = $this->tester->getFacade()->getPaymentMethodByTenantIdentifierAndPaymentMethodKey($tenantIdentifier, $paymentMethodKey);

        // Assert
        $this->assertInstanceOf(PaymentMethodTransfer::class, $paymentMethodTransfer);
    }

    /**
     * @return void
     */
    public function testGetPaymentMethodByTenantIdentifierAndPaymentMethodKeyThrowsExceptionWhenPaymentMethodNotFound(): void
    {
        // Arrange
        $tenantIdentifier = Uuid::uuid4()->toString();
        $paymentMethodKey = 'test-payment-method-key';

        // Expext
        $this->expectException(PaymentMethodNotFoundException::class);
        $this->expectExceptionMessage(sprintf('Payment method "%s" not found for Tenant "%s"', $paymentMethodKey, $tenantIdentifier));

        // Act
        $this->tester->getFacade()->getPaymentMethodByTenantIdentifierAndPaymentMethodKey($tenantIdentifier, $paymentMethodKey);
    }
}
