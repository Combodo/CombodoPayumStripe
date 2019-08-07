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
class StripeV3LineItemsAppendDetailled
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
     * return the payment details completed with the line_items.
     *
     * Stripe has a limitation: it cannot handle order level promotions (and it refuses negative amount for the line_items).
     * So if there are so, this code fails!
     *
     * @param array $paymentDetails
     * @param Order $order
     * @return array
     */
    public function appendLineItems(array $paymentDetails, Order $order): array
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


}