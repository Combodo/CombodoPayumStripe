<?php
/**
 * Created by Bruno DA SILVA, working for Combodo
 * Date: 08/08/18
 * Time: 15:12
 */

namespace AppBundle\Payment;


use AppBundle\Entity\Order;
use AppBundle\Entity\OrderItem;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Request\Capture;

use Sylius\Bundle\PayumBundle\Request\GetStatus;
use Sylius\Component\Core\Model\ImageInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;

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
 *
 * @author Bruno DA SILVA
 */
class StripeV3RequirementsFulfillerOnCaptureExtensions implements ExtensionInterface
{
    /** @var string  */
    private $providerName;
    /**
     * @var CacheManager
     */
    private $cacheManager;
    /**
     * @var string
     */
    private $liipImagineFilterName;

    public function __construct(string $providerName, CacheManager $cacheManager, string $liipImagineFilterName)
    {
        $this->providerName = $providerName;
        $this->cacheManager = $cacheManager;
        $this->liipImagineFilterName = $liipImagineFilterName;
    }

    /**
     * @var Context $context
     */
    public function onPreExecute(Context $context)
    {
        //do nothing
    }

    /**
     * @var Context $context
     */
    public function onExecute(Context $context)
    {
        if (! $this->supports($context)) {
            return;
        }

        /** @var Capture $request */
        $request = $context->getRequest();

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getModel();

        /** @var Order $order */
        $order = $payment->getOrder();

        $context->getGateway()->execute($status = new GetStatus($payment));
        if (! $status->isNew()) {
            return;
        }

        /** @var array $paymentDetails */
        $paymentDetails = $payment->getDetails();

        $paymentDetails = $this->appendLineItems($paymentDetails, $order);

        $paymentDetails = $this->appendCustomerEmail($order, $paymentDetails);

        $payment->setDetails($paymentDetails);
    }

    /**
     * @var Context $context
     */
    public function onPostExecute(Context $context)
    {
        //do nothing
    }

    public function supports($context)
    {
        /** @var $request Capture */
        $request = $context->getRequest();

        return
            $request instanceof Capture &&
            $request->getModel() instanceof SyliusPaymentInterface
            ;
    }

    /**
     * @param array $paymentDetails
     * @param Order $order
     * @return array
     */
    private function appendLineItems(array $paymentDetails, Order $order): array
    {
        $paymentDetails['line_items'] = [];

        /** @var OrderItem $item */
        foreach ($order->getItems() as $item) {
            $imageUrl = $this->cacheManager->generateUrl(
                $item->getProduct()->getImages()->first()->getPath(),
                $this->liipImagineFilterName
            );
            $paymentDetails['line_items'][] = [
                'name' => $item->getVariantName(),
                'amount' => $item->getDiscountedUnitPrice(),
                'currency' => $order->getCurrencyCode(),
                'quantity' => $item->getQuantity(),
                'images' => [$imageUrl],
                'description' => $item->getProduct()->getShortDescription(),
            ];
        }

        return $paymentDetails;
    }

    /**
     * @param Order $order
     * @param array $paymentDetails
     * @return array
     */
    private function appendCustomerEmail(Order $order, array $paymentDetails): array
    {
        $email = $order->getCustomer()->getEmail();
        if (! empty($email)) {
            $paymentDetails['customer_email'] = $email;
        }

        return $paymentDetails;
    }
}