<?php
/**
 * Created by Bruno DA SILVA, working for Combodo
 * Date: 22/07/19
 * Time: 15:46
 */
namespace Combodo\StripeV3\Request;


class HandleLostPayments
{
    /** @var int $tokenNotFoundCounter*/
    private $tokenNotFoundCounter = 0;
    /** @var int $tokenFoundCounter */
    private $tokenFoundCounter    = 0;

    public function setParsedValidCounter(int $tokenNotFoundCounter): void
    {
        $this->tokenNotFoundCounter = $tokenNotFoundCounter;
    }

    public function setLostRetrievedCounter(int $tokenFoundCounter): void
    {
        $this->tokenFoundCounter;
    }

    public function getParsedValidCounter(): int
    {
        return $this->tokenNotFoundCounter;
    }

    public function getLostRetrievedCounter(): int
    {
        return $this->tokenFoundCounter;
    }
}