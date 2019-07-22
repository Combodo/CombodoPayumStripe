<?php
/**
 * Created by Bruno DA SILVA, working for Combodo
 * Date: 22/07/19
 * Time: 15:46
 */
namespace Combodo\StripeV3\Request;

use Stripe\Collection;

class PollFullfilledPayments
{
    /** @var array $eventsFilter @see https://stripe.com/docs/payments/checkout/fulfillment#polling */
    private $eventsFilter;
    /** @var Collection $events a collection of Events
     * @see https://stripe.com/docs/api/checkout/sessions/object
     * @see https://stripe.com/docs/api/events/list
     * @see https://stripe.com/docs/api/pagination/auto
     */
    private $events;

    public function __construct(array $eventsFilter = [])
    {
        $eventsFilterDefault = [
            'type'      => 'checkout.session.completed',
            'limit'     => 100,                         //note: given this limit, you must use autoPagingIterator() to iterate over the Collection
            'created'   => [
                'gte' => strtotime('-1 month'),
            ],
        ];

        $this->eventsFilter = array_merge($eventsFilterDefault, $eventsFilter);
    }

    public function getEventsFilter(): array
    {
        return $this->eventsFilter;
    }

    public function setEvents(Collection $events) {
        $this->events = $events;
    }

    public function getEvents(): ?Collection
    {
        return $this->events;
    }
}