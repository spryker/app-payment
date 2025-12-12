<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Persistence;

use Generated\Shared\Transfer\PaymentCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\PaymentRefundTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\PaymentTransmissionTransfer;
use Orm\Zed\AppPayment\Persistence\SpyPayment;
use Orm\Zed\AppPayment\Persistence\SpyPaymentQuery;
use Orm\Zed\AppPayment\Persistence\SpyPaymentRefund;
use Orm\Zed\AppPayment\Persistence\SpyPaymentStatusHistory;
use Orm\Zed\AppPayment\Persistence\SpyPaymentTransfer;
use Spryker\Zed\AppPayment\Persistence\Exception\PaymentByTransactionIdNotFoundException;
use Spryker\Zed\AppPayment\Persistence\Exception\RefundByRefundIdNotFoundException;
use Spryker\Zed\Kernel\Persistence\AbstractEntityManager;

/**
 * @method \Spryker\Zed\AppPayment\Persistence\AppPaymentPersistenceFactory getFactory()
 */
class AppPaymentEntityManager extends AbstractEntityManager implements AppPaymentEntityManagerInterface
{
    public function createPayment(PaymentTransfer $paymentTransfer): PaymentTransfer
    {
        $spyPayment = $this->getFactory()->createPaymentMapper()->mapPaymentTransferToPaymentEntity($paymentTransfer, new SpyPayment());
        $spyPayment->save();

        return $this->getFactory()->createPaymentMapper()->mapPaymentEntityToPaymentTransfer($spyPayment, $paymentTransfer);
    }

    public function updatePayment(PaymentTransfer $paymentTransfer): PaymentTransfer
    {
        $spyPaymentQuery = $this->getFactory()->createPaymentQuery();

        if (!in_array($paymentTransfer->getIdPayment(), [null, '', '0'], true)) {
            $spyPaymentQuery->filterByIdPayment($paymentTransfer->getIdPayment());
        }

        if (in_array($paymentTransfer->getIdPayment(), [null, '', '0'], true)) {
            $spyPaymentQuery->filterByTransactionId($paymentTransfer->getTransactionIdOrFail());
        }

        $spyPayment = $spyPaymentQuery->findOne();

        if ($spyPayment === null) {
            throw new PaymentByTransactionIdNotFoundException($paymentTransfer->getTransactionIdOrFail());
        }

        $spyPayment = $this->getFactory()->createPaymentMapper()->mapPaymentTransferToPaymentEntity($paymentTransfer, $spyPayment);
        $spyPayment->save();

        return $this->getFactory()->createPaymentMapper()->mapPaymentEntityToPaymentTransfer($spyPayment, $paymentTransfer);
    }

    public function savePaymentTransfer(PaymentTransmissionTransfer $paymentTransmissionTransfer): PaymentTransmissionTransfer
    {
        $spyPaymentTransfer = $this->getFactory()->createPaymentMapper()
            ->mapPaymentTransmissionTransferToPaymentTransferEntity($paymentTransmissionTransfer, new SpyPaymentTransfer());

        $spyPaymentTransfer->save();

        return $this->getFactory()->createPaymentMapper()
            ->mapPaymentTransmissionEntityToPaymentTransmissionTransfer($spyPaymentTransfer, $paymentTransmissionTransfer);
    }

    public function createPaymentRefund(PaymentRefundTransfer $paymentRefundTransfer): PaymentRefundTransfer
    {
        $spyPaymentRefund = $this->getFactory()->createPaymentMapper()
            ->mapPaymentRefundTransferToPaymentRefundEntity($paymentRefundTransfer, (new SpyPaymentRefund()));
        $spyPaymentRefund->save();

        return $this->getFactory()->createPaymentMapper()->mapPaymentRefundEntityToPaymentRefundTransfer($spyPaymentRefund, $paymentRefundTransfer);
    }

    public function updatePaymentRefund(PaymentRefundTransfer $paymentRefundTransfer): PaymentRefundTransfer
    {
        $paymentRefundEntity = $this->getFactory()->createPaymentRefundQuery()
            ->findOneByRefundId($paymentRefundTransfer->getRefundIdOrFail());

        if ($paymentRefundEntity === null) {
            throw new RefundByRefundIdNotFoundException($paymentRefundTransfer->getRefundIdOrFail());
        }

        $paymentRefundEntity = $this->getFactory()->createPaymentMapper()
            ->mapPaymentRefundTransferToPaymentRefundEntity($paymentRefundTransfer, $paymentRefundEntity);
        $paymentRefundEntity->save();

        return $this->getFactory()->createPaymentMapper()->mapPaymentRefundEntityToPaymentRefundTransfer($paymentRefundEntity, $paymentRefundTransfer);
    }

    public function deletePaymentCollection(
        PaymentCollectionDeleteCriteriaTransfer $paymentCollectionDeleteCriteriaTransfer
    ): void {
        $spyPaymentQuery = $this->getFactory()->createPaymentQuery();
        $spyPaymentQuery->filterByTenantIdentifier($paymentCollectionDeleteCriteriaTransfer->getTenantIdentifierOrFail());

        if (!in_array($paymentCollectionDeleteCriteriaTransfer->getTransactionId(), [null, '', '0'], true)) {
            $spyPaymentQuery->filterByTransactionId($paymentCollectionDeleteCriteriaTransfer->getTransactionIdOrFail());
        }

        $spyPaymentQuery->delete();
    }

    public function updatePaymentTransactionId(PaymentTransfer $paymentTransfer, string $transactionId): void
    {
        $spyPaymentEntity = SpyPaymentQuery::create()
            ->filterByTransactionId($paymentTransfer->getTransactionIdOrFail())
            ->findOne();

        $spyPaymentEntity
            ?->setTransactionId($transactionId)
            ?->save();
    }

    public function savePaymentStatusHistory(PaymentTransfer $paymentTransfer): void
    {
        $spyPaymentStatusHistory = new SpyPaymentStatusHistory();
        $spyPaymentStatusHistory->fromArray($paymentTransfer->toArray());

        $spyPaymentStatusHistory->save();
    }
}
