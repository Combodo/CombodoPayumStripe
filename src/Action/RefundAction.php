<?php
namespace Combodo\StripeV3\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Combodo\StripeV3\Keys;
use Payum\Core\Request\Refund;
use Stripe\Error;
use Stripe\Stripe;

class RefundAction implements ActionInterface, ApiAwareInterface
{
    /**
     * @var Keys
     */
    protected $keys;

    /**
     * {@inheritDoc}
     */
    public function setApi($api)
    {
        if (false == $api instanceof Keys) {
            throw new UnsupportedApiException('Not supported.');
        }

        $this->keys = $api;
    }

    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {
        /** @var $request Refund */
        RequestNotSupportedException::assertSupports($this, $request);
        $model = ArrayObject::ensureArrayObject($request->getModel());
        if (!@$model['payment_intent_id']) {
            throw new LogicException('The payment intent id id has to be set.');
        }
        if (isset($model['amount'])
            && (!is_numeric($model['amount']) || $model['amount'] <= 0)
        ) {
            throw new LogicException('The amount is invalid.');
        }
        try {
            $this->refund($model);
            $model['refunded'] = true;
        } catch (Error\Base $e) {
            $model->replace([$e->getJsonBody()]);
        }
    }

    /**
     * @param \ArrayAccess $model
     *
     * @return \Stripe\PaymentIntent
     */
    private function refund(\ArrayAccess $model)
    {
        Stripe::setApiKey($this->keys->getSecretKey());
        $intent = \Stripe\PaymentIntent::retrieve($model['payment_intent_id']);
        $intent->charges->data[0]->refund();
        return $intent;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Refund &&
            $request->getModel() instanceof \ArrayAccess
            ;
    }
}
