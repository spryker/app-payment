<?php

/**
 * This file is part of the Spryker Suite.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Glue\AppPaymentBackendApi\Plugin\GlueApplication;

use Spryker\Glue\AppPaymentBackendApi\Controller\ConfirmPreOrderPaymentResourceController;
use Spryker\Glue\AppPaymentBackendApi\Controller\InitializePaymentResourceController;
use Spryker\Glue\AppPaymentBackendApi\Controller\PaymentsTransfersResourceController;
use Spryker\Glue\GlueApplicationExtension\Dependency\Plugin\RouteProviderPluginInterface;
use Spryker\Glue\Kernel\Backend\AbstractPlugin;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @codeCoverageIgnore This class will only be used when caching is disabled. Without this Plugin the InitializePayment request would fail and we would see an issue right away.
 */
class AppPaymentBackendApiRouteProviderPlugin extends AbstractPlugin implements RouteProviderPluginInterface
{
    public function addRoutes(RouteCollection $routeCollection): RouteCollection
    {
        $routeCollection->add('postPayment', $this->getPostPaymentRoute());
        $routeCollection->add('postConfirmPreOrderPayment', $this->getPostConfirmPreOrderPaymentRoute());
        $routeCollection->add('postPaymentsTransfers', $this->getPostPaymentsTransfersRoute());

        return $routeCollection;
    }

    public function getPostPaymentRoute(): Route
    {
        return (new Route('/private/initialize-payment'))
            ->setDefaults([
                '_controller' => [InitializePaymentResourceController::class, 'postAction'],
                '_resourceName' => 'Payment',
                '_method' => 'post',
            ])
            ->setMethods(Request::METHOD_POST);
    }

    public function getPostConfirmPreOrderPaymentRoute(): Route
    {
        return (new Route('/private/confirm-pre-order-payment'))
            ->setDefaults([
                '_controller' => [ConfirmPreOrderPaymentResourceController::class, 'postAction'],
                '_resourceName' => 'Payment',
                '_method' => 'post',
            ])
            ->setMethods(Request::METHOD_POST);
    }

    public function getPostPaymentsTransfersRoute(): Route
    {
        return (new Route('/private/payments/transfers'))
            ->setDefaults([
                '_controller' => [PaymentsTransfersResourceController::class, 'postAction'],
                '_resourceName' => 'payments-transfers',
                '_method' => 'post',
            ])
            ->setMethods(Request::METHOD_POST);
    }
}
