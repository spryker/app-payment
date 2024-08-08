<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SprykerTest\Glue\AppPaymentBackendApi;

use Codeception\Actor;
use Generated\Shared\Transfer\InitializePaymentResponseTransfer;
use Generated\Shared\Transfer\OrderItemTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

/**
 * Inherited Methods
 *
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 *
 * @SuppressWarnings(PHPMD)
 */
class AppPaymentBackendApiTester extends Actor
{
    use _generated\AppPaymentBackendApiTesterActions;

    public function seeResponseJsonContainsPayment(Response $response): void
    {
        $response = json_decode($response->getContent(), true);

        $initializePaymentResponseTransfer = (new InitializePaymentResponseTransfer())->fromArray($response);

        $this->assertTrue($initializePaymentResponseTransfer->getIsSuccessful());
    }

    public function seeResponseJsonContainsClientSecret(Response $response): void
    {
        $response = json_decode($response->getContent(), true);

        $initializePaymentResponseTransfer = (new InitializePaymentResponseTransfer())->fromArray($response);

        $this->assertNotNull($initializePaymentResponseTransfer->getClientSecret(), 'Expected to get a client secret but got null');
    }

    public function haveTransferDefaults(string $tenantIdentifier, string $merchantReference, string $transactionId, string $orderReference): void
    {
        $this->haveAppConfigForTenant($tenantIdentifier);

        $this->havePayment([
            PaymentTransfer::TENANT_IDENTIFIER => $tenantIdentifier,
            PaymentTransfer::TRANSACTION_ID => $transactionId,
            PaymentTransfer::ORDER_REFERENCE => $orderReference,
        ]);
    }

    public function haveOrderItemsForTransfer(string $orderReference): array
    {
        $orderItems = [
            $this->haveOrderItem([
                OrderItemTransfer::ORDER_REFERENCE => $orderReference,
                OrderItemTransfer::ITEM_REFERENCE => Uuid::uuid4()->toString(),
                OrderItemTransfer::AMOUNT => 90,
            ]),
            $this->haveOrderItem([
                OrderItemTransfer::ORDER_REFERENCE => $orderReference,
                OrderItemTransfer::ITEM_REFERENCE => Uuid::uuid4()->toString(),
                OrderItemTransfer::AMOUNT => 90,
            ]),
        ];

        $requestOrderItems = [];

        foreach ($orderItems as $orderItemTransfer) {
            $requestOrderItems[] = $orderItemTransfer->toArray();
        }

        return $requestOrderItems;
    }

    public function haveOrderItemsForReverseTransfer(
        string $orderReference,
        string $merchantReference1,
        string $merchantReference2,
        string $transferId1,
        string $transferId2
    ): array {
        $orderItems = [
            $this->haveOrderItem([
                OrderItemTransfer::MERCHANT_REFERENCE => $merchantReference1,
                OrderItemTransfer::ORDER_REFERENCE => $orderReference,
                OrderItemTransfer::ITEM_REFERENCE => Uuid::uuid4()->toString(),
                OrderItemTransfer::TRANSFER_ID => $transferId1,
                OrderItemTransfer::AMOUNT => -90,
            ]),
            $this->haveOrderItem([
                OrderItemTransfer::MERCHANT_REFERENCE => $merchantReference2,
                OrderItemTransfer::ORDER_REFERENCE => $orderReference,
                OrderItemTransfer::ITEM_REFERENCE => Uuid::uuid4()->toString(),
                OrderItemTransfer::TRANSFER_ID => $transferId2,
                OrderItemTransfer::AMOUNT => -90,
            ]),
        ];

        $requestOrderItems = [];

        foreach ($orderItems as $orderItemTransfer) {
            $requestOrderItems[] = $orderItemTransfer->toArray();
        }

        return $requestOrderItems;
    }
}
