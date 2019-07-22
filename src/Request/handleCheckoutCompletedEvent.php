<?php
/**
 * Created by Bruno DA SILVA, working for Combodo
 * Date: 22/07/19
 * Time: 16:54
 */

namespace Combodo\StripeV3\Request;


use Stripe\Event;

class handleCheckoutCompletedEvent
{
    /** @var Event $event */
    private $event;
    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }
}