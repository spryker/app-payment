<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Payment\Method\Reader;

use Generated\Shared\Transfer\PaymentMethodCriteriaTransfer;
use Generated\Shared\Transfer\PaymentMethodTransfer;
use Spryker\Zed\AppPayment\Business\Exception\PaymentMethodNotFoundException;
use Spryker\Zed\AppPayment\Business\Payment\Method\Normalizer\PaymentMethodNormalizer;
use Spryker\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface;

class PaymentMethodReader
{
    public function __construct(protected AppPaymentRepositoryInterface $appPaymentRepository, protected PaymentMethodNormalizer $paymentMethodNormalizer)
    {
    }

    public function getPaymentMethod(PaymentMethodCriteriaTransfer $paymentMethodCriteriaTransfer): PaymentMethodTransfer
    {
        if (in_array($paymentMethodCriteriaTransfer->getTenantIdentifier(), [null, '', '0'], true) || (in_array($paymentMethodCriteriaTransfer->getPaymentMethodKey(), [null, '', '0'], true))) {
            throw new PaymentMethodNotFoundException(sprintf(
                'Payment method "%s" not found for Tenant "%s". Maybe the TenantIdentifier, the PaymentMethodKey, or both are missing.',
                $paymentMethodCriteriaTransfer->getPaymentMethodKey(),
                $paymentMethodCriteriaTransfer->getTenantIdentifier(),
            ));
        }

        $paymentMethodTransferCollection = $this->appPaymentRepository->getTenantPaymentMethods($paymentMethodCriteriaTransfer->getTenantIdentifier());

        $normalizePaymentMethodKey = $this->paymentMethodNormalizer->normalizePaymentMethodKey($paymentMethodCriteriaTransfer->getPaymentMethodKey());

        foreach ($paymentMethodTransferCollection as $paymentMethodTransfer) {
            if ($paymentMethodTransfer->getPaymentMethodKey() !== $normalizePaymentMethodKey) {
                continue;
            }

            return $paymentMethodTransfer;
        }

        throw new PaymentMethodNotFoundException(sprintf(
            'Payment method "%s" not found for Tenant "%s"',
            $paymentMethodCriteriaTransfer->getPaymentMethodKey(),
            $paymentMethodCriteriaTransfer->getTenantIdentifier(),
        ));
    }
}
