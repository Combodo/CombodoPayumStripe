<?php
namespace Combodo\StripeV3\Action\Api;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\RenderTemplate;
use Combodo\StripeV3\Keys;
use Combodo\StripeV3\Request\Api\ObtainToken;
use Stripe\Checkout\Session;
use Stripe\Stripe;

/**
 * @param Keys $keys
 * @param Keys $api
 */
class ObtainTokenAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
{
    use ApiAwareTrait {
        setApi as _setApi;
    }
    use GatewayAwareTrait;

    /**
     * @var string
     */
    protected $templateName;

    /**
     * @deprecated BC will be removed in 2.x. Use $this->api
     *
     * @var Keys
     */
    protected $keys;

    /**
     * @param string $templateName
     */
    public function __construct($templateName)
    {
        $this->templateName = $templateName;

        $this->apiClass = Keys::class;
    }

    /**
     * {@inheritDoc}
     */
    public function setApi($api)
    {
        $this->_setApi($api);

        // BC. will be removed in 2.x
        $this->keys = $this->api;
    }

    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {
        /** @var $request ObtainToken */
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if ($model['card']) {
            throw new LogicException('The token has already been set.');
        }

        $getHttpRequest = new GetHttpRequest();
        $this->gateway->execute($getHttpRequest);
        if ($getHttpRequest->method == 'POST' && isset($getHttpRequest->request['stripeToken'])) {
            $model['card'] = $getHttpRequest->request['stripeToken'];

            return;
        }

        Stripe::setApiKey($this->keys->getSecretKey());

        if (empty($model['session_id'])) {
            $session = Session::create([
                'success_url'           => $request->getToken()->getTargetUrl(), //@TODO : could not find any doc about what to use => check if my guess is good
                'cancel_url'            => $request->getToken()->getAfterUrl(),  //@TODO : could not find any doc about what to use => check if my guess is good
                'payment_method_types'  => ['card'],
                'submit_type'           => Session::SUBMIT_TYPE_PAY,
                'line_items'            => $model['line_items'],
                'client_reference_id'   => $model['id'], //ie the \Sylius\Component\Payment\Model\Payment::id
            ]);

            $model['session_id'] = $session->id;
        }


        $this->gateway->execute($renderTemplate = new RenderTemplate($this->templateName, array(
            'publishable_key'   => $this->keys->getPublishableKey(),
            "session_id"        => $session->id,
        )));


        throw new HttpResponse($renderTemplate->getResult());
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof ObtainToken &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
