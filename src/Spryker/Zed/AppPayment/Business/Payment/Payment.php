<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment;

use Generated\Shared\Transfer\CancelPreOrderPaymentRequestTransfer;
use Generated\Shared\Transfer\CancelPreOrderPaymentResponseTransfer;
use Generated\Shared\Transfer\ConfirmPreOrderPaymentRequestTransfer;
use Generated\Shared\Transfer\ConfirmPreOrderPaymentResponseTransfer;
use Generated\Shared\Transfer\CustomerRequestTransfer;
use Generated\Shared\Transfer\CustomerResponseTransfer;
use Generated\Shared\Transfer\InitializePaymentRequestTransfer;
use Generated\Shared\Transfer\InitializePaymentResponseTransfer;
use Generated\Shared\Transfer\PaymentMethodTransfer;
use Generated\Shared\Transfer\PaymentPageRequestTransfer;
use Generated\Shared\Transfer\PaymentPageResponseTransfer;
use Generated\Shared\Transfer\PaymentTransmissionsRequestTransfer;
use Generated\Shared\Transfer\PaymentTransmissionsResponseTransfer;
use Spryker\Zed\AppPayment\Business\Customer\Customer;
use Spryker\Zed\AppPayment\Business\Payment\Initialize\PaymentInitializer;
use Spryker\Zed\AppPayment\Business\Payment\Method\Reader\PaymentMethodReader;
use Spryker\Zed\AppPayment\Business\Payment\Page\PaymentPage;
use Spryker\Zed\AppPayment\Business\Payment\PreOrder\PaymentPreOrder;
use Spryker\Zed\AppPayment\Business\Payment\Transfer\PaymentTransfer;
use Spryker\Zed\AppPayment\Business\Payment\Webhook\WebhookHandler;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;

class Payment
{
    public function __construct(
        protected AppPaymentPlatformPluginInterface $appPaymentPlatformPlugin,
        protected PaymentInitializer $paymentInitializer,
        protected PaymentPreOrder $paymentPreOrder,
        protected PaymentTransfer $paymentTransfer,
        protected PaymentPage $paymentPage,
        protected PaymentMethodReader $paymentMethodReader,
        protected Customer $customer,
        protected WebhookHandler $webhookHandler
    ) {
    }

    public function initializePayment(InitializePaymentRequestTransfer $initializePaymentRequestTransfer): InitializePaymentResponseTransfer
    {
        return $this->paymentInitializer->initializePayment($initializePaymentRequestTransfer);
    }

    public function confirmPreOrderPayment(
        ConfirmPreOrderPaymentRequestTransfer $confirmPreOrderPaymentRequestTransfer
    ): ConfirmPreOrderPaymentResponseTransfer {
        return $this->paymentPreOrder->confirmPreOrderPayment($confirmPreOrderPaymentRequestTransfer);
    }

    public function cancelPreOrderPayment(
        CancelPreOrderPaymentRequestTransfer $cancelPreOrderPaymentRequestTransfer
    ): CancelPreOrderPaymentResponseTransfer {
        return $this->paymentPreOrder->cancelPreOrderPayment($cancelPreOrderPaymentRequestTransfer);
    }

    public function getPaymentPage(PaymentPageRequestTransfer $paymentPageRequestTransfer): PaymentPageResponseTransfer
    {
        return $this->paymentPage->getPaymentPage($paymentPageRequestTransfer);
    }

    public function transferPayments(PaymentTransmissionsRequestTransfer $paymentTransmissionsRequestTransfer): PaymentTransmissionsResponseTransfer
    {
        return $this->paymentTransfer->transferPayments($paymentTransmissionsRequestTransfer);
    }

    public function getPaymentMethodByTenantIdentifierAndPaymentMethodKey(string $tenantIdentifier, string $paymentMethodKey): PaymentMethodTransfer
    {
        return $this->paymentMethodReader->getPaymentMethodByTenantIdentifierAndPaymentMethodKey($tenantIdentifier, $paymentMethodKey);
    }

    public function customer(CustomerRequestTransfer $customerRequestTransfer): CustomerResponseTransfer
    {
        return $this->customer->customer($customerRequestTransfer);
    }
}
