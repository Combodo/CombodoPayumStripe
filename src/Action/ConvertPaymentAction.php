<?php
namespace Combodo\StripeV3\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Convert;
use Payum\Core\Security\SensitiveValue;

class ConvertPaymentAction implements ActionInterface
{
    use GatewayAwareTrait;

    /**
     * {@inheritDoc}
     *
     * @param Convert $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PaymentInterface $payment */
        $payment = $request->getSource();

        $details = ArrayObject::ensureArrayObject($payment->getDetails());
        $details["amount"] = $payment->getTotalAmount();
        $details["currency"] = $payment->getCurrencyCode();
        $details["description"] = $payment->getDescription();

        if ($card = $payment->getCreditCard()) {
            // Since I (the intial developer of this gateway: Combodo) does not handle credit card,
            // I prefer to block this path of the code in order to make sure it is properly tested by anyone who need it !
            // PS: handling credit card is bad, you should avoid it!
            throw new \LogicException('Credit card is purposely not implemented, as it is out of scope for Combodo');

            if ($card->getToken()) {
                $details["customer"] = $card->getToken();
            } else {
                $details["card"] = SensitiveValue::ensureSensitive([
                    'number' => $card->getNumber(),
                    'exp_month' => $card->getExpireAt()->format('m'),
                    'exp_year' => $card->getExpireAt()->format('Y'),
                    'cvc' => $card->getSecurityCode(),
                ]);
            }
        }

        $request->setResult((array) $details);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Convert &&
            $request->getSource() instanceof PaymentInterface &&
            $request->getTo() == 'array'
        ;
    }
}
