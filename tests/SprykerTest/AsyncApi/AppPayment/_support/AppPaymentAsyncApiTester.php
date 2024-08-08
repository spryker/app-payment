<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SprykerTest\AsyncApi\AppPayment;

use Codeception\Actor;
use Codeception\Stub;
use Generated\Shared\Transfer\WebhookResponseTransfer;
use Spryker\Zed\AppPayment\AppPaymentDependencyProvider;
use Spryker\Zed\AppPayment\Dependency\Plugin\AppPaymentPlatformPluginInterface;

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
 *
 * @method \Spryker\Zed\AppPayment\Business\AppPaymentFacadeInterface getFacade()
 */
class AppPaymentAsyncApiTester extends Actor
{
    use _generated\AppPaymentAsyncApiTesterActions;

    public function mockPlatformPlugin(string $status): void
    {
        $platformPluginMock = Stub::makeEmpty(AppPaymentPlatformPluginInterface::class, [
            'handleWebhook' => function () use ($status): WebhookResponseTransfer {
                $webhookResponseTransfer = new WebhookResponseTransfer();
                $webhookResponseTransfer->setIsSuccessful(true);
                $webhookResponseTransfer->setPaymentStatus($status);

                return $webhookResponseTransfer;
            },
        ]);

        $this->setDependency(AppPaymentDependencyProvider::PLUGIN_PLATFORM, $platformPluginMock);
    }
}
