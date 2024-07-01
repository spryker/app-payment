<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\AppPayment\Dependency\Facade;

use Generated\Shared\Transfer\AppConfigCriteriaTransfer;
use Generated\Shared\Transfer\AppConfigResponseTransfer;
use Generated\Shared\Transfer\AppConfigTransfer;
use Spryker\Shared\Kernel\Transfer\TransferInterface;

class AppPaymentToAppKernelFacadeBridge implements AppPaymentToAppKernelFacadeInterface
{
    /**
     * @var \Spryker\Zed\AppKernel\Business\AppKernelFacadeInterface
     */
    protected $appKernelFacade;

    /**
     * @param \Spryker\Zed\AppKernel\Business\AppKernelFacadeInterface $appKernelFacade
     */
    public function __construct($appKernelFacade)
    {
        $this->appKernelFacade = $appKernelFacade;
    }

    public function getConfig(AppConfigCriteriaTransfer $appConfigCriteriaTransfer, AppConfigTransfer $appConfigTransfer): TransferInterface
    {
        return $this->appKernelFacade->getConfig($appConfigCriteriaTransfer, $appConfigTransfer);
    }

    public function saveConfig(AppConfigTransfer $appConfigTransfer): AppConfigResponseTransfer
    {
        return $this->appKernelFacade->saveConfig($appConfigTransfer);
    }
}