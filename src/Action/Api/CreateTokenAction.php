<?php
namespace Combodo\StripeV3\Action\Api;

use Combodo\StripeV3\Action\Api\BaseApiAwareAction;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Combodo\StripeV3\Keys;
use Combodo\StripeV3\Request\Api\CreateToken;
use Stripe\Error;
use Stripe\Stripe;
use Stripe\Token;

class CreateTokenAction extends BaseApiAwareAction
{
    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {
        /** @var $request CreateToken */
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        try {
            Stripe::setApiKey($this->api->getSecretKey());

            $token = Token::create($model->toUnsafeArrayWithoutLocal());

            $model->replace($token->__toArray(true));
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
            $request instanceof CreateToken &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
