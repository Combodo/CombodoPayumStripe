<?php
/**
 * Created by Bruno DA SILVA, working for Combodo
 * Date: 11/07/18
 * Time: 17:45
 */

namespace AppBundle\Entity;

use Combodo\StripeV3\Model\StripePaymentDetails;
use Sylius\Component\Core\Model\Payment as BasePayment;

class Payment extends BasePayment implements StripePaymentDetails
{
    /** @var string $checkoutSessionId */
    private $checkoutSessionId;
    /** @var string $paymentIntentId */
    private $paymentIntentId;

    /**
     * Legacy method
     * BEWARE : it's current implementation is specific to stripe!
     * @return string
     */
    public function getRemoteId(): string
    {
        return $this->getPaymentIntentId() ?? '';
    }

    /**
     * @return string
     */
    public function getCheckoutSessionId(): string
    {
        return $this->checkoutSessionId;
    }

    /**
     * @param string $checkoutSessionId
     */
    public function setCheckoutSessionId(string $checkoutSessionId): void
    {
        $this->checkoutSessionId = $checkoutSessionId;
    }

    /**
     * @return string
     */
    public function getPaymentIntentId(): string
    {
        return $this->paymentIntentId;
    }

    /**
     * @param string $paymentIntentId
     */
    public function setPaymentIntentId(string $paymentIntentId): void
    {
        $this->paymentIntentId = $paymentIntentId;
    }
}