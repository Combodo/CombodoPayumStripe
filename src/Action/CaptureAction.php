<?php
namespace Combodo\StripeV3\Action;

use Combodo\StripeV3\Exception\TokenNotFound;
use Combodo\StripeV3\Request\handleCheckoutCompletedEvent;
use Combodo\StripeV3\Request\PollFullfilledPayments;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\Capture;
use Payum\Core\Exception\RequestNotSupportedException;
use Combodo\StripeV3\Request\Api\CreateCharge;
use Combodo\StripeV3\Request\Api\ObtainToken;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Security\TokenInterface;

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

        if ($model['status']) {
            return;
        }

        if ($model['customer']) {
            return;
        }

        $this->handleIfPaymentDone($request->getToken());

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

    /**
     * once the customer has paid, he is redirected here, so first, let's check if he has effectively paid
     */
    private function handleIfPaymentDone(TokenInterface $token): void
    {
        $getHttpRequest = new GetHttpRequest();
        $this->gateway->execute($getHttpRequest);
        if (isset($getHttpRequest->query['checkout_status'])) {
            $this->pollFullfilledPayments($token);
        }
    }

    private function pollFullfilledPayments(TokenInterface $token): void
    {
        $eventsFilter = [
            'created'   => [
                'gte' => strtotime('-15 minutes'),
            ],
        ];

        $fullfilledPayements = new PollFullfilledPayments($eventsFilter);
        $this->gateway->execute($fullfilledPayements);

        $eventsIterator = $fullfilledPayements->getEvents()->autoPagingIterator();
        foreach ($eventsIterator as $event) {
            if (!isset($event->data->object->client_reference_id) || $token->getHash() != $event->data->object->client_reference_id) {
                continue;
            }
            try {
                $request = new handleCheckoutCompletedEvent($event, handleCheckoutCompletedEvent::TOKEN_CAN_BE_INVALIDATED);
                $this->gateway->execute($request); //an extension must be plugged onto this in order to handle the payment logic on the website side (@see https://github.com/Combodo/CombodoPayumStripe/tree/master/doc/sylius-example)
            } catch (TokenNotFound $e) {
                //if this token is not found, it means that the payment was already processed, hu ho !?! This has no sense! the finally will try to redirect the user, but their are a lot of chance that something pretty bad happend... we logically should never enter here!
            } finally {
                throw new HttpRedirect($token->getAfterUrl());
            }
        }
    }
}
