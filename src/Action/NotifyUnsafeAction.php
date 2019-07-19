<?php
namespace Combodo\StripeV3\Action;

use Combodo\StripeV3\Constants;
use Combodo\StripeV3\Keys;
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
    use ApiAwareTrait {
        setApi as _setApi;
    }

    use GatewayAwareTrait;

    /** @var GetBinaryStatus $status */
    private $status;
    /** @var TokenInterface $token  */
    private $token;

    public function __construct()
    {
        $this->apiClass     = Keys::class;
        $this->status       = null;
        $this->token        = null;
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


        $event              = $this->obtainStripeEvent();
        $checkoutSession    = $event->data->object;
        $tokenHash          = $checkoutSession->client_reference_id;
        $checkoutSessionId  = $checkoutSession->id;
        $paymentIntentId    = $checkoutSession->payment_intent;

        $this->token    = $this->findTokenByHash($tokenHash);
        $this->status   = $this->findStatusByToken($this->token);

        $this->status->markCaptured();
        $payment = $this->status->getFirstModel();
        $request->setModel($payment);

        $this->updatePayment($payment, $checkoutSessionId, $paymentIntentId);
    }


    private function findStatusByToken(TokenInterface $token): GetBinaryStatus
    {
        $status = new GetBinaryStatus($token);
        $this->gateway->execute($status);

        if (empty($status->getValue())) {
            throw new BadRequestHttpException('The payment status could not be fetched');
        }

        return $status;
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

    public function getStatus() :?GetBinaryStatus
    {
        return $this->status;
    }

    public function getToken() : ?TokenInterface
    {
        return $this->token;
    }

    /**
     * @param $tokenHash
     */
    private function findTokenByHash($tokenHash): TokenInterface
    {
        $getTokenRequest = new GetToken($tokenHash);
        $this->gateway->execute($getTokenRequest);
        $token = $getTokenRequest->getToken();
        if (!$token instanceof TokenInterface) {
            throw new BadRequestHttpException('The requested token was not found');
        }

        return $token;
    }

    /**
     * @param $payment
     * @param $checkoutSession
     */
    private function updatePayment($payment, $checkoutSessionId, $paymentIntentId): void
    {
        $details = $payment->getDetails();
        $details['checkout_session_id'] = $checkoutSessionId;
        $details['payment_intent_id']   = $paymentIntentId;
        $payment->setDetails($details);
    }
}
