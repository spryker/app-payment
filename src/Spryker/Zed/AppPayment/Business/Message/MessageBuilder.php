<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business\Message;

use Generated\Shared\Transfer\CustomerRequestTransfer;

class MessageBuilder
{
    public static function paymentByTransactionIdNotFound(string $transactionId): string
    {
        return sprintf('Could not find a payment with the transaction id "%s"', $transactionId);
    }

    public static function getTransactionIdOrTenantIdentifierMissingOrEmpty(): string
    {
        return 'Either the transactionId or the tenantIdentifier is not set or empty.';
    }

    public static function getInvalidTransactionIdAndTenantIdentifierCombination(): string
    {
        return 'Found a transaction for the requested transactionId but it does not match with the requested tenantIdentifier';
    }

    public static function getRequestTransactionIdIsMissingOrEmpty(): string
    {
        return 'Request transactionId is missing or empty';
    }

    public static function getRequestTenantIdentifierIsMissingOrEmpty(): string
    {
        return 'Request tenantIdentifier is missing or empty';
    }

    public static function getPlatformPluginDoesNotProvideRenderingAPaymentPage(): string
    {
        return 'Platform plugin does not provide rendering a payment page';
    }

    public static function getPlatformPluginDoesNotProvideMarketplaceFeatures(): string
    {
        return 'Platform plugin does not provide Marketplace features';
    }

    public static function getPlatformPluginDoesNotProvideCustomerFeatures(): string
    {
        return 'Platform plugin does not provide Customer features';
    }

    public static function getNeitherACustomerNorCustomerPaymentProviderDataIsPresent(): string
    {
        return sprintf('Neither a `%1$s.%2$s` nor `%1$s.%3$s` is present. Depending on your use case you must at least provide one of them.', CustomerRequestTransfer::class, CustomerRequestTransfer::CUSTOMER, CustomerRequestTransfer::CUSTOMER_PAYMENT_SERVICE_PROVIDER_DATA);
    }

    public static function paymentByTenantIdentifierAndOrderReferenceNotFound(string $tenantIdentifier, string $orderReference): string
    {
        return sprintf('Could not find a payment with the tenantIdentifier "%s" and orderReference "%s"', $tenantIdentifier, $orderReference);
    }

    public static function paymentStatusAlreadyInStatus(string $status): string
    {
        return sprintf('Payment status already in "%s".', $status);
    }

    public static function paymentStatusTransitionNotAllowed(string $sourceStatus, string $targetStatus): string
    {
        return sprintf('Payment status transition from "%s" to "%s" is not allowed.', $sourceStatus, $targetStatus);
    }

    public static function refundByRefundIdNotFound(string $refundId): string
    {
        return sprintf('Could not find a refund with the refund id "%s"', $refundId);
    }

    public static function paymentTransferByTransferIdNotFound(string $transferId): string
    {
        return sprintf('Could not find a payment transfer with the transfer id "%s"', $transferId);
    }
}
