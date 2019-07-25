<?php
namespace Payum\Klarna\Checkout\Tests\Functional\Resources\Views;

use Combodo\StripeV3\StripeV3GatewayFactory;
use Payum\Core\Bridge\Twig\TwigFactory;
use Payum\Core\Gateway;

class ObtainTokenTemplateTest extends \PHPUnit\Framework\TestCase
{


    /**
     * @test
     */
    public function shouldRenderRedirectToCheckoutWithGivenParameters()
    {
        $twig = self::createTwigEnvironment();

        $result = $twig->render('@CombodoStripeV3/redirect_to_checkout.html.twig', array(
            'publishable_key'   => 'theKey',
            'session_id'        => 'theSessionId',
        ));

        $this->assertContains('var stripe = Stripe(\'theKey\');', $result);
        $this->assertContains('sessionId: \'theSessionId\'', $result);
        $this->assertContains('https://js.stripe.com/v3/', $result);
    }


    /**
     * return a twig environement with the required `namespaced=>paths` mapping
     * Inspired by the implementation of TwigFactory
     * (the path list is hardcoded so I had to handle it on my own)
     *
     * @see TwigFactory
     *
     * @return \Twig_Environment
     * @throws \ReflectionException
     * @throws \Twig\Error\LoaderError
     */
    private static function createTwigEnvironment() : \Twig_Environment
    {
        $loader = new \Twig_Loader_Filesystem();

        $rc = new \ReflectionClass(StripeV3GatewayFactory::class);
        $path = dirname($rc->getFileName()).'/../templates';
        $loader->addPath($path, 'CombodoStripeV3');

        $rc = new \ReflectionClass(Gateway::class);
        $path = dirname($rc->getFileName()).'/Resources/views';
        $loader->addPath($path, 'PayumCore');


        return new \Twig_Environment($loader);
    }
}
