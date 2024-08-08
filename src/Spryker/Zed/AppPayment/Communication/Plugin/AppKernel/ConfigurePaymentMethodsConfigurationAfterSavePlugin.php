<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Communication\Plugin\AppKernel;

use Generated\Shared\Transfer\AppConfigTransfer;
use Spryker\Zed\AppKernelExtension\Dependency\Plugin\ConfigurationAfterSavePluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * {@inheritDoc}
 *
 * @api
 *
 * @method \Spryker\Zed\AppPayment\AppPaymentConfig getConfig()
 * @method \Spryker\Zed\AppPayment\Business\AppPaymentFacadeInterface getFacade()
 * @method \Spryker\Zed\AppPayment\Business\AppPaymentBusinessFactory getFactory()
 */
class ConfigurePaymentMethodsConfigurationAfterSavePlugin extends AbstractPlugin implements ConfigurationAfterSavePluginInterface
{
    public function afterSave(AppConfigTransfer $appConfigTransfer): AppConfigTransfer
    {
        return $this->getFacade()->configurePaymentMethods($appConfigTransfer);
    }
}
