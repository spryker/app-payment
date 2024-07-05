<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace SprykerTest\Glue\AppPaymentBackendApi;

use Codeception\Actor;

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
 */
class AppPaymentBackendApiTester extends Actor
{
    use _generated\AppPaymentBackendApiTesterActions;

    public function seeResponseJsonContainsPayment(): void
    {
        $this->seeResponseJsonPathContains(['data' => ['type' => 'payment']]);
    }
}
