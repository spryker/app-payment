<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Business;

use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Business\Customer\Customer;
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
use Spryker\Zed\AppPayment\Business\Payment\Message\PaymentMethodMessageSender;
use Spryker\Zed\AppPayment\Business\Payment\Method\Normalizer\PaymentMethodNormalizer;
use Spryker\Zed\AppPayment\Business\Payment\Method\PaymentMethod;
use Spryker\Zed\AppPayment\Business\Payment\Method\Reader\PaymentMethodReader;
use Spryker\Zed\AppPayment\Business\Payment\Page\PaymentPage;
use Spryker\Zed\AppPayment\Business\Payment\Payment;
use Spryker\Zed\AppPayment\Business\Payment\PreOrder\PaymentPreOrder;
use Spryker\Zed\AppPayment\Business\Payment\Refund\PaymentRefunder;
use Spryker\Zed\AppPayment\Business\Payment\Refund\PaymentRefundValidator;
use Spryker\Zed\AppPayment\Business\Payment\Status\PaymentStatusTransitionValidator;
use Spryker\Zed\AppPayment\Business\Payment\Transfer\PaymentTransfer;
use Spryker\Zed\AppPayment\Business\Payment\Webhook\Handler\PaymentRefundWebhookHandler;
use Spryker\Zed\AppPayment\Business\Payment\Webhook\Handler\PaymentWebhookHandler;
use Spryker\Zed\AppPayment\Business\Payment\Webhook\Handler\WebhookHandlerSelector;
use Spryker\Zed\AppPayment\Business\Payment\Webhook\WebhookHandler;
use Spryker\Zed\AppPayment\Business\Payment\Webhook\WebhookMessageSender;
use Spryker\Zed\AppPayment\Business\Payment\Webhook\WebhookRequestExtender;
use Spryker\Zed\AppPayment\Business\Payment\Writer\PaymentWriter;
use Spryker\Zed\AppPayment\Business\Payment\Writer\PaymentWriterInterface;
use Spryker\Zed\AppPayment\Business\Redirect\Redirect;
use Spryker\Zed\AppPayment\Dependency\Facade\AppPaymentToAppKernelFacadeInterface;
use Spryker\Zed\AppPayment\Dependency\Facade\AppPaymentToAppWebhookFacadeInterface;
use Spryker\Zed\AppPayment\Dependency\Facade\AppPaymentToMessageBrokerFacadeInterface;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;
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
            $this->createPaymentInitializer(),
            $this->createPaymentPreOrder(),
            $this->createPaymentTransfer(),
            $this->createPaymentPage(),
            $this->createPaymentMethodReader(),
            $this->createCustomer(),
            $this->createWebhookHandler(),
        );
    }

    public function createPaymentInitializer(): PaymentInitializer
    {
        return new PaymentInitializer($this->getPlatformPlugin(), $this->getEntityManager(), $this->getRepository(), $this->createPaymentWriter(), $this->createPaymentMethodNormalizer(), $this->createMessageSender(), $this->getConfig(), $this->createAppConfigLoader());
    }

    public function createPaymentPreOrder(): PaymentPreOrder
    {
        return new PaymentPreOrder($this->getPlatformPlugin(), $this->getRepository(), $this->createPaymentWriter(), $this->getAppWebhookFacade(), $this->createMessageSender(), $this->getConfig(), $this->createAppConfigLoader());
    }

    public function createPaymentTransfer(): PaymentTransfer
    {
        return new PaymentTransfer(
            $this->getPlatformPlugin(),
            $this->getEntityManager(),
            $this->getRepository(),
            $this->getConfig(),
            $this->createAppConfigLoader(),
            $this->getPaymentTransmissionsRequestExtenderPlugins(),
        );
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
            $this->createPaymentWriter(),
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

    /**
     * @return array<\Spryker\Zed\AppPayment\Dependency\Plugin\PaymentTransmissionsRequestExtenderPluginInterface>
     */
    protected function getPaymentTransmissionsRequestExtenderPlugins(): array
    {
        /** @phpstan-var array<\Spryker\Zed\AppPayment\Dependency\Plugin\PaymentTransmissionsRequestExtenderPluginInterface> */
        return $this->getProvidedDependency(AppPaymentDependencyProvider::PLUGINS_PAYMENTS_TRANSMISSIONS_REQUEST_EXPANDER);
    }

    public function getPlatformPlugin(): AppPaymentPlatformPluginInterface
    {
        /** @phpstan-var \Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface */
        return $this->getProvidedDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM);
    }

    public function createPaymentMethodNormalizer(): PaymentMethodNormalizer
    {
        return new PaymentMethodNormalizer();
    }

    public function createMessageSender(): MessageSender
    {
        return new MessageSender($this->getConfig(), $this->getMessageBrokerFacade());
    }

    public function createPaymentMethod(): PaymentMethod
    {
        return new PaymentMethod($this->getPlatformPlugin(), $this->getConfig(), $this->createPaymentMethodMessageSender(), $this->getRepository());
    }

    public function createPaymentMethodMessageSender(): PaymentMethodMessageSender
    {
        return new PaymentMethodMessageSender($this->getConfig(), $this->getMessageBrokerFacade(), $this->getPlatformPlugin());
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

    public function getAppWebhookFacade(): AppPaymentToAppWebhookFacadeInterface
    {
        /** @phpstan-var \Spryker\Zed\AppPayment\Dependency\Facade\AppPaymentToAppWebhookFacadeInterface */
        return $this->getProvidedDependency(AppPaymentDependencyProvider::FACADE_APP_WEBHOOK);
    }

    public function createCancelPaymentMessageHandler(): CancelPaymentMessageHandlerInterface
    {
        return new CancelPaymentMessageHandler(
            $this->getRepository(),
            $this->createTenantIdentifierExtractor(),
            $this->getAppKernelFacade(),
            $this->createCancelPayment(),
            $this->createMessageSender(),
        );
    }

    public function createCancelPayment(): CancelPayment
    {
        return new CancelPayment(
            $this->getPlatformPlugin(),
            $this->createPaymentStatusTransitionValidator(),
            $this->createPaymentWriter(),
            $this->getConfig(),
            $this->createAppConfigLoader(),
        );
    }

    public function createCapturePaymentMessageHandler(): CapturePaymentMessageHandlerInterface
    {
        return new CapturePaymentMessageHandler(
            $this->getRepository(),
            $this->createTenantIdentifierExtractor(),
            $this->getAppKernelFacade(),
            $this->createPaymentCapturer(),
            $this->createMessageSender(),
        );
    }

    public function createRefundPaymentMessageHandler(): RefundPaymentMessageHandlerInterface
    {
        return new RefundPaymentMessageHandler(
            $this->getRepository(),
            $this->createTenantIdentifierExtractor(),
            $this->getAppKernelFacade(),
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
            $this->getRepository(),
            $this->createPaymentWriter(),
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
        return new PaymentCapturer($this->getPlatformPlugin(), $this->createPaymentWriter(), $this->getConfig(), $this->createAppConfigLoader());
    }

    public function createRedirect(): Redirect
    {
        return new Redirect($this->getPlatformPlugin(), $this->getRepository(), $this->getConfig(), $this->createAppConfigLoader());
    }

    public function createPaymentWriter(): PaymentWriterInterface
    {
        return new PaymentWriter($this->getEntityManager(), $this->getRepository(), $this->createMessageSender());
    }

    public function createPaymentMethodReader(): PaymentMethodReader
    {
        return new PaymentMethodReader($this->getRepository(), $this->createPaymentMethodNormalizer());
    }

    public function createCustomer(): Customer
    {
        return new Customer($this->getPlatformPlugin(), $this->createAppConfigLoader());
    }
}
