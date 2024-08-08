<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Dependency\Plugin;

use Generated\Shared\Transfer\CancelPaymentRequestTransfer;
use Generated\Shared\Transfer\CancelPaymentResponseTransfer;
use Generated\Shared\Transfer\CapturePaymentRequestTransfer;
use Generated\Shared\Transfer\CapturePaymentResponseTransfer;
use Generated\Shared\Transfer\InitializePaymentRequestTransfer;
use Generated\Shared\Transfer\InitializePaymentResponseTransfer;
use Generated\Shared\Transfer\PaymentStatusRequestTransfer;
use Generated\Shared\Transfer\PaymentStatusResponseTransfer;
use Generated\Shared\Transfer\RefundPaymentRequestTransfer;
use Generated\Shared\Transfer\RefundPaymentResponseTransfer;
use Generated\Shared\Transfer\WebhookRequestTransfer;
use Generated\Shared\Transfer\WebhookResponseTransfer;

interface AppPaymentPlatformPluginInterface
{
    /**
     * Specification:
     * - Receives a `InitializePaymentRequestTransfer` with:
     *   - `InitializePaymentRequestTransfer::orderData` (QuoteTransfer)
     *   - `InitializePaymentRequestTransfer::QuoteTransfer::currencyCode`
     *   - `InitializePaymentRequestTransfer::QuoteTransfer::grandTotal`
     *   - `InitializePaymentRequestTransfer::QuoteTransfer::orderReference` (optional: not required for pre-order payments) Connection happens through
     *      the `AppPreOrderPaymentPlatformPluginInterface` for pre-order payments when the PSP supports this functionality.
     * - Returns a `InitializePaymentResponseTransfer`.
     * - Requires `InitializePaymentResponseTransfer::isSuccessful`to be set.
     * - Requires `InitializePaymentResponseTransfer::message` to be set when the 3rd party provider could not process the request.
     * - Returns a `InitializePaymentResponseTransfer` with a failed response when the 3rd party provider could not process the request.
     * - Returns a `InitializePaymentResponseTransfer` with a successful response when the 3rd party provider was able to process the request.
     * - Requires to return a `InitializePaymentResponseTransfer::transactionId` with a successful response when the 3rd party provider was able to process the request.
     *
     * @api
     */
    public function initializePayment(InitializePaymentRequestTransfer $initializePaymentRequestTransfer): InitializePaymentResponseTransfer;

    /**
     * Specification:
     * - Tries to Capture Payment for an existing PaymentIntent.
     * - Requires `CapturePaymentRequestTransfer::transactionId`to be set.
     * - Requires `CapturePaymentRequestTransfer::appConfig`to be set.
     * - Returns a `CapturePaymentResponseTransfer`.
     * - Requires `CapturePaymentResponseTransfer::isSuccessful`to be set.
     * - Requires `CapturePaymentResponseTransfer::message` to be set when the 3rd party provider could not process the request.
     * - Returns a `CapturePaymentResponseTransfer` with a failed response status and message when the 3rd party provider could not process the request.
     *
     * @api
     */
    public function capturePayment(CapturePaymentRequestTransfer $capturePaymentRequestTransfer): CapturePaymentResponseTransfer;

    /**
     * Specification:
     * - Tries to Cancel Payment for an existing PaymentIntent.
     * - Requires `CancelPaymentRequestTransfer::transactionId`to be set.
     * - Requires `CancelPaymentRequestTransfer::appConfig`to be set.
     * - Returns a `CancelPaymentResponseTransfer`.
     * - Requires `CancelPaymentResponseTransfer::isSuccessful`to be set.
     * - Requires `CancelPaymentResponseTransfer::message` to be set when the 3rd party provider could not process the request.
     * - Returns a `CancelPaymentResponseTransfer` with a failed response status and message when the 3rd party provider could not process the request.
     *
     * @api
     */
    public function cancelPayment(CancelPaymentRequestTransfer $cancelPaymentRequestTransfer): CancelPaymentResponseTransfer;

    /**
     * Specification:
     * - Tries to Refund some amount from Payment.
     * - Requires `RefundPaymentRequestTransfer::transactionId`to be set.
     * - Requires `RefundPaymentRequestTransfer::appConfig`to be set.
     * - Returns a `RefundPaymentResponseTransfer`.
     * - Requires `RefundPaymentResponseTransfer::isSuccessful`to be set.
     * - Requires `RefundPaymentResponseTransfer::message` to be set when the 3rd party provider could not process the request.
     * - Returns a `RefundPaymentResponseTransfer` with a failed response status and message when the 3rd party provider could not process the request.
     *
     * @api
     */
    public function refundPayment(RefundPaymentRequestTransfer $refundPaymentRequestTransfer): RefundPaymentResponseTransfer;

    /**
     * Specification:
     * - Receives a `WebhookRequestTransfer` with:
     *   - `WebhookRequestTransfer::payment` (PaymentTransfer)
     *   - `WebhookRequestTransfer::appConfig (AppConfigTransfer)`
     *   - `WebhookRequestTransfer::content`
     * - Returns a `WebhookResponseTransfer`.
     * - Requires `WebhookResponseTransfer::isSuccessful`to be set.
     * - Requires `WebhookResponseTransfer::message` to be set when the 3rd party provider could not process the request.
     * - Returns a `WebhookResponseTransfer` with a failed response when the 3rd party provider could not process the request.
     * - Returns a `WebhookResponseTransfer` with a successful response when the 3rd party provider was able to process the request.
     *
     * @api
     */
    public function handleWebhook(WebhookRequestTransfer $webhookRequestTransfer, WebhookResponseTransfer $webhookResponseTransfer): WebhookResponseTransfer;

    /**
     * Specification:
     * - Gets a status of the payment
     * - Requires `PaymentStatusRequestTransfer::transactionId`to be set.
     * - Requires `PaymentStatusRequestTransfer::appConfig`to be set.
     * - Returns a `PaymentStatusResponseTransfer`.
     * - Requires `PaymentStatusResponseTransfer::isSuccessful`to be set.
     * - Returns a `PaymentStatusResponseTransfer::isSuccessful` true when the payment was successfully created.
     * - Returns a `PaymentStatusResponseTransfer::isSuccessful` false when the payment was not successfully created.
     *
     * @api
     */
    public function getPaymentStatus(PaymentStatusRequestTransfer $paymentStatusRequestTransfer): PaymentStatusResponseTransfer;
}
