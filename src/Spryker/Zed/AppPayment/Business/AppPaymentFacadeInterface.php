<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business;

use Generated\Shared\Transfer\AppConfigTransfer;
use Generated\Shared\Transfer\CancelPaymentTransfer;
use Generated\Shared\Transfer\CapturePaymentTransfer;
use Generated\Shared\Transfer\ConfirmPreOrderPaymentRequestTransfer;
use Generated\Shared\Transfer\ConfirmPreOrderPaymentResponseTransfer;
use Generated\Shared\Transfer\InitializePaymentRequestTransfer;
use Generated\Shared\Transfer\InitializePaymentResponseTransfer;
use Generated\Shared\Transfer\PaymentCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\PaymentPageRequestTransfer;
use Generated\Shared\Transfer\PaymentPageResponseTransfer;
use Generated\Shared\Transfer\PaymentTransmissionsRequestTransfer;
use Generated\Shared\Transfer\PaymentTransmissionsResponseTransfer;
use Generated\Shared\Transfer\RedirectRequestTransfer;
use Generated\Shared\Transfer\RedirectResponseTransfer;
use Generated\Shared\Transfer\RefundPaymentTransfer;
use Generated\Shared\Transfer\WebhookRequestTransfer;
use Generated\Shared\Transfer\WebhookResponseTransfer;

interface AppPaymentFacadeInterface
{
    /**
     * Specification:
     * - Calls the `PaymentPlatformPluginInterface::initializePayment()` method.
     * - When `PaymentPlatformPluginInterface::initializePayment()` throws an exception, the exception is logged.
     * - When `PaymentPlatformPluginInterface::initializePayment()` throws an exception, a `InitializePaymentResponseTransfer` with a failed response is returned.
     * - When `PaymentPlatformPluginInterface::initializePayment()` is successful, the `InitializePaymentResponseTransfer::redirectUrl` will be set to the current application.
     * - When `PaymentPlatformPluginInterface::initializePayment()` is successful, a `SpyPayment` entity will be persisted.
     * - When `PaymentPlatformPluginInterface::initializePayment()` is successful, a `InitializePaymentResponseTransfer` with a successful response is returned.
     *
     * @api
     */
    public function initializePayment(InitializePaymentRequestTransfer $initializePaymentRequestTransfer): InitializePaymentResponseTransfer;

    /**
     * Specification:
     * - Validates the `$requestData`:
     *   - Requires `$requestData['transactionId']`.
     *   - Requires `$requestData['tenantIdentifier']`.
     * - When one of the required fields is not given or empty, an error will be logged.
     * - When one of the required fields is not given or empty, a `PaymentPageResponseTransfer` with a failed response will be returned.
     * - When one of the required fields is not given or empty, the default error page will be rendered.
     * - Loads the in the `PaymentFacadeInterface::initializePayment()` method persisted `PaymentTransfer`.
     * - When no Payment entity found for the given `transactionId`, an error will be logged.
     * - When no Payment entity found for the given `transactionId`, the default error page will be rendered.
     * - Validates the `PaymentTransfer::tenantIdentifier` with the one passed by the request.
     * - When the passed `tenantIdentifier` does not match with the persisted one, an error will be logged.
     * - When the passed `tenantIdentifier` does not match with the persisted one, the default error page will be rendered.
     * - Loads the `AppConfigTransfer` for the passed `tenantIdentifier`.
     * - Calls the `PaymentPlatformPluginInterface::getPaymentPage()` method.
     * - When `PaymentPlatformPluginInterface::getPaymentPage()` throws an exception, the exception is logged.
     * - When `PaymentPlatformPluginInterface::getPaymentPage()` throws an exception, a `PaymentPageResponseTransfer` with a failed response is returned.
     * - When `PaymentPlatformPluginInterface::getPaymentPage()` usSuccessful, a `PaymentPageResponseTransfer` with a successful response is returned.
     *
     * @api
     */
    public function getPaymentPage(PaymentPageRequestTransfer $paymentPageRequestTransfer): PaymentPageResponseTransfer;

    /**
     * Specification:
     * - Loads the in the `PaymentFacadeInterface::initializePayment()` method persisted `PaymentTransfer`.
     * - When no Payment entity found for the given `transactionId`, an error will be logged.
     * - Validates the `PaymentTransfer::tenantIdentifier` with the one passed by the request.
     * - When the passed `tenantIdentifier` does not match with the persisted one, an error will be logged.
     * - Loads the `AppConfigTransfer` for the passed `tenantIdentifier`.
     * - Calls the `PaymentPlatformPluginInterface::handleWebhook()` method.
     * - When `PaymentPlatformPluginInterface::handleWebhook()` throws an exception, the exception is logged.
     * - When `PaymentPlatformPluginInterface::handleWebhook()` throws an exception, a `WebhookResponseTransfer` with a failed response is returned.
     * - When `PaymentPlatformPluginInterface::handleWebhook()` isSuccessful, a `WebhookResponseTransfer` with a successful response is returned.
     *
     * @api
     */
    public function handleWebhook(WebhookRequestTransfer $webhookRequestTransfer, WebhookResponseTransfer $webhookResponseTransfer): WebhookResponseTransfer;

