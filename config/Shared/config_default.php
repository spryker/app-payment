<?php

/**
 * This configuration is used for TESTING only and will never be used in production!
 */

use Generated\Shared\Transfer\AddPaymentMethodTransfer;
use Generated\Shared\Transfer\AppConfigUpdatedTransfer;
use Generated\Shared\Transfer\CancelPaymentTransfer;
use Generated\Shared\Transfer\CapturePaymentTransfer;
use Generated\Shared\Transfer\DeletePaymentMethodTransfer;
use Generated\Shared\Transfer\PaymentAuthorizationFailedTransfer;
use Generated\Shared\Transfer\PaymentAuthorizedTransfer;
use Generated\Shared\Transfer\PaymentCanceledTransfer;
use Generated\Shared\Transfer\PaymentCancellationFailedTransfer;
use Generated\Shared\Transfer\PaymentCapturedTransfer;
use Generated\Shared\Transfer\PaymentCaptureFailedTransfer;
use Generated\Shared\Transfer\PaymentCreatedTransfer;
use Generated\Shared\Transfer\PaymentOverpaidTransfer;
use Generated\Shared\Transfer\PaymentRefundedTransfer;
use Generated\Shared\Transfer\PaymentRefundFailedTransfer;
use Generated\Shared\Transfer\PaymentUnderpaidTransfer;
use Generated\Shared\Transfer\PaymentUpdatedTransfer;
use Generated\Shared\Transfer\RefundPaymentTransfer;
use Generated\Shared\Transfer\UpdatePaymentMethodTransfer;
use Ramsey\Uuid\Uuid;
use Spryker\Shared\AppKernel\AppKernelConstants;
use Spryker\Shared\Application\ApplicationConstants;
use Spryker\Shared\Application\Log\Config\SprykerLoggerConfig;
use Spryker\Shared\AppPayment\AppPaymentConstants;
use Spryker\Shared\ErrorHandler\ErrorHandlerConstants;
use Spryker\Shared\GlueBackendApiApplication\GlueBackendApiApplicationConstants;
use Spryker\Shared\GlueJsonApiConvention\GlueJsonApiConventionConstants;
use Spryker\Shared\Http\HttpConstants;
use Spryker\Shared\Kernel\KernelConstants;
use Spryker\Shared\Log\LogConstants;
use Spryker\Shared\MessageBroker\MessageBrokerConstants;
use Spryker\Shared\MessageBrokerAws\MessageBrokerAwsConstants;
use Spryker\Shared\Propel\PropelConstants;
use Spryker\Shared\ZedRequest\ZedRequestConstants;
use Spryker\Zed\MessageBrokerAws\MessageBrokerAwsConfig;
use Spryker\Zed\PropelOrm\Business\Builder\ExtensionObjectBuilder;
use Spryker\Zed\PropelOrm\Business\Builder\ExtensionQueryBuilder;
use Spryker\Zed\PropelOrm\Business\Builder\ObjectBuilder;
use Spryker\Zed\PropelOrm\Business\Builder\QueryBuilder;

// ----------------------------------------------------------------------------
// ------------------------------ Glue Backend API ----------------------------
// ----------------------------------------------------------------------------
$config[GlueBackendApiApplicationConstants::GLUE_BACKEND_API_HOST] = 'api.payment.local';

$config[KernelConstants::ENABLE_CONTAINER_OVERRIDING] = true;
$config[KernelConstants::PROJECT_NAMESPACES] =
$config[GlueBackendApiApplicationConstants::PROJECT_NAMESPACES] = [
    'Spryker',
];
$config[ZedRequestConstants::ZED_API_SSL_ENABLED] = (bool)getenv('SPRYKER_ZED_SSL_ENABLED');

$config[ApplicationConstants::BASE_URL_ZED] = sprintf(
    'https://%s',
    'api.payment.local',
);

$config[AppKernelConstants::APP_IDENTIFIER] = Uuid::uuid4()->toString();

$config[HttpConstants::URI_SIGNER_SECRET_KEY] = Uuid::uuid4()->toString();

$config[GlueJsonApiConventionConstants::GLUE_DOMAIN] = sprintf(
    '%s://%s',
    getenv('SPRYKER_SSL_ENABLE') ? 'https' : 'http',
    $config[GlueBackendApiApplicationConstants::GLUE_BACKEND_API_HOST] ?: 'localhost',
);

$config[MessageBrokerConstants::MESSAGE_TO_CHANNEL_MAP] = [
    AppConfigUpdatedTransfer::class => 'app-events',
];

$config[MessageBrokerConstants::CHANNEL_TO_SENDER_TRANSPORT_MAP] = [
    'app-events' => MessageBrokerAwsConfig::HTTP_CHANNEL_TRANSPORT,
];

$connections = [
    'mysql' => [
        'adapter' => 'sqlite',
        'dsn' => 'sqlite:tests/_data/app_payment_db',
        'user' => '',
        'password' => '',
        'settings' => [],
    ],
];

$config[PropelConstants::PROPEL] = [
    'database' => [
        'connections' => [],
    ],
    'runtime' => [
        'defaultConnection' => 'default',
        'connections' => ['default', 'zed'],
    ],
    'generator' => [
        'defaultConnection' => 'default',
        'connections' => ['default', 'zed'],
        'objectModel' => [
            'defaultKeyType' => 'fieldName',
            'builders' => [
                // If you need full entity logging on Create/Update/Delete, then switch to
                // Spryker\Zed\PropelOrm\Business\Builder\ObjectBuilderWithLogger instead.
                'object' => ObjectBuilder::class,
                'objectstub' => ExtensionObjectBuilder::class,
                'query' => QueryBuilder::class,
                'querystub' => ExtensionQueryBuilder::class,
            ],
        ],
    ],
    'paths' => [
        'phpDir' => APPLICATION_ROOT_DIR,
        'sqlDir' => APPLICATION_SOURCE_DIR . '/Orm/Propel/Sql/',
        'migrationDir' => APPLICATION_SOURCE_DIR . '/Orm/Propel/Migration_SQLite/',
        'schemaDir' => APPLICATION_SOURCE_DIR . '/Orm/Propel/Schema/',
    ],
];

$config[PropelConstants::ZED_DB_ENGINE] = 'mysql';
$config[PropelConstants::ZED_DB_HOST] = 'localhost';
$config[PropelConstants::ZED_DB_PORT] = 1234;
$config[PropelConstants::ZED_DB_USERNAME] = 'catface';
$config[PropelConstants::ZED_DB_PASSWORD] = 'catface';

$config[PropelConstants::PROPEL]['database']['connections']['default'] = $connections['mysql'];
$config[PropelConstants::PROPEL]['database']['connections']['zed'] = $connections['mysql'];

$config[KernelConstants::PROJECT_NAMESPACE] = 'Spryker';
$config[KernelConstants::PROJECT_NAMESPACES] = ['Spryker'];
$config[KernelConstants::CORE_NAMESPACES] = ['Spryker'];
$config[KernelConstants::ENABLE_CONTAINER_OVERRIDING] = true;
$config[ErrorHandlerConstants::ERROR_LEVEL] = E_ALL & ~E_DEPRECATED;
$config[LogConstants::LOGGER_CONFIG] = SprykerLoggerConfig::class;
$config[LogConstants::LOG_FILE_PATH] = sys_get_temp_dir() . '/logs';

$config[AppPaymentConstants::APP_IDENTIFIER] = Uuid::uuid4()->toString();

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
    PaymentOverpaidTransfer::class => 'payment-events',
    PaymentUnderpaidTransfer::class => 'payment-events',
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
