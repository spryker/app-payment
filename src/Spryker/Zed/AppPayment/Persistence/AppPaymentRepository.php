<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Persistence;

use Generated\Shared\Transfer\PaymentCollectionTransfer;
use Generated\Shared\Transfer\PaymentCriteriaTransfer;
use Generated\Shared\Transfer\PaymentMethodTransfer;
use Generated\Shared\Transfer\PaymentRefundTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\PaymentTransmissionTransfer;
use Orm\Zed\AppPayment\Persistence\SpyPayment;
use Orm\Zed\AppPayment\Persistence\SpyPaymentQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Spryker\Zed\AppPayment\Persistence\Exception\PaymentByTenantIdentifierAndOrderReferenceNotFoundException;
use Spryker\Zed\AppPayment\Persistence\Exception\PaymentByTransactionIdNotFoundException;
use Spryker\Zed\AppPayment\Persistence\Exception\RefundByRefundIdNotFoundException;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;

/**
 * @method \Spryker\Zed\AppPayment\Persistence\AppPaymentPersistenceFactory getFactory()
 */
class AppPaymentRepository extends AbstractRepository implements AppPaymentRepositoryInterface
{
    public function getPaymentCollection(PaymentCriteriaTransfer $paymentCriteriaTransfer): PaymentCollectionTransfer
    {
        $paymentCollection = $this->applyPaymentCriteria(
            $this->getFactory()->createPaymentQuery(),
            $paymentCriteriaTransfer,
        )->find();

        return $this->getFactory()->createPaymentMapper()
            ->mapPaymentEntitiesToPaymentCollectionTransfer($paymentCollection, new PaymentCollectionTransfer());
    }

    /**
     * @throws \Spryker\Zed\AppPayment\Persistence\Exception\PaymentByTransactionIdNotFoundException
     */
    public function getPaymentByTransactionId(string $transactionId): PaymentTransfer
    {
        $spyPayment = $this->getFactory()->createPaymentQuery()->findOneByTransactionId($transactionId);

        if ($spyPayment === null) {
            throw new PaymentByTransactionIdNotFoundException($transactionId);
        }

        return $this->mapPaymentEntityToPaymentTransfer($spyPayment);
    }

    /**
     * @throws \Spryker\Zed\AppPayment\Persistence\Exception\PaymentByTenantIdentifierAndOrderReferenceNotFoundException
     */
    public function getPaymentByTenantIdentifierAndOrderReference(string $tenantIdentifier, string $orderReference): PaymentTransfer
    {
        $spyPayment = $this->getFactory()->createPaymentQuery()->filterByTenantIdentifier($tenantIdentifier)
            ->filterByOrderReference($orderReference)
            ->findOne();

        if ($spyPayment === null) {
            throw new PaymentByTenantIdentifierAndOrderReferenceNotFoundException($tenantIdentifier, $orderReference);
        }

        return $this->mapPaymentEntityToPaymentTransfer($spyPayment);
    }

    /**
     * @param array<string> $orderReferences
     *
     * @return array<\Generated\Shared\Transfer\PaymentTransfer>
     */
    public function getPaymentsByTenantIdentifierAndOrderReferences(string $tenantIdentifier, array $orderReferences): array
    {
        /** @var array<\Orm\Zed\AppPayment\Persistence\SpyPayment> $spyPaymentCollection */
        $spyPaymentCollection = $this->getFactory()->createPaymentQuery()
            ->filterByTenantIdentifier($tenantIdentifier)
            ->filterByOrderReference_In($orderReferences)
            ->find();

        $paymentTransfers = [];

        foreach ($spyPaymentCollection as $spyPaymentEntity) {
            $paymentTransfers[] = $this->mapPaymentEntityToPaymentTransfer($spyPaymentEntity);
        }

        return $paymentTransfers;
    }

    /**
     * @throws \Spryker\Zed\AppPayment\Persistence\Exception\RefundByRefundIdNotFoundException
     */
    public function getRefundByRefundId(string $refundId): PaymentRefundTransfer
    {
        $paymentRefundEntity = $this->getFactory()->createPaymentRefundQuery()->findOneByRefundId($refundId);

        if ($paymentRefundEntity === null) {
            throw new RefundByRefundIdNotFoundException($refundId);
        }

        return $this->getFactory()->createPaymentMapper()
            ->mapPaymentRefundEntityToPaymentRefundTransfer($paymentRefundEntity, new PaymentRefundTransfer());
    }

