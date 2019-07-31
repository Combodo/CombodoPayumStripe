<?php
/**
 * Created by Bruno DA SILVA, working for Combodo
 * Date: 31/07/2019
 * Time: 12:17
 */

namespace Combodo\StripeV3\Action;

use Payum\Core\Request\GetBinaryStatus;
use Payum\Core\Security\TokenInterface;

/**
 * Interface CheckoutCompletedInformationProvider
 *
 * contract: Payum's extensions will have sufficient data in order to perform their own workflow
 *
 * @package Combodo\StripeV3\Action
 */
interface CheckoutCompletedInformationProvider
{
    /**
     * @return GetBinaryStatus|null
     * @see
     *
     */
    public function getStatus(): ?GetBinaryStatus;

    /**
     * @see
     * null|TokenInterface
     */
    public function getToken(): ?TokenInterface;

}