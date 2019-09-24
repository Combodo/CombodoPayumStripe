<?php
/**
 * Created by Bruno DA SILVA, working for Combodo
 * Date: 31/07/2019
 * Time: 15:13
 */

namespace Combodo\StripeV3\Model;

interface StripePaymentDetails
{
    /**
     * @return string|null
     */
    public function getCheckoutSessionId(): ?string;

    /**
     * @param string $checkoutSessionId
     */
    public function setCheckoutSessionId(?string $checkoutSessionId): void;

    /**
     * @return string|null
     */
    public function getPaymentIntentId(): ?string;

    /**
     * @param string $paymentIntentId
     */
    public function setPaymentIntentId(?string $paymentIntentId): void;
}