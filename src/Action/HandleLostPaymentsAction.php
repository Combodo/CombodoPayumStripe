<?php
namespace Combodo\StripeV3\Action;

use Combodo\StripeV3\Exception\TokenNotFound;
use Combodo\StripeV3\Keys;
use Combodo\StripeV3\Request\FindLostPayments;
use Combodo\StripeV3\Request\handleCheckoutCompletedEvent;
use Combodo\StripeV3\Request\HandleLostPayments;
use Combodo\StripeV3\Request\PollFullfilledPayments;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Cancel;
use Payum\Core\Request\GetToken;
use Payum\Core\Security\TokenInterface;
use Stripe\Collection;
use Stripe\Event;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class HandleLostPaymentsAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * {@inheritDoc}
     *
     * @param HandleLostPayments $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $eventsCollection = $this->fetchEventsCollection($request);

        list($tokenNotFoundCounter, $tokenFoundCounter) = $this->iterateCollection($eventsCollection);

        $request->setLostRetrievedCounter($tokenFoundCounter);
        $request->setParsedValidCounter($tokenNotFoundCounter);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof HandleLostPayments
        ;
    }

    /**
     * @param $request
     *
     * @return PollFullfilledPayments
     */
    private function fetchEventsCollection(HandleLostPayments $request): Collection
    {
        $eventsFilter = [];
        if (!empty($request->getMinCtime())) {
            $eventsFilter = [
                'created' => [
                    'gte' => strtotime($request->getMinCtime()),
                ],
            ];
        }

        $fullfilledPayements = new PollFullfilledPayments($eventsFilter);
        $this->gateway->execute($fullfilledPayements);

        return $fullfilledPayements->getEvents();
    }

    /**
     * @param $request
     * @param $eventsCollection
     *
     * @return array
     */
    private function iterateCollection($eventsCollection): array
    {
        $tokenFoundCounter = $tokenNotFoundCounter = 0;

        $eventsIterator = $eventsCollection->autoPagingIterator();
        foreach ($eventsIterator as $event) {
            try {
                $request = new handleCheckoutCompletedEvent($event, handleCheckoutCompletedEvent::TOKEN_CAN_BE_INVALIDATED);
                $this->gateway->execute($request);
                $tokenFoundCounter++;
            } catch (TokenNotFound $e) {
                //if this token is not found, it means that the payment was already processed, on a 100% working code we should always enter here
                $tokenNotFoundCounter++;
            }
        }

        return array($tokenNotFoundCounter, $tokenFoundCounter);
    }


}