    public function getRefundsByTransactionIdAndOrderItemIdAndStatuses(
        string $transactionId,
        array $orderItemIds,
        array $refundStatuses
    ): array {
        $paymentRefundEntityCollection = $this->getFactory()->createPaymentRefundQuery()
            ->filterByTransactionId($transactionId)
            ->filterByOrderItemIds($orderItemIds, Criteria::CONTAINS_SOME)
            ->filterByStatus_In($refundStatuses)
            ->find();

        return $this->getFactory()->createPaymentMapper()
            ->mapPaymentRefundEntityCollectionToPaymentRefundTransfers($paymentRefundEntityCollection);
    }

    protected function applyPaymentCriteria(
        SpyPaymentQuery $spyPaymentQuery,
        PaymentCriteriaTransfer $paymentCriteriaTransfer
    ): SpyPaymentQuery {
        $paymentConditionsTransfer = $paymentCriteriaTransfer->getPaymentConditionsOrFail();

        if ($paymentConditionsTransfer->getTenantIdentifier() !== null) {
            $spyPaymentQuery->filterByTenantIdentifier($paymentConditionsTransfer->getTenantIdentifier());
        }

        if ($paymentConditionsTransfer->getExcludingStatuses() !== []) {
            $spyPaymentQuery->filterByStatus($paymentConditionsTransfer->getExcludingStatuses(), Criteria::NOT_IN);
        }

        return $spyPaymentQuery;
    }

    protected function mapPaymentEntityToPaymentTransfer(SpyPayment $spyPayment): PaymentTransfer
    {
        return $this->getFactory()->createPaymentMapper()->mapPaymentEntityToPaymentTransfer($spyPayment, new PaymentTransfer());
    }

    /**
     * @param array<string> $transferIds
     *
     * @return array<\Generated\Shared\Transfer\PaymentTransmissionTransfer>
     */
    public function findPaymentTransmissionsByTransferIds(array $transferIds): array
    {
        $collection = $this->getFactory()->createPaymentTransferQuery()->filterByTransferId_In($transferIds)->find();

        $paymentTransmissionTransfers = [];

        foreach ($collection as $paymentTransferEntity) {
            $paymentTransmissionTransfers[$paymentTransferEntity->getTransferId()] = $this->getFactory()->createPaymentMapper()
                ->mapPaymentTransmissionEntityToPaymentTransmissionTransfer($paymentTransferEntity, new PaymentTransmissionTransfer());
        }

        return $paymentTransmissionTransfers;
    }

    /**
     * @return array<\Generated\Shared\Transfer\PaymentMethodTransfer>
     */
    public function getTenantPaymentMethods(string $tenantIdentifier): array
    {
        $collection = $this->getFactory()->createPaymentMethodQuery()
            ->filterByTenantIdentifier($tenantIdentifier)
            ->find();

        $paymentMethodTransfers = [];

        foreach ($collection as $paymentMethodEntity) {
            $paymentMethodTransfers[] = $this->getFactory()->createPaymentMapper()
                ->mapPaymentMethodEntityToPaymentMethodTransfer($paymentMethodEntity, new PaymentMethodTransfer());
        }

        return $paymentMethodTransfers;
    }

    public function savePaymentMethod(PaymentMethodTransfer $paymentMethodTransfer, string $tenantIdentifier): PaymentMethodTransfer
    {
        $paymentMethodEntity = $this->getFactory()->createPaymentMethodQuery()
            ->filterByTenantIdentifier($tenantIdentifier)
            ->filterByName($paymentMethodTransfer->getName())
            ->findOneOrCreate();

        $paymentMethodEntity = $this->getFactory()->createPaymentMapper()
            ->mapPaymentMethodTransferToPaymentMethodEntity($paymentMethodTransfer, $paymentMethodEntity);

        $paymentMethodEntity->save();

        return $this->getFactory()->createPaymentMapper()
            ->mapPaymentMethodEntityToPaymentMethodTransfer($paymentMethodEntity, $paymentMethodTransfer);
    }

    public function deletePaymentMethod(PaymentMethodTransfer $paymentMethodTransfer, string $tenantIdentifier): PaymentMethodTransfer
    {
        $paymentMethodEntity = $this->getFactory()->createPaymentMethodQuery()
            ->filterByTenantIdentifier($tenantIdentifier)
            ->filterByName($paymentMethodTransfer->getName())
            ->findOne();

        if ($paymentMethodEntity === null) {
            // @codeCoverageIgnoreStart
            // There is always a Payment method
            return $paymentMethodTransfer;
            // @codeCoverageIgnoreEnd
        }

        $paymentMethodEntity->delete();

        return $paymentMethodTransfer;
    }
}
