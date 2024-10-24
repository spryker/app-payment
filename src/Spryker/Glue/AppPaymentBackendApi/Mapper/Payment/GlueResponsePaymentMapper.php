<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Glue\AppPaymentBackendApi\Mapper\Payment;

use ArrayObject;
use Generated\Shared\Transfer\CancelPreOrderPaymentResponseTransfer;
use Generated\Shared\Transfer\ConfirmPreOrderPaymentResponseTransfer;
use Generated\Shared\Transfer\CustomerResponseTransfer;
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

        if ($initializePaymentResponseTransfer->getIsSuccessful() === false) {
            $glueResponseTransfer->setHttpStatus($initializePaymentResponseTransfer->getStatusCode() ?? Response::HTTP_BAD_REQUEST);

            if ($initializePaymentResponseTransfer->getMessage() !== null && $initializePaymentResponseTransfer->getMessage() !== '' && $initializePaymentResponseTransfer->getMessage() !== '0') {
                $glueResponseTransfer->addError((new GlueErrorTransfer())->setMessage(
                    $initializePaymentResponseTransfer->getMessage(),
                ));
            }

            return $glueResponseTransfer;
        }

        return $this->addInitializePaymentResponseTransferToGlueResponse($initializePaymentResponseTransfer, $glueResponseTransfer);
    }

    public function mapPaymentTransmissionsResponseTransferToSingleResourceGlueResponseTransfer(
        PaymentTransmissionsResponseTransfer $paymentTransmissionsResponseTransfer
    ): GlueResponseTransfer {
        $glueResponseTransfer = new GlueResponseTransfer();

        if ($paymentTransmissionsResponseTransfer->getIsSuccessful() === false) {
            $glueResponseTransfer->setHttpStatus($paymentTransmissionsResponseTransfer->getStatusCode() ?? Response::HTTP_BAD_REQUEST);
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
            $glueResponseTransfer->setHttpStatus($confirmPreOrderPaymentResponseTransfer->getStatusCode() ?? Response::HTTP_BAD_REQUEST);
            $glueResponseTransfer->addError((new GlueErrorTransfer())->setMessage(
                $confirmPreOrderPaymentResponseTransfer->getMessageOrFail(),
            ));
        }

        return $glueResponseTransfer;
    }

    public function mapCancelPreOrderPaymentResponseTransferToSingleResourceGlueResponseTransfer(
        CancelPreOrderPaymentResponseTransfer $cancelPreOrderPaymentResponseTransfer
    ): GlueResponseTransfer {
        $glueResponseTransfer = new GlueResponseTransfer();
        $glueResponseTransfer->setHttpStatus(Response::HTTP_OK);

        if ($cancelPreOrderPaymentResponseTransfer->getIsSuccessful() === false) {
            $glueResponseTransfer->setHttpStatus($cancelPreOrderPaymentResponseTransfer->getStatusCode() ?? Response::HTTP_BAD_REQUEST);
            $glueResponseTransfer->addError((new GlueErrorTransfer())->setMessage(
                $cancelPreOrderPaymentResponseTransfer->getMessageOrFail(),
            ));
        }

        return $glueResponseTransfer;
    }

    public function mapCustomerResponseTransferToSingleResourceGlueResponseTransfer(
        CustomerResponseTransfer $customerResponseTransfer
    ): GlueResponseTransfer {
        $glueResponseTransfer = new GlueResponseTransfer();
        $glueResponseTransfer->setHttpStatus(Response::HTTP_OK);

        if ($customerResponseTransfer->getIsSuccessful() === false) {
            $glueResponseTransfer->setHttpStatus($customerResponseTransfer->getStatusCode() ?? Response::HTTP_BAD_REQUEST);
            $glueResponseTransfer->addError((new GlueErrorTransfer())->setMessage(
                $customerResponseTransfer->getMessageOrFail(),
            ));

            return $glueResponseTransfer;
        }

        $glueResponseTransfer->setContent(
            (string)json_encode($customerResponseTransfer->toArray(true, true)),
        );

        return $glueResponseTransfer;
    }

    protected function addInitializePaymentResponseTransferToGlueResponse(
        InitializePaymentResponseTransfer $initializePaymentResponseTransfer,
        GlueResponseTransfer $glueResponseTransfer
    ): GlueResponseTransfer {
        $glueResponseTransfer->setContent(
            (string)json_encode($initializePaymentResponseTransfer->toArray(true, true)),
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
                'paymentTransmissionItems' => $this->formatPaymentTransmissionItemsForTransferResponse($paymentTransmission->getPaymentTransmissionItems()),
                'amount' => $paymentTransmission->getAmount(),
                'transferId' => $paymentTransmission->getTransferId(), // Return the transfer identifier as transaction id to be known on the Tenant side. May be empty in case oif a failure
            ];
        }

        return (string)json_encode($responseContent);
    }

    /**
     * @param \ArrayObject<int, (\Generated\Shared\Transfer\OrderItemTransfer | \Generated\Shared\Transfer\PaymentTransmissionItemTransfer)> $arrayObject
     *
     * @return array<int, array<string, string|null>>
     */
    protected function formatPaymentTransmissionItemsForTransferResponse(ArrayObject $arrayObject): array
    {
        $paymentTransmissionItemsData = [];
        foreach ($arrayObject as $paymentTransmissionItem) {
            $paymentTransmissionItemsData[] = [
                'merchantReference' => $paymentTransmissionItem->getMerchantReferenceOrFail(),
                'orderReference' => $paymentTransmissionItem->getOrderReferenceOrFail(),
                'itemReference' => $paymentTransmissionItem->getItemReferenceOrFail(),
                'amount' => $paymentTransmissionItem->getAmountOrFail(),
            ];
        }

        return $paymentTransmissionItemsData;
    }
}
