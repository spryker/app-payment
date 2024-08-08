<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Glue\AppPaymentBackendApi\Mapper\Payment;

use ArrayObject;
use Generated\Shared\Transfer\ConfirmPreOrderPaymentResponseTransfer;
use Generated\Shared\Transfer\GlueErrorTransfer;
use Generated\Shared\Transfer\GlueResourceTransfer;
use Generated\Shared\Transfer\GlueResponseTransfer;
use Generated\Shared\Transfer\InitializePaymentResponseTransfer;
use Generated\Shared\Transfer\PaymentTransmissionsResponseTransfer;
use Symfony\Component\HttpFoundation\Response;

class GlueResponsePaymentMapper implements GlueResponsePaymentMapperInterface
{
    public function mapInitializePaymentResponseTransferToSingleResourceGlueResponseTransfer(
        InitializePaymentResponseTransfer $initializePaymentResponseTransfer
    ): GlueResponseTransfer {
        $glueResponseTransfer = new GlueResponseTransfer();

        return $this->addInitializePaymentResponseTransferToGlueResponse($initializePaymentResponseTransfer, $glueResponseTransfer);
    }

    public function mapPaymentTransmissionsResponseTransferToSingleResourceGlueResponseTransfer(
        PaymentTransmissionsResponseTransfer $paymentTransmissionsResponseTransfer
    ): GlueResponseTransfer {
        $glueResponseTransfer = new GlueResponseTransfer();

        if ($paymentTransmissionsResponseTransfer->getIsSuccessful() === false) {
            $glueResponseTransfer->setHttpStatus(Response::HTTP_BAD_REQUEST);
            $glueResponseTransfer->addError((new GlueErrorTransfer())->setMessage(
                $paymentTransmissionsResponseTransfer->getMessageOrFail(),
            ));

            return $glueResponseTransfer;
        }

        $glueResourceTransfer = new GlueResourceTransfer();
        $glueResourceTransfer->setType('payments-transfers');

        $glueResponseTransfer->setHttpStatus(Response::HTTP_OK);
        $glueResponseTransfer->addResource($glueResourceTransfer);
        $glueResponseTransfer->setContent($this->generateTransfersResponseContent($paymentTransmissionsResponseTransfer));

        return $glueResponseTransfer;
    }

    public function mapConfirmPreOrderPaymentResponseTransferToSingleResourceGlueResponseTransfer(
        ConfirmPreOrderPaymentResponseTransfer $confirmPreOrderPaymentResponseTransfer
    ): GlueResponseTransfer {
        $glueResponseTransfer = new GlueResponseTransfer();
        $glueResponseTransfer->setHttpStatus(Response::HTTP_OK);

        if ($confirmPreOrderPaymentResponseTransfer->getIsSuccessful() === false) {
            $glueResponseTransfer->setHttpStatus(Response::HTTP_BAD_REQUEST);
            $glueResponseTransfer->addError((new GlueErrorTransfer())->setMessage(
                $confirmPreOrderPaymentResponseTransfer->getMessageOrFail(),
            ));
        }

        return $glueResponseTransfer;
    }

    protected function addInitializePaymentResponseTransferToGlueResponse(
        InitializePaymentResponseTransfer $initializePaymentResponseTransfer,
        GlueResponseTransfer $glueResponseTransfer
    ): GlueResponseTransfer {
        $glueResponseTransfer->setContent(
            (string)json_encode($initializePaymentResponseTransfer->toArray()),
        );

        // SCOS Checkout expects a 200 response code and check isSuccessful property to show error message.
        $glueResponseTransfer->setHttpStatus(Response::HTTP_OK);

        return $glueResponseTransfer;
    }

    protected function generateTransfersResponseContent(PaymentTransmissionsResponseTransfer $paymentTransmissionsResponseTransfer): string
    {
        $responseContent = [
            'transfers' => [],
        ];

        foreach ($paymentTransmissionsResponseTransfer->getPaymentTransmissions() as $paymentTransmission) {
            $responseContent['transfers'][] = [
                'isSuccessful' => $paymentTransmission->getIsSuccessful(),
                'failureMessage' => $paymentTransmission->getMessage(),
                'merchantReference' => $paymentTransmission->getMerchantReference(),
                'orderReference' => $paymentTransmission->getOrderReference(),
                'orderItems' => $this->formatOrderItemsForTransferResponse($paymentTransmission->getOrderItems()),
                'amount' => $paymentTransmission->getAmount(),
                'transferId' => $paymentTransmission->getTransferId(), // Return the transfer identifier as transaction id to be known on the Tenant side. May be empty in case oif a failure
            ];
        }

        return (string)json_encode($responseContent);
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\OrderItemTransfer> $arrayObject
     *
     * @return array<int, array<string, string|null>>
     */
    protected function formatOrderItemsForTransferResponse(ArrayObject $arrayObject): array
    {
        $orderItemsData = [];

        foreach ($arrayObject as $orderItem) {
            $orderItemsData[] = [
                'merchantReference' => $orderItem->getMerchantReferenceOrFail(),
                'orderReference' => $orderItem->getOrderReferenceOrFail(),
                'itemReference' => $orderItem->getItemReferenceOrFail(),
                'amount' => $orderItem->getAmountOrFail(),
            ];
        }

        return $orderItemsData;
    }
}
