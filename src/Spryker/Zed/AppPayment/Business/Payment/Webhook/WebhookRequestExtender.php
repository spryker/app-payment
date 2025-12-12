<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Webhook;

use Generated\Shared\Transfer\WebhookRequestTransfer;
use Spryker\Zed\AppPayment\Business\Payment\AppConfig\AppConfigLoader;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;
use Spryker\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface;

class WebhookRequestExtender
{
    public function __construct(
        protected AppPaymentPlatformPluginInterface $appPaymentPlatformPlugin,
        protected AppConfigLoader $appConfigLoader,
        protected AppPaymentRepositoryInterface $appPaymentRepository
    ) {
    }

    public function extendWebhookRequestTransfer(WebhookRequestTransfer $webhookRequestTransfer): WebhookRequestTransfer
    {
        $paymentTransfer = $this->appPaymentRepository->getPaymentByTransactionId($webhookRequestTransfer->getTransactionIdOrFail());
        $appConfigTransfer = $this->appConfigLoader->loadAppConfig($paymentTransfer->getTenantIdentifierOrFail());

        $webhookRequestTransfer->setPaymentOrFail($paymentTransfer);
        $webhookRequestTransfer->setAppConfigOrFail($appConfigTransfer);

        // When the Payment Service Provider calls the refund webhook and the payment was cancelled before, there is no need for further process this webhook.
        // This happens when the Payment is cancelled on the Tenant side before the refund process was initialized.
        if ($webhookRequestTransfer->getTypeOrFail() === WebhookDataType::REFUND && $paymentTransfer->getStatus() === PaymentStatus::STATUS_CANCELED) {
            return $webhookRequestTransfer->setAbortHandling(true);
        }

        return $this->extendRefundWebhookRequestTransfer($webhookRequestTransfer);
    }

    protected function extendRefundWebhookRequestTransfer(WebhookRequestTransfer $webhookRequestTransfer): WebhookRequestTransfer
    {
        if ($webhookRequestTransfer->getTypeOrFail() === WebhookDataType::REFUND) {
            $webhookRequestTransfer->setRefundOrFail(
                $this->appPaymentRepository->getRefundByRefundId($webhookRequestTransfer->getRefundOrFail()->getRefundIdOrFail()),
            );
        }

        return $webhookRequestTransfer;
    }
}
