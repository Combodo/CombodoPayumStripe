<?php
namespace Combodo\StripeV3\Action\Api;

use Combodo\StripeV3\Keys;
use Combodo\StripeV3\Request\PollFullfilledPayments;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Cancel;

class PollFullfilledPaymentsAction implements ActionInterface, ApiAwareInterface
{
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
     * @param PollFullfilledPayments $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $secretKey = $this->keys->getSecretKey();
        \Stripe\Stripe::setApiKey($secretKey);

        $eventsFilter = $request->getEventsFilter();
        $events = \Stripe\Event::all($eventsFilter);
        
        

        $request->setEvents($events);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof PollFullfilledPayments
        ;
    }
}
