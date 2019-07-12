<?php
namespace Combodo\StripeV3\Action\Api;

use Combodo\StripeV3\Action\Api\BaseApiAwareAction;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareTrait;
use Combodo\StripeV3\Keys;
use Combodo\StripeV3\Request\Api\CreateCharge;
use Stripe\Charge;
use Stripe\Error;
use Stripe\Stripe;

class CreateChargeAction extends BaseApiAwareAction
{
    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {
        /** @var $request CreateCharge */
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if (false == ($model['card'] || $model['customer'])) {
            throw new LogicException('The either card token or customer id has to be set.');
        }

        if (is_array($model['card'])) {
            throw new LogicException('The token has already been used.');
        }

        try {
            Stripe::setApiKey($this->api->getSecretKey());

            $charge = Charge::create($model->toUnsafeArrayWithoutLocal());

            $model->replace($charge->__toArray(true));
        } catch (Error\Base $e) {
            $model->replace($e->getJsonBody());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof CreateCharge &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
