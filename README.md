# AppPayment Package
[![Latest Stable Version](https://poser.pugx.org/spryker/app-payment/v/stable.svg)](https://packagist.org/packages/spryker/app-payment)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.1-8892BF.svg)](https://php.net/)

Provides SyncAPI and AsyncAPI schema files and the needed code to be used in a Payment Service Provider App.

## Installation

```
composer require spryker/app-payment
```

### Tenant-related packages
- `spryker/sales-payment` - For the Payment processing.
- `spryker/sales-payment-merchant` - For the Onboarding, Payout, and Payout reversal processing.
- `spryker/merchant-app` - For the communication related to Merchants between the Tenant and the App.
- `spryker/merchant-app-merchant-portal-gui` - For the displaying of the onboarding process and the onboarding status.

### Testing the AppPayment

You can test the AppPayment as usual with Codeception. Before that you need to run some commands:

```
composer setup
```

With these commands you've set up the AppPayment and can start the tests

```
vendor/bin/codecept build
vendor/bin/codecept run
```

# Documentation

# High-Level Architecture

[<img alt="AppPayment High-Level Architecture" width="auto" src="docs/images/app-payment-high-level-architecture.svg" />](https://docs.spryker.com/)

# Features

## Initialize Payment
This is triggered from the Tenant side when a customer places an order. The request contains all the Tenant's’s known data that is relevant to initialize a payment.

## Capture Payment
This is initiated via the `CapturePayment` message sent from the Tenant. The corresponding `PaymentTransfer` as well as the `AppConfigTransfer` are loaded from the database and passed via the `CapturePaymentRequestTransfer` to the Platform implementation.

## Cancel Payment
This is initiated via the `CancelPayment` message sent from the Tenant. The corresponding `PaymentTransfer` as well as the `AppConfigTransfer` are loaded from the database and passed via the `CancelPaymentRequestTransfer` to the Platform implementation.

## Refund Payment
This is initiated via the `RefundPayment` message sent from the Tenant. The corresponding `PaymentTransfer` as well as the `AppConfigTransfer` are loaded from the database and passed via the `RefundPaymentRequestTransfer` to the Platform implementation.

## Handle Webhooks
Webhooks are piped through this package and extended with the needed Payment or Refund information for this webhook and are forwarded to the Platform implementation.

## Transfer Money
This is known on the Tenant side as Payout or Reverse Payout. This feature will be used together with merchants (when `spryker/app-merchant`) is also used.

# APIs
- `resources/api/asyncapi.yml`
- `resources/api/openapi.yml`

## Configuration

### App Identifier

config/Shared/config_default.php

```
use Spryker\Shared\AppPayment\AppConstants;

$config[AppConstants::APP_IDENTIFIER] = getenv('APP_IDENTIFIER') ?: 'hello-world';
```

### Configure the MessageBroker

Add this to your project configuration:

```
$config[MessageBrokerConstants::MESSAGE_TO_CHANNEL_MAP] =
$config[MessageBrokerAwsConstants::MESSAGE_TO_CHANNEL_MAP] = [
    PaymentAuthorizedTransfer::class => 'payment-events',
    PaymentAuthorizationFailedTransfer::class => 'payment-events',
    PaymentCapturedTransfer::class => 'payment-events',
    PaymentCaptureFailedTransfer::class => 'payment-events',
    PaymentRefundedTransfer::class => 'payment-events',
    PaymentRefundFailedTransfer::class => 'payment-events',
    PaymentCanceledTransfer::class => 'payment-events',
    PaymentCancellationFailedTransfer::class => 'payment-events',
    CancelPaymentTransfer::class => 'payment-commands',
    CapturePaymentTransfer::class => 'payment-commands',
    RefundPaymentTransfer::class => 'payment-commands',
    AddPaymentMethodTransfer::class => 'payment-method-commands',
    DeletePaymentMethodTransfer::class => 'payment-method-commands',
    PaymentCreatedTransfer::class => 'payment-events',
    // App event
    AppConfigUpdatedTransfer::class => 'app-events',
];

$config[MessageBrokerConstants::CHANNEL_TO_TRANSPORT_MAP] = [
    'app-events' => MessageBrokerAwsConfig::HTTP_TRANSPORT,
    'payment-events' => MessageBrokerAwsConfig::HTTP_TRANSPORT,
    'payment-method-commands' => MessageBrokerAwsConfig::HTTP_TRANSPORT,
    'payment-commands' => MessageBrokerAwsConfig::SQS_TRANSPORT,
];

$config[MessageBrokerAwsConstants::CHANNEL_TO_SENDER_TRANSPORT_MAP] = [
    'app-events' => MessageBrokerAwsConfig::HTTP_TRANSPORT,
    'payment-events' => MessageBrokerAwsConfig::HTTP_TRANSPORT,
    'payment-method-commands' => MessageBrokerAwsConfig::HTTP_TRANSPORT,
];

$config[MessageBrokerAwsConstants::CHANNEL_TO_RECEIVER_TRANSPORT_MAP] = [
    'payment-commands' => MessageBrokerAwsConfig::SQS_TRANSPORT,
];
```

## Zed
- `\Spryker\Zed\AppPayment\AppPaymentConfig::getPaymentProviderName()`
- `\Spryker\Zed\AppPayment\AppPaymentConfig::getIsTenantPaymentsDeletionAfterDisconnectionEnabled()`
- `\Spryker\Zed\AppPayment\AppPaymentConfig::getHandleableWebhookTypes()`

### GetPaymentProviderName
This must be overridden on the project level and must return the name of the Payment Provider e.g. Stripe, PayOne, etc

### GetIsTenantPaymentsDeletionAfterDisconnectionEnabled
This can be configured in the `config_default.php` of the App via `AppPaymentConstants::IS_TENANT_PAYMENTS_DELETION_AFTER_DISCONNECTION_ENABLED`. By default, this is false. When you want to delete all payments from the Apps database set this to true and when the App gets disconnected, all Payments of this Tenant will be removed from the database.

### GetHandleableWebhookTypes
The default values of these are

- `payment`
- `refund`

You can add other types in case you need more. The types are defined in `\Spryker\Zed\AppPayment\Business\Payment\Webhook\WebhookDataType` and via the `\Spryker\Glue\AppWebhookBackendApi\Plugin\AppWebhookBackendApi\GlueRequestWebhookMapperPluginInterface` the implementation sets this type to the `WebhookRequestTransfer`.


## Database
This package adds the following database tables to the App

- `spy_payment` - Contains all payments of a Tenant.
- `spy_payment_transfer` - Contains all money transfers.
- `spy_payment_refund` - Contains all refunded payments.

# Plugins
This package provides the following plugins

## Glue
- `\Spryker\Glue\AppPaymentBackendApi\Plugin\GlueApplication\AppPaymentBackendApiRouteProviderPlugin`

### AppPaymentBackendApiRouteProviderPlugin
This plugin must be added to the `\Pyz\Glue\GlueBackendApiApplication\GlueBackendApiApplicationDependencyProvider::getRouteProviderPlugins()`. It adds the following URLs to the App:

- `/payment/initialize`
- `/payments/transfers`

## Zed
- `\Spryker\Zed\AppPayment\Communication\Plugin\AppKernel\DeleteTenantPaymentsConfigurationAfterDeletePlugin`
- `\Spryker\Zed\AppPayment\Communication\Plugin\AppKernel\SendAddPaymentMethodMessageConfigurationAfterSavePlugin`
- `\Spryker\Zed\AppPayment\Communication\Plugin\AppKernel\SendDeletePaymentMethodMessageConfigurationAfterDeletePlugin`
- `\Spryker\Zed\AppPayment\Communication\Plugin\AppWebhook\PaymentWebhookHandlerPlugin`
- `\Spryker\Zed\AppPayment\Communication\Plugin\MessageBroker\CancelPaymentMessageHandlerPlugin`
- `\Spryker\Zed\AppPayment\Communication\Plugin\MessageBroker\CapturePaymentMessageHandlerPlugin`
- `\Spryker\Zed\AppPayment\Communication\Plugin\MessageBroker\RefundPaymentMessageHandlerPlugin`

### DeleteTenantPaymentsConfigurationAfterDeletePlugin
This plugin can be added to the `\Pyz\Zed\AppKernel\AppKernelDependencyProvider::getConfigurationAfterDeletePlugins()` and will drop all payments of a Tenant when configured to do so and when the App gets disconnected.

### SendAddPaymentMethodMessageConfigurationAfterSavePlugin
This plugin can be added to the `\Pyz\Zed\AppKernel\AppKernelDependencyProvider::getConfigurationAfterSavePlugins()` and will send an `AddPaymentMethod` message when the App gets configured. The message is not sent when App is in “disconnected“ state.

Details of the message can be seen in `resources/api/asyncapi.yml`

### SendDeletePaymentMethodMessageConfigurationAfterDeletePlugin
This plugin can be added to the `\Pyz\Zed\AppKernel\AppKernelDependencyProvider::getConfigurationAfterDeletePlugins()` and sends a `DeletePaymentMethod` message when the app gets disconnected.

Details of the message can be seen in `resources/api/asyncapi.yml`

### PaymentWebhookHandlerPlugin
This plugin can be added to the `\Pyz\Zed\AppWebhook\AppWebhookDependencyProvider::getWebhookHandlerPlugins()` and handles all Payment-related webhook requests. It ensures that a transaction ID is passed and forwards the `WebhookRequestTransfer` to the platform implementation. The returned `WebhookResponseTransfer` can be either successful or failed. In a successful case, the response must contain either `WebhookResponseTransfer::PAYMENT_STATUS` or `WebhookResponseTransfer::REFUND_STATUS`. Possible states are defined in the `\Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatus`. Based on the returned status the AppPayment package sends a defined message to the Tenant.

- `PaymentStatus::STATUS_CAPTURED` - Sends a PaymentCaptured message to the Tenant.
- `PaymentStatus::STATUS_CAPTURE_FAILED` - Sends a PaymentCaptureFailed message to the Tenant.
- `PaymentStatus::STATUS_AUTHORIZED` - Sends a PaymentAuthorized message to the Tenant.
- `PaymentStatus::STATUS_AUTHORIZATION_FAILED` - Sends a PaymentAuthorizationFailed message to the Tenant.
- `PaymentRefundStatus::PENDING` - Sends no message to the Tenant this is an initial state and does not need further action.
- `PaymentRefundStatus::SUCCEEDED` - Sends a PaymentRefunded message to the Tenant.
- `PaymentRefundStatus::FAILED` - Sends a PaymentRefundFailed message to the Tenant.

### CancelPaymentMessageHandlerPlugin
This plugin can be added to the `\Pyz\Zed\MessageBroker\MessageBrokerDependencyProvider::getMessageHandlerPlugins()` and when the Tenant sends this message the related payment will be loaded from the database and is forwarded to the platform implementation via the `CancelPaymentRequestTransfer`. The platform implementation will return a `CancelPaymentResponseTransfer` with a status.

- `PaymentStatus::STATUS_CANCELED` - Sends PaymentCanceled message to the Tenant.
- `PaymentStatus::STATUS_CANCELLATION_FAILED` - Sends a `PaymentCancelationFailed` message to the Tenant.

### CapturePaymentMessageHandlerPlugin
This plugin can be added to the `\Pyz\Zed\MessageBroker\MessageBrokerDependencyProvider::getMessageHandlerPlugins()` and when the Tenant sends this message the related payment will be loaded from the database and is forwarded to the platform implementation via the `CapturePaymentRequestTransfer`. The platform implementation will return a `CapturePaymentResponseTransfer` with a status.

- `PaymentStatus::STATUS_CAPTURED` - Sends a `PaymentCaptured` message to the Tenant.
- `PaymentStatus::STATUS_CAPTURE_FAILED` - Sends a `PaymentCaptureFailed` message to the Tenant.

### RefundPaymentMessageHandlerPlugin
This plugin can be added to the `\Pyz\Zed\MessageBroker\MessageBrokerDependencyProvider::getMessageHandlerPlugins()` and when the Tenant sends this message the related payment will be loaded from the database and is forwarded to the platform implementation via the `RefundPaymentRequestTransfer`. The platform implementation will return a `RefundPaymentResponseTransfer` with a status.

- `PaymentRefundStatus::PENDING` - Sends no message to the Tenant this is an initial state and does not need further action.
- `PaymentRefundStatus::SUCCEEDED` - Sends a PaymentRefunded message to the Tenant.
- `PaymentRefundStatus::FAILED` - Sends a PaymentRefundFailed message to the Tenant.


# Extension
This package provides the following extension points

## Zed
- `\Spryker\Zed\AppPayment\Dependency\Plugin\PaymentTransmissionsRequestExtenderPluginInterface`
- `\Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPaymentPagePluginInterface`
- `\Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface`
- `\Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformMarketplacePluginInterface`

### PaymentTransmissionsRequestExtenderPluginInterface
This plugin can be implemented by other packages e.g. to group PaymentTransmissions (Transfer of Money from one account to another account, used e.g. for grouping transfer of money by merchants). The `PaymentTransmissionsRequestTransfer` contains all order-relevant data to be processed.

### AppPaymentPlatformPaymentPagePluginInterface
This plugin is used when a PSP offers a Payment Page which the user gets redirected to after an order is placed. The `PaymentPageRequestTransfer` contains the transaction ID, the `AppConfigTransfer`, and the `PaymentTransfer`. The returned `PaymentPageResponseTransfer` has to contain a `paymentPageTemplate` name and the `paymentPageData` that has to be rendered on this page.

### AppPaymentPlatformPluginInterface
This plugin must be implemented in the PSP App to provide the needed functionality to make payments. The following methods are provided:

- `initializePayment`
- `capturePayment`
- `cancelPayment`
- `refundPayment`
- `handleWebhook`
- `getPaymentStatus`

#### InitializePayment
This is triggered from the Tenant side when a customer places an order. The request contains all the Tenant's’s known data that is relevant to initialize a payment. The platform implementation must return a `InitializePaymentResponseTransfer`. It must provide if the initialization was successful or not, it must return a transaction ID and can return (depending on the storefront implementation) redirectUrl. In failure cases, it can also return a message.

The information is sent back to the Tenant who can act accordingly.

#### CapturePayment
This is initiated via the `CapturePayment` message sent from the Tenant. The corresponding `PaymentTransfer` as well as the `AppConfigTransfer` are loaded from the database and passed via the `CapturePaymentRequestTransfer` to the Platform implementation.

The platform implementation must return a `CapturePaymentResponseTransfer`.  It must provide if the capturing was successful or not and the payment status. In failure cases, it must return a message.

#### CancelPayment
This is initiated via the `CancelPayment` message sent from the Tenant. The corresponding `PaymentTransfer` as well as the `AppConfigTransfer` are loaded from the database and passed via the `CancelPaymentRequestTransfer` to the Platform implementation.

The platform implementation must return a `CancelPaymentResponseTransfer`.  It must provide if the canceling was successful or not and the payment status. In failure cases, it must return a message.

#### RefundPayment
This is initiated via the `RefundPayment` message sent from the Tenant. The corresponding `PaymentTransfer` as well as the `AppConfigTransfer` are loaded from the database and passed via the `RefundPaymentRequestTransfer` to the Platform implementation.

The platform implementation must return a `RefundPaymentResponseTransfer`.  It must provide if the refund was successful or not and the payment status. In failure cases, it must return a message.

#### HandleWebhook
Webhooks are piped through this package and extended with the needed Payment or Refund information for this webhook and are forwarded to the Platform implementation.



### AppPaymentPlatformMarketplacePluginInterface
This plugin must be implemented in the PSP App to provide the needed functionality to do payout and reverse payouts for Marketplaces. The following methods are provided:

- `transferPayments`

#### TransferMoney
This is known on the Tenant side as Payout or Reverse Payout. This feature will be used together with merchants (when spryker/app-merchant) is also used. The process is initiated from the Tenant side and in the request all orderItems and the corresponding order will be passed to the App.

The orderItems will be grouped into `PaymentTransmissionTransfers` and are forwarded to the Platform implementation.

The platform implementation must set the successful state of each transfer and in a failure case, it must add a failure message. The payment transfers are persisted on the App side and a response with all the transmissions successful or failed will be returned in the response to the Tenant.
