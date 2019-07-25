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
        $this->checkStripeEventType($event);
        $this->handleStripeEvent($event);
    }

    
    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Notify &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }

    /**
     * @return array
     */
    private function obtainStripeEvent(): Event
    {
        $this->gateway->execute($httpRequest = new GetHttpRequest());


        if (empty($_SERVER['HTTP_STRIPE_SIGNATURE'])) {
            throw new LogicException('The stripe signature is mandatory', 400);
        }
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $endpoint_secret = $this->keys->getEndpointSecretKey();
        $payload = $httpRequest->content;
        $event = null;

        try {
            $tolerance = Webhook::DEFAULT_TOLERANCE;
//            $tolerance = 99999; //TODO: remove this tests value!!!!

            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret, $tolerance);
        } catch (\UnexpectedValueException $e) {
            throw new LogicException('Invalid payload', 400);
        } catch (\Stripe\Error\SignatureVerification $e) {
            throw new LogicException('Invalid signature', 400);
        }


        return $event;
    }

    /**
     * @param $request
     * @param $event
     */
    private function handleStripeEvent(Event $event): void
    {
        $request = new handleCheckoutCompletedEvent($event, handleCheckoutCompletedEvent::TOKEN_MUST_BE_KEPT);
        $this->gateway->execute($request);
    }

    /**
     * accept only the checkout.session.completed event
     * @param Event $event
     */
    private function checkStripeEventType(Event $event): void
    {
        if ($event->type != 'checkout.session.completed') {
            throw new LogicException(
                sprintf('Invalid event "%s", only "checkout.session.completed" is supported!', $event->type), 400
            );
        }
    }


}
