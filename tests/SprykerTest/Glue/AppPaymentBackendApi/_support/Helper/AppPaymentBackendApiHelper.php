<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace SprykerTest\Glue\AppPaymentBackendApi\Helper;

use Codeception\Module;
use Generated\Shared\Transfer\InitializePaymentResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Orm\Zed\AppPayment\Persistence\SpyPaymentQuery;
use Spryker\Shared\Config\Config;
use Spryker\Shared\GlueBackendApiApplication\GlueBackendApiApplicationConstants;
use Spryker\Shared\ZedRequest\ZedRequestConstants;
use Spryker\Zed\AppPayment\Persistence\AppPaymentRepository;
use SprykerTest\Shared\Testify\Helper\DependencyHelperTrait;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;
use Symfony\Component\HttpFoundation\Response;

class AppPaymentBackendApiHelper extends Module
{
    use LocatorHelperTrait;
    use DependencyHelperTrait;

    public function buildPaymentUrl(): string
    {
        return $this->buildBackendApiUrl('private/initialize-payment');
    }

    public function buildWebhookUrl(): string
    {
        return $this->buildBackendApiUrl('webhooks');
    }

    public function buildPaymentsTransfersUrl(): string
    {
        return $this->buildBackendApiUrl('private/payments/transfers');
    }

    public function buildConfirmPreOrderPaymentUrl(): string
    {
        return $this->buildBackendApiUrl('private/confirm-pre-order-payment');
    }

    public function buildCancelPreOrderPaymentUrl(): string
    {
        return $this->buildBackendApiUrl('private/cancel-pre-order-payment');
    }

    public function buildCustomerUrl(): string
    {
        return $this->buildBackendApiUrl('private/customer');
    }

    /**
     * @param array<mixed>|string $params
     */
    protected function buildBackendApiUrl(string $url, array $params = []): string
    {
        $url = sprintf('%s://%s/%s', Config::get(ZedRequestConstants::ZED_API_SSL_ENABLED) ? 'https' : 'http', Config::get(GlueBackendApiApplicationConstants::GLUE_BACKEND_API_HOST), $this->formatUrl($url, $params));

        return rtrim($url, '/');
    }

    /**
     * @param array<mixed>|string $params
     */
    protected function formatUrl(string $url, array $params): string
    {
        $refinedParams = [];
        foreach ($params as $key => $value) {
            $refinedParams['{' . $key . '}'] = urlencode($value);
        }

        return strtr($url, $refinedParams);
    }

    public function assertPaymentWithTransactionIdExists(string $transactionId): void
    {
        $spyPaymentQuery = new SpyPaymentQuery();
        $paymentEntity = $spyPaymentQuery->findOneByTransactionId($transactionId);

        $this->assertNotNull($paymentEntity, sprintf('Expected to find a persisted payment with transaction id "%s" but it was not found in the database', $transactionId));
    }

    public function assertPaymentWithTransactionIdDoesNotExists(string $transactionId): void
    {
        $spyPaymentQuery = new SpyPaymentQuery();
        $paymentEntity = $spyPaymentQuery->findOneByTransactionId($transactionId);

        $this->assertNull($paymentEntity, sprintf('Expected not to find a persisted payment with transaction id "%s" but it was found in the database', $transactionId));
    }

    public function assertSamePaymentQuoteAndRequestQuote(string $transactionId, QuoteTransfer $quoteTransfer): void
    {
        $paymentRepository = new AppPaymentRepository();
        $paymentTransfer = $paymentRepository->getPaymentByTransactionId($transactionId);

        $this->assertNotNull($paymentTransfer, sprintf('Expected to find a persisted payment with transaction id "%s" but it was not found in the database', $transactionId));

        $this->assertSame($quoteTransfer->toArray(), $paymentTransfer->getQuote()->toArray(), 'Expected that the persisted quote is the same as the one from the authorize request.');
    }

    public function assertResponseHasErrorMessage(Response $response, string $errorMessage): void
    {
        $response = json_decode($response->getContent(), true);

        $initializePaymentResponseTransfer = (new InitializePaymentResponseTransfer())->fromArray($response);

        $this->assertIsString($initializePaymentResponseTransfer->getMessage());
        $this->assertSame($errorMessage, $initializePaymentResponseTransfer->getMessage());
    }
}
