# AppPayment Module
[![Latest Stable Version](https://poser.pugx.org/spryker/app-payment/v/stable.svg)](https://packagist.org/packages/spryker/app-payment)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.2-8892BF.svg)](https://php.net/)

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

- /private/initialize-payment - Used from the Tenant side to initialize a payment.
- /private/confirm-pre-order-payment - Used from the Tenant side to confirm pre-order payment after the order was persisted.

### AppKernel
- \Spryker\Zed\AppPayment\Communication\Plugin\AppKernel\ConfigurePaymentMethodsConfigurationAfterSavePlugin
- \Spryker\Zed\AppPayment\Communication\Plugin\AppKernel\DeleteTenantPaymentsConfigurationAfterDeletePlugin
- \Spryker\Zed\AppPayment\Communication\Plugin\AppKernel\SendAddPaymentMethodMessageConfigurationAfterSavePlugin
- \Spryker\Zed\AppPayment\Communication\Plugin\AppKernel\SendDeletePaymentMethodMessagesConfigurationAfterDeletePlugin

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

## Extensions

- \Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformMarketplacePluginInterface
- \Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPaymentMethodsPluginInterface
- \Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPaymentPagePluginInterface
- \Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface
- \Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPreOrderPluginInterface
- \Spryker\Zed\AppPayment\Dependency\Plugin\PaymentTransmissionsRequestExtenderPluginInterface

### AppPaymentPlatformMarketplacePluginInterface

You can implement this plugin when your PSP App supports Marketplace capabilities.

### AppPaymentPlatformPaymentMethodsPluginInterface

This plugin must be implemented to provide the payment methods that the PSP App supports.

### AppPaymentPlatformPaymentPagePluginInterface

This plugin can be implemented to provide a payment page that the PSP App supports. This is only needed when using the redirect flows after the order was created.

### AppPaymentPlatformPluginInterface

This is the root plugin which must be implemented to provide the PSP App capabilities from the project level.

### AppPaymentPlatformPreOrderPluginInterface

This plugin can be implemented to provide the pre-order capabilities that the PSP App supports.

When using the pre-order payment flow, the InitializePayment API endpoint is used before the order gets persisted and it returns the needed data for an headless approach to add the payment page on project side. Usually, this is done via a provided JavaScript that is send to the frontend in the InitializePayment API call response.

On project side the customer than makes the Payment via the provided JavaScripts and the payment page provided by the PSP provider. After the order is persistzed on the project side a call to the ConfirmPreOrderPayment API endpoint is made to confirm the payment and connect it with the orderReference.

### PaymentTransmissionsRequestExtenderPluginInterface

This plugin can be implemented to extend the request data that is send to the PSP App when doing payouts to Merchants. This is usually only needed when the PSP App supports Marketplace capabilities.

## Configure the MessageBroker

Add this to your app configuration:

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
    UpdatePaymentMethodTransfer::class => 'payment-method-commands',
    DeletePaymentMethodTransfer::class => 'payment-method-commands',
    PaymentCreatedTransfer::class => 'payment-events',
    PaymentUpdatedTransfer::class => 'payment-events',
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
=======
### Configure Payment Methods

Each PSP implementation has different Payment Methods available. Through the `\Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPaymentMethodsPluginInterface` you can provide the available Payment Methods.

Each Payment Method can also have different configuration details.

Add the plugin interface to your implementation and you can configure the payment methods.

#### Payment Method default configuration

This package adds endpoints to be used from the SCOS side to each of the configured Payment Methods. The default configuration for each Payment Method is:

- The base URL - This is the URL that the SCOS side will use to call the PSP App.
- Endpoints - The endpoints that the SCOS side will call on the PSP App.
  - `authorization` - The endpoint to initialize a Payment, `/private/initialize-payment`
  - `pre-order-confirmation` - The endpint to confirm a PreOrder payment,  `/private/confirm-pre-order-payment`
  - `pre-order-cancellation` - The endpint to cancel a PreOrder payment,  `/private/cancel-pre-order-payment`
  - `transfer` - The endpoint to transfer money to a Merchant, `/private/transfers`

These are used on the SCOS side by their names. F.e. when the SCOS side wants to initialize a payment it will call the `authorization` endpoint on the PSP App.

#### Payment Service Provider with only one Payment Method

A simple example for a PSP with only one Payment Method could look like this:

```
public function configurePaymentMethods(PaymentMethodConfigurationRequestTransfer $paymentMethodConfigurationRequestTransfer): PaymentMethodConfigurationResponseTransfer
{
    $paymentMethodConfigurationResponseTransfer = new PaymentMethodConfigurationResponseTransfer();

    $checkoutConfigurationTransfer = new CheckoutConfigurationTransfer();
    $checkoutConfigurationTransfer->setStrategy('embedded');
    $checkoutConfigurationTransfer->setScripts([
        ...
    ]);

    $paymentMethodAppConfigurationTransfer = new PaymentMethodAppConfigurationTransfer();
    $paymentMethodAppConfigurationTransfer
        ->setCheckoutConfiguration($checkoutConfigurationTransfer);

    $paymentMethodTransfer = new PaymentMethodTransfer();
    $paymentMethodTransfer
        ->setName('Foo')
        ->setProviderName('Bar')
        ->setPaymentMethodAppConfiguration($paymentMethodAppConfigurationTransfer);

    $paymentMethodConfigurationResponseTransfer->addPaymentMethod($paymentMethodTransfer);

    return $paymentMethodConfigurationResponseTransfer;
}
```

Here we configure exactly one payment method. The payment method is named "Foo" and the provider is named "Bar". The Payment method also has a configuration that will be persisted on the SCOS side.

The strategy is set to "embedded" which means that the payment page will be embedded in the SCOS checkout. The scripts are the scripts that are needed to embed and run the payment page in the SCOS checkout.

This code runs when the PSP App gets configured. After this method call the so configured methods will be persisted on the App sides database, enriched with default configurations, and via the AddPaymentMethod message sent to the SCOS side.

When the App gets reconfigured and a different number of Payment Methods are configured, the DeletePaymentMethod message will be sent to the SCOS side and the previously configured Payment Methods for the current Tenant will be deleted from the database.

When the PaymentMethod configuration has changed the UpdatePaymentMethod message will be sent to the SCOS side.

#### Payment Service Provider with multiple Payment Methods

In case you have multiple payment methods, you can add multiple PaymentMethodTransfer objects to the PaymentMethodConfigurationResponseTransfer object. For this, you can get the AppConfigTransfer from the PaymentMethodConfigurationRequestTransfer. An example could look like this:

```
public function configurePaymentMethods(PaymentMethodConfigurationRequestTransfer $paymentMethodConfigurationRequestTransfer): PaymentMethodConfigurationResponseTransfer
{
    $appConfigTransfer = $paymentMethodConfigurationRequestTransfer->getAppConfig();

    // Contains ['bar'] which was the only one selected through the configuration page
    $configuredPaymentMethods = $appConfigTransfer->getPaymentMethods();

    // These are all methods you can provide which are configurable through the AppStore Catalogs App configuration page
    $availablePaymentMethods = [
        'foo',
        'bar',
        'baz',
    ];

    $paymentMethodConfigurationResponseTransfer = new PaymentMethodConfigurationResponseTransfer();

    foreeach ($availablePaymentMethods as $paymentMethodName) {
        if (!isset($configuredPaymentMethods[$paymentMethodName])) {
            continue;
        }

        $paymentMethodTransfer = new PaymentMethodTransfer();
        $paymentMethodTransfer
            ->setName($paymentMethodName)
            ->setProviderName('PaymentProviderName');

        $paymentMethodConfigurationResponseTransfer->addPaymentMethod($paymentMethodTransfer);
    }

    return $paymentMethodConfigurationResponseTransfer;
}
```

In this example you would get one PaymentMethod added to SCOS via the AddPaymentMethod message. The PaymentMethod is named "bar" and the provider is named "PaymentProviderName".