    /**
     * Specification:
     * - Requests the `AppPaymentPaymentMethodsPlatformPluginInterface::configurePaymentMethods()` method to return a list PaymentMethods to be added.
     * - When the passed `AppPaymentPlatformPluginInterface` is not an instance of `AppPaymentPaymentMethodsPlatformPluginInterface` it will return early.
     * - PaymentMethods that were already added will not be added again.
     * - PaymentMethods that were already persisted and are no longer returned from `AppPaymentPaymentMethodsPlatformPluginInterface::configurePaymentMethods()` method will be deleted and trigger a `DeletePaymentMethod` message.
     * - PaymentMethods that were already persisted and require an update will trigger a `UpdatePaymentMethod` message.
     *
     * @api
     */
    public function configurePaymentMethods(AppConfigTransfer $appConfigTransfer): AppConfigTransfer;

    /**
     * Specification:
     * - Requests the `AppPaymentPaymentMethodsPlatformPluginInterface::configurePaymentMethods()` method to return a list PaymentMethods to add and to delete.
     * - When the passed `AppPaymentPlatformPluginInterface` is not an instance of `AppPaymentPaymentMethodsPlatformPluginInterface` it will return early.
     *
     * @api
     */
    public function deletePaymentMethods(AppConfigTransfer $appConfigTransfer): AppConfigTransfer;

    /**
     * Specification:
     * - Sends a `AddPaymentMethod` message when the AppConfiguration is in state NEW.
     * - Updates the AppConfiguration and sets its state to connected after the `AddPaymentMethod` message was sent.
     * - When the AppConfiguration is in state CONNECTED the `AddPaymentMethod` message will not be sent.
     *
     * @api
     *
     * @deprecated Use {@link \Spryker\Zed\AppPayment\Business\AppPaymentFacadeInterface::configurePaymentMethods() } instead
     */
    public function sendAddPaymentMethodMessage(AppConfigTransfer $appConfigTransfer): AppConfigTransfer;

    /**
     * Specification:
     * - Sends a `DeletePaymentMethod` message when the AppConfiguration is removed.
     *
     * @api
     *
     * @deprecated Use {@link \Spryker\Zed\AppPayment\Business\AppPaymentFacade::deletePaymentMethods() } instead
     */
    public function sendDeletePaymentMethodMessage(AppConfigTransfer $appConfigTransfer): AppConfigTransfer;

    /**
     * Specification:
     * - Handles the `CancelPayment` message.
     *
     * @api
     */
    public function handleCancelPayment(CancelPaymentTransfer $cancelPaymentTransfer): void;

    /**
     * Specification:
     * - Handles the `CapturePayment` message.
     *
     * @api
     */
    public function handleCapturePayment(CapturePaymentTransfer $capturePaymentTransfer): void;

    /**
     * Specification:
     * - Handles the `RefundPayment` message.
     *
     * @api
     */
    public function handleRefundPayment(RefundPaymentTransfer $refundPaymentTransfer): void;

    /**
     * Specification:
     * - Loads the in the `PaymentFacadeInterface::initializePayment()` method persisted `PaymentTransfer`.
     * - When no Payment entity found for the given `transactionId`, an error will be logged.
     * - Loads the `AppConfigTransfer` by the `tenantIdentifier` of the `PaymentTransfer`.
     * - Calls the `PaymentPlatformPluginInterface::getPaymentStatus()` method.
     * - Prepares a `PaymentStatusRequestTransfer` with the `PaymentTransfer`, `AppConfigTransfer`, and the `transactionId`.
     * - When `PaymentPlatformPluginInterface::getPaymentStatus()` throws an exception, the exception is logged.
     * - When `PaymentPlatformPluginInterface::getPaymentStatus()` throws an exception, a `PaymentStatusResponseTransfer` with a failed response is returned.
     * - When `PaymentPlatformPluginInterface::getPaymentStatus()` isSuccessful, a `PaymentStatusResponseTransfer` with a successful response is returned.
     * - Based on the `PaymentStatusResponseTransfer::isSuccessful` the `RedirectResponseTransfer::url` will be set to `cancel` or `success` URL that was passed when initializing the payment.
     *
     * @api
     */
    public function getRedirectUrl(RedirectRequestTransfer $redirectRequestTransfer): RedirectResponseTransfer;

    /**
     * Specification:
     * - Requires `PaymentCollectionDeleteCriteria.tenantIdentifier` to be set.
     * - Deletes the payment collection based on the provided criteria.
     *
     * @api
     */
    public function deletePaymentCollection(
        PaymentCollectionDeleteCriteriaTransfer $paymentCollectionDeleteCriteriaTransfer
    ): void;

    /**
     * Specification:
     * - Transfers payments.
     * - Loads the `AppConfigTransfer` and adds it to the PaymentTransmissionsRequestTransfer.
     * - Applies PaymentTransmissionExpanderPluginInterfaces.
     * - Returns a PaymentTransmissionsResponseTransfer.
     *
     * @api
     */
    public function transferPayments(PaymentTransmissionsRequestTransfer $paymentTransmissionsRequestTransfer): PaymentTransmissionsResponseTransfer;

    /**
     * Specification:
     * - Confirm a payment that was made before the order was persisted.
     * - Loads the `AppConfigTransfer` and adds it to the ConfirmPreOrderPaymentRequestTransfer.
     * - Returns a ConfirmPreOrderPaymentResponseTransfer.
     *
     * @api
     */
    public function confirmPreOrderPayment(
        ConfirmPreOrderPaymentRequestTransfer $confirmPreOrderPaymentRequestTransfer
    ): ConfirmPreOrderPaymentResponseTransfer;
}
