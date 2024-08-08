<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Persistence;

use Generated\Shared\Transfer\PaymentMethodTransfer;
use Generated\Shared\Transfer\PaymentRefundTransfer;
use Generated\Shared\Transfer\PaymentTransfer;

interface AppPaymentRepositoryInterface
{
    /**
     * @throws \Spryker\Zed\AppPayment\Persistence\Exception\PaymentByTransactionIdNotFoundException
     */
    public function getPaymentByTransactionId(string $transactionId): PaymentTransfer;

    /**
     * @throws \Spryker\Zed\AppPayment\Persistence\Exception\PaymentByTenantIdentifierAndOrderReferenceNotFoundException
     */
    public function getPaymentByTenantIdentifierAndOrderReference(string $tenantIdentifier, string $orderReference): PaymentTransfer;

    /**
     * @param array<string> $orderReferences
     *
     * @return array<\Generated\Shared\Transfer\PaymentTransfer>
     */
    public function getPaymentsByTenantIdentifierAndOrderReferences(string $tenantIdentifier, array $orderReferences): array;

    /**
     * @throws \Spryker\Zed\AppPayment\Persistence\Exception\RefundByRefundIdNotFoundException
     */
    public function getRefundByRefundId(string $refundId): PaymentRefundTransfer;

    /**
     * @param array<string> $orderItemIds
     * @param array<string> $refundStatuses
     *
     * @return array<\Generated\Shared\Transfer\PaymentRefundTransfer>
     */
    public function getRefundsByTransactionIdAndOrderItemIdAndStatuses(
        string $transactionId,
        array $orderItemIds,
        array $refundStatuses
    ): array;

    /**
     * @param array<string> $transferIds
     *
     * @return array<\Generated\Shared\Transfer\PaymentTransmissionTransfer>
     */
    public function findPaymentTransmissionsByTransferIds(array $transferIds): array;

    /**
     * @return array<\Generated\Shared\Transfer\PaymentMethodTransfer>
     */
    public function getTenantPaymentMethods(string $tenantIdentifier): array;

    public function savePaymentMethod(PaymentMethodTransfer $paymentMethodTransfer, string $tenantIdentifier): PaymentMethodTransfer;

    public function deletePaymentMethod(PaymentMethodTransfer $paymentMethodTransfer, string $tenantIdentifier): PaymentMethodTransfer;
}
