<?php


namespace AppBundle\Payment;


use Combodo\StripeV3\Action\CheckoutCompletedEventAction;
use Combodo\StripeV3\Constants;
use Payum\Core\Action\CapturePaymentAction;
use Payum\Core\Bridge\Symfony\Security\HttpRequestVerifier;
use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Model\Payment;

/**
 * This class handles the payment once returned paid from stripe
 *
 *
 * It is wired using Payum's built in extension system
 *  - @see https://github.com/Payum/PayumBundle/blob/master/Resources/doc/container_tags.md#extension-tag
 *  - @see https://github.com/Payum/Payum/blob/master/docs/the-architecture.md#extensions
 *
 * It is wired to stripe_checkout only using a factory filter on the service tag.
 * It is additionally filtered to the Capture request using a code check.
 *
 *
 * @package AppBundle\Payment
 *
 * @author Bruno DA SILVA
 * @author Valentin Corre
 *
 */
class StripeV3UpdatePaymentStateOnCheckoutCompletedEvent implements ExtensionInterface
{
    /** @var HttpRequestVerifier */
    private $httpRequestVerifier;

    /**
     * @param HttpRequestVerifier $httpRequestVerifier
     *
     * @return $this
     */
    public function setHttpRequestVerifier(HttpRequestVerifier $httpRequestVerifier)
    {
        $this->httpRequestVerifier = $httpRequestVerifier;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function onPostExecute(Context $context): void
    {
        $action = $context->getAction();

        if (!$action instanceof CheckoutCompletedEventAction) return;

        if ($context->getException() !== null) return;
        $token      = $action->getToken();
        $status     = $action->getStatus();
        if (empty($token) ) {
            throw new \LogicException('The token provided was not found! (see previous exceptions)');
        }
        if (empty($status)) {
            throw new \LogicException('The request status could not be retrieved! (see previous exceptions)');
        }
        if (!$status->isCaptured()) return;

        // Recovering the last CapturePaymentAction occurence in order to update his model
        // (which will be used in StatusAction to define the final paymentStatus)
        $captureContext = null;
        foreach($context->getPrevious() as $previousContext) {
            if ($previousContext->getAction() instanceof CapturePaymentAction) $captureContext = $previousContext;
        }
        if(empty($captureContext)) return;
        $payment = $captureContext->getRequest()->getFirstModel();
        if (!$payment instanceof Payment) return;
        $details = $payment->getDetails();
        $details['captured'] = true;
        $details['paid'] = true;
        $details['status'] = Constants::STATUS_PAID;
        $payment->setDetails($details);
        $this->httpRequestVerifier->invalidate($token);
    }

    /**
     * {@inheritdoc}
     */
    public function onPreExecute(Context $context)
    {
        // Nothing to do here
    }

    /**
     * {@inheritdoc}
     */
    public function onExecute(Context $context)
    {
        // Nothing to do here
    }

}
