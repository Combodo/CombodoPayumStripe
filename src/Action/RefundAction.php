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
use Stripe\Charge as Stripe_Charge;
use Stripe\Refund as StripeRefund;
use Stripe\Error;
use Stripe\Stripe;
use Combodo\StripeV3\Constants;

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
        if (!@$model['session_id']) {
            throw new LogicException('The session id has to be set.');
        }

        if (isset($model['amount'])
            && (!is_numeric($model['amount']) || $model['amount'] <= 0)
        ) {
            throw new LogicException('The amount is invalid.');
        }

        try {
            Stripe::setApiKey($this->keys->getSecretKey());
            $session = \Stripe\Checkout\Session::retrieve($model['session_id']);
            $intent = \Stripe\PaymentIntent::retrieve($session->payment_intent);
            $intent->charges->data[0]->refund();
            $refund = $model['refunded'] = true;
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
            $request instanceof Refund &&
            $request->getModel() instanceof \ArrayAccess
            ;
    }
}
