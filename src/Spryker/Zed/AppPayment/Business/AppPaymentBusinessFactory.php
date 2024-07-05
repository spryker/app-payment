<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business;

use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Business\MessageBroker\CancelPaymentMessageHandler;
use Spryker\Zed\AppPayment\Business\MessageBroker\CancelPaymentMessageHandlerInterface;
use Spryker\Zed\AppPayment\Business\MessageBroker\CapturePaymentMessageHandler;
use Spryker\Zed\AppPayment\Business\MessageBroker\CapturePaymentMessageHandlerInterface;
use Spryker\Zed\AppPayment\Business\MessageBroker\RefundPaymentMessageHandler;
use Spryker\Zed\AppPayment\Business\MessageBroker\RefundPaymentMessageHandlerInterface;
use Spryker\Zed\AppPayment\Business\MessageBroker\TenantIdentifier\TenantIdentifierExtractor;
use Spryker\Zed\AppPayment\Business\Payment\AppConfig\AppConfigLoader;
use Spryker\Zed\AppPayment\Business\Payment\Cancel\CancelPayment;
use Spryker\Zed\AppPayment\Business\Payment\Capture\PaymentCapturer;
use Spryker\Zed\AppPayment\Business\Payment\Initialize\PaymentInitializer;
use Spryker\Zed\AppPayment\Business\Payment\Message\MessageSender;
use Spryker\Zed\AppPayment\Business\Payment\Page\PaymentPage;
use Spryker\Zed\AppPayment\Business\Payment\Payment;
use Spryker\Zed\AppPayment\Business\Payment\Refund\PaymentRefunder;
use Spryker\Zed\AppPayment\Business\Payment\Refund\PaymentRefundValidator;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatusTransitionValidator;
use Spryker\Zed\AppPayment\Business\Payment\Validate\ConfigurationValidator;
use Spryker\Zed\AppPayment\Business\Payment\Webhook\Handler\PaymentRefundWebhookHandler;
use Spryker\Zed\AppPayment\Business\Payment\Webhook\Handler\PaymentWebhookHandler;
use Spryker\Zed\AppPayment\Business\Payment\Webhook\Handler\WebhookHandlerSelector;
use Spryker\Zed\AppPayment\Business\Payment\Webhook\WebhookHandler;
use Spryker\Zed\AppPayment\Business\Payment\Webhook\WebhookMessageSender;
use Spryker\Zed\AppPayment\Business\Payment\Webhook\WebhookRequestExtender;
use Spryker\Zed\AppPayment\Business\Redirect\Redirect;
use Spryker\Zed\AppPayment\Dependency\Facade\AppPaymentToAppKernelFacadeInterface;
use Spryker\Zed\AppPayment\Dependency\Facade\AppPaymentToMessageBrokerFacadeInterface;
use Spryker\Zed\AppPayment\Dependency\Plugin\PlatformPluginInterface;
use Spryker\Zed\AppPayment\Dependency\Service\AppPaymentToUtilEncodingServiceInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;

/**
 * @method \Spryker\Zed\AppPayment\AppPaymentConfig getConfig()
 * @method \Spryker\Zed\AppPayment\Persistence\AppPaymentEntityManagerInterface getEntityManager()
 * @method \Spryker\Zed\AppPayment\Persistence\AppPaymentRepositoryInterface getRepository()
 */
class AppPaymentBusinessFactory extends AbstractBusinessFactory
{
    public function createPayment(): Payment
    {
        return new Payment(
            $this->getPlatformPlugin(),
            $this->createConfigurationValidator(),
            $this->createPaymentInitializer(),
            $this->createPaymentPage(),
            $this->createWebhookHandler(),
        );
    }

    public function createConfigurationValidator(): ConfigurationValidator
    {
        return new ConfigurationValidator($this->getPlatformPlugin(), $this->getUtilEncodingService());
    }

    public function createPaymentInitializer(): PaymentInitializer
    {
        return new PaymentInitializer($this->getPlatformPlugin(), $this->getEntityManager(), $this->createMessageSender(), $this->getConfig(), $this->createAppConfigLoader());
    }

    public function createPaymentPage(): PaymentPage
    {
        return new PaymentPage($this->getPlatformPlugin(), $this->getRepository(), $this->createAppConfigLoader(), $this->getConfig());
    }

    public function createWebhookHandler(): WebhookHandler
    {
        return new WebhookHandler(
            $this->getPlatformPlugin(),
            $this->createWebhookRequestExtender(),
            $this->createWebhookMessageSender(),
            $this->createWebhookHandlerSelector(),
        );
    }

    public function createWebhookRequestExtender(): WebhookRequestExtender
    {
        return new WebhookRequestExtender(
            $this->getPlatformPlugin(),
            $this->createAppConfigLoader(),
            $this->getRepository(),
        );
    }

