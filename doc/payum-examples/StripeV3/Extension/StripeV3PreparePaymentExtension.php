<?php
/**
 * Created by Bruno DA SILVA, working for Combodo
 * Date: 08/08/18
 * Time: 15:12
 */
namespace App\Extension\StripeV3;


use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Model\Payment;

/**
 *
 * This class handles the addition of line_items to stripe checkout v3
 *
 * It is wired using Payum's built in extension system
 *  - @see https://github.com/Payum/PayumBundle/blob/master/Resources/doc/container_tags.md#extension-tag
 *  - @see https://github.com/Payum/Payum/blob/master/docs/the-architecture.md#extensions
 *
 * It is wired to stripe_checkout only using a factory filter on the service tag.
 * It is additionally filtered to the Capture request using a code check.
 *
 * for the explanation of why line_items are required, please refer to
 *  - https://stripe.com/docs/payments/checkout/server#integrate
 *  - https://stripe.com/docs/api/checkout/sessions/create#create_checkout_session-line_items
 *
 * @author Bruno DA SILVA
 * @author Valentin Corre
 */
class StripeV3PreparePaymentExtension implements ExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function onExecute(Context $context)
    {

        if (!$this->supports($context)) return;

        /** @var Capture $request */
        $request = $context->getRequest();
        /** @var Payment $payment */
        $payment = $request->getModel();

        $context->getGateway()->execute($status = new GetHumanStatus($payment));
        if (!$status->isNew()) return;

        /** @var array $paymentDetails */
        $paymentDetails = $payment->getDetails();
        $paymentDetails['line_items'] = [];
        $paymentDetails['line_items'][] = [
            'name' => $payment->getDescription(),
            "amount" => $payment->getTotalAmount(),
            "currency" => $payment->getCurrencyCode(),
            "quantity" => 1
        ];
        $payment->setDetails($paymentDetails);
    }

    /**
     * {@inheritdoc}
     */
    public function onPreExecute(Context $context)
    {
        //do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function onPostExecute(Context $context)
    {
        //do nothing
    }

    public function supports(Context $context)
    {
        $request = $context->getRequest();
        return
            $request instanceof Capture &&
            $request->getModel() instanceof Payment
            ;
    }
}
