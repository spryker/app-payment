<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Communication\Plugin\AppKernel;

use Generated\Shared\Transfer\AppConfigTransfer;
use Spryker\Zed\AppKernelExtension\Dependency\Plugin\ConfigurationAfterDeletePluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * {@inheritDoc}
 *
 * @deprecated Use `\Spryker\Zed\AppPayment\Communication\Plugin\AppKernel\SendDeletePaymentMethodMessagesConfigurationAfterDeletePlugin` instead.
 *
 * @api
 *
 * @method \Spryker\Zed\AppPayment\AppPaymentConfig getConfig()
 * @method \Spryker\Zed\AppPayment\Business\AppPaymentFacadeInterface getFacade()
 * @method \Spryker\Zed\AppPayment\Business\AppPaymentBusinessFactory getFactory()
 */
class SendDeletePaymentMethodMessageConfigurationAfterDeletePlugin extends AbstractPlugin implements ConfigurationAfterDeletePluginInterface
{
    public function afterDelete(AppConfigTransfer $appConfigTransfer): AppConfigTransfer
    {
        return $this->getFacade()->sendDeletePaymentMethodMessage($appConfigTransfer);
    }
}
