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
class StripeV3LineItemsAppendIntoSingleLine
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
     * This method is a fallback for appendDetailedLineItems.
     *
     * It is needed when there is a promotion on the order:
     * within stripe's API, promotions on the items are manageable but not on the order.
     *
     * @param array $paymentDetails
     * @param Order $order
     *
     * @return array All items a glued together as a single one line_items
     */
    public function appendLineItems(array $paymentDetails, Order $order): array
    {
        $paymentDetails['line_items'] = [];
        $images                       = [];
        $descriptionExtensionsList    = [];

        /** @var OrderItem $item */
        foreach ($order->getItems() as $item) {
            $images[] = $this->cacheManager->generateUrl(
                $item->getProduct()->getImages()->first()->getPath(),
                $this->liipImagineFilterName
            );
            $descriptionExtensionsList[] = sprintf(
                '%s (%s€)',
                $item->getVariantName(),
                round($item->getDiscountedUnitPrice() / 100, 2)
            );
        }

        if ($order->getPromotionCoupon() && !empty($order->getPromotionCoupon()->getCode())) {
            $promotion = sprintf(
                "\n, including promotion: %s (%s€)",
                $order->getPromotionCoupon()->getCode(),
                round($order->getOrderPromotionTotal() / 100, 2)
            );
        } if ($order->getOrderPromotionTotal() != 0) {
        $promotion = sprintf(
            "\n, including a promotion of %s€",
            round($order->getOrderPromotionTotal() / 100, 2)
        );
    } else {
        $promotion = '';
    }

        $description = sprintf(
            "%d extensions: \n%s%s",
            count($descriptionExtensionsList),
            implode("\n, ", $descriptionExtensionsList),
            $promotion
        );

        $paymentDetails['line_items'][] = [
            'name' => sprintf('iTop Hub order %s', $order->getNumber()),
            'amount' => $order->getTotal(),
            'currency' => $order->getCurrencyCode(),
            'quantity' => 1,
            'images' => $images,
            'description' => $description,
        ];

        return $paymentDetails;
    }


}