<?php
namespace Combodo\StripeV3\Action;

use Combodo\StripeV3\Request\Api\CheckPayment;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Capture;
use Payum\Core\Exception\RequestNotSupportedException;
use Combodo\StripeV3\Request\Api\CreateCharge;
use Combodo\StripeV3\Request\Api\ObtainToken;
use Payum\Core\Request\GetHttpRequest;
use Stripe\Stripe;

class CaptureAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * {@inheritDoc}
     *
     * @param Capture $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());
        $getHttpRequest = new GetHttpRequest();
        $this->gateway->execute($getHttpRequest);
        if ($getHttpRequest->method == 'GET' && isset($getHttpRequest->query['status'])) {
            $obtainToken = new CheckPayment($request->getToken());
            $obtainToken->setModel($model);
            $this->gateway->execute($obtainToken);
            return;
        }
        if ($model['status']) {
            return;
        }

        if ($model['customer']) {
            return;
        }

        $obtainToken = new ObtainToken($request->getToken());
        $obtainToken->setModel($model);

        $this->gateway->execute($obtainToken);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
