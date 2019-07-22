<?php
namespace Combodo\StripeV3\Action;

use Combodo\StripeV3\Constants;
use Combodo\StripeV3\Keys;
use Combodo\StripeV3\Request\handleCheckoutCompletedEvent;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Action\ExecuteSameRequestWithModelDetailsAction;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetBinaryStatus;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\GetToken;
use Payum\Core\Request\Notify;
use Payum\Core\Security\TokenInterface;
use Payum\Core\Storage\StorageInterface;
use Stripe\Event;
use Stripe\Webhook;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class NotifyAction
 * @package Combodo\StripeV3\Action
 *
 * @property Keys $keys
 */
class NotifyUnsafeAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;
    
    use ApiAwareTrait {
        setApi as _setApi;
    }

    public function __construct()
    {
        $this->apiClass     = Keys::class;
    }

    /**
     * {@inheritDoc}
     */
    public function setApi($api)
    {
        $this->_setApi($api);

        // Has more meaning than api since it is just the api keys!
        $this->keys = $this->api;
    }

    /**
     * {@inheritDoc}
     *
     * @param Notify $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $event = $this->obtainStripeEvent();
        $request = new handleCheckoutCompletedEvent($event);
        $this->gateway->execute($request);
    }

    
    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Notify
        ;
    }

    /**
     * @return array
     */
    private function obtainStripeEvent(): Event
    {
        $this->gateway->execute($httpRequest = new GetHttpRequest());


        $endpoint_secret = $this->keys->getEndpointSecretKey();
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $payload = $httpRequest->content;
        $event = null;

        try {
            $tolerance = Webhook::DEFAULT_TOLERANCE;
            $tolerance = 99999; //TODO: remove this tests value!!!!

            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret, $tolerance);
        } catch (\UnexpectedValueException $e) {
            throw new BadRequestHttpException('Invalid payload', null, 400);
        } catch (\Stripe\Error\SignatureVerification $e) {
            throw new BadRequestHttpException('Invalid signature', null, 400);
        }

        // Handle the checkout.session.completed event
        if ($event->type != 'checkout.session.completed') {
            throw new BadRequestHttpException(
                srpintf('Invalid event "%s", only "checkout.session.completed" is supported!', $event->type), null, 400
            );
        }

        return $event;
    }

    
}