    public function createWebhookHandlerSelector(): WebhookHandlerSelector
    {
        return new WebhookHandlerSelector(
            $this->createPaymentWebhookHandler(),
            $this->createPaymentRefundWebhookHandler(),
        );
    }

    public function createPaymentWebhookHandler(): PaymentWebhookHandler
    {
        return new PaymentWebhookHandler(
            $this->getEntityManager(),
            $this->createPaymentStatusTransitionValidator(),
        );
    }

    public function createPaymentRefundWebhookHandler(): PaymentRefundWebhookHandler
    {
        return new PaymentRefundWebhookHandler(
            $this->getEntityManager(),
        );
    }

    public function createPaymentStatusTransitionValidator(): PaymentStatusTransitionValidator
    {
        return new PaymentStatusTransitionValidator();
    }

    public function createWebhookMessageSender(): WebhookMessageSender
    {
        return new WebhookMessageSender($this->createMessageSender());
    }

    public function getPlatformPlugin(): PlatformPluginInterface
    {
        /** @phpstan-var \Spryker\Zed\AppPayment\Dependency\Plugin\PlatformPluginInterface */
        return $this->getProvidedDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM);
    }

    public function createMessageSender(): MessageSender
    {
        return new MessageSender($this->getMessageBrokerFacade(), $this->getConfig(), $this->getAppKernelFacade());
    }

    public function createAppConfigLoader(): AppConfigLoader
    {
        return new AppConfigLoader($this->getAppKernelFacade());
    }

    public function getAppKernelFacade(): AppPaymentToAppKernelFacadeInterface
    {
        /** @phpstan-var \Spryker\Zed\AppPayment\Dependency\Facade\AppPaymentToAppKernelFacadeInterface */
        return $this->getProvidedDependency(AppPaymentDependencyProvider::FACADE_APP_KERNEL);
    }

    public function getMessageBrokerFacade(): AppPaymentToMessageBrokerFacadeInterface
    {
        /** @phpstan-var \Spryker\Zed\AppPayment\Dependency\Facade\AppPaymentToMessageBrokerFacadeInterface */
        return $this->getProvidedDependency(AppPaymentDependencyProvider::FACADE_MESSAGE_BROKER);
    }

    public function getUtilEncodingService(): AppPaymentToUtilEncodingServiceInterface
    {
        /** @phpstan-var \Spryker\Zed\AppPayment\Dependency\Service\AppPaymentToUtilEncodingServiceInterface */
        return $this->getProvidedDependency(AppPaymentDependencyProvider::SERVICE_UTIL_ENCODING);
    }

    public function createCancelPaymentMessageHandler(): CancelPaymentMessageHandlerInterface
    {
        return new CancelPaymentMessageHandler(
            $this->getRepository(),
            $this->createTenantIdentifierExtractor(),
            $this->createCancelPayment(),
            $this->createMessageSender(),
        );
    }

    public function createCancelPayment(): CancelPayment
    {
        return new CancelPayment(
            $this->getPlatformPlugin(),
            $this->createPaymentStatusTransitionValidator(),
            $this->getEntityManager(),
            $this->getConfig(),
            $this->createAppConfigLoader(),
        );
    }

    public function createCapturePaymentMessageHandler(): CapturePaymentMessageHandlerInterface
    {
        return new CapturePaymentMessageHandler(
            $this->getRepository(),
            $this->createTenantIdentifierExtractor(),
            $this->createPaymentCapturer(),
            $this->createMessageSender(),
        );
    }

    public function createRefundPaymentMessageHandler(): RefundPaymentMessageHandlerInterface
    {
        return new RefundPaymentMessageHandler(
            $this->getRepository(),
            $this->createTenantIdentifierExtractor(),
            $this->createPaymentRefunder(),
            $this->createMessageSender(),
        );
    }

    public function createPaymentRefunder(): PaymentRefunder
    {
        return new PaymentRefunder(
            $this->getPlatformPlugin(),
            $this->createPaymentRefundValidator(),
            $this->getEntityManager(),
            $this->getConfig(),
            $this->createAppConfigLoader(),
        );
    }

    public function createPaymentRefundValidator(): PaymentRefundValidator
    {
        return new PaymentRefundValidator(
            $this->createPaymentStatusTransitionValidator(),
            $this->getRepository(),
        );
    }

    public function createTenantIdentifierExtractor(): TenantIdentifierExtractor
    {
        return new TenantIdentifierExtractor();
    }

    public function createPaymentCapturer(): PaymentCapturer
    {
        return new PaymentCapturer($this->getPlatformPlugin(), $this->getEntityManager(), $this->getConfig(), $this->createAppConfigLoader());
    }

    public function createRedirect(): Redirect
    {
        return new Redirect($this->getPlatformPlugin(), $this->getRepository(), $this->getConfig(), $this->createAppConfigLoader());
    }
}
