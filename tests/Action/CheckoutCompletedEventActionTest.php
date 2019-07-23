<?php
/**
 * Created by Bruno DA SILVA, working for Combodo
 * Date: 23/07/19
 * Time: 11:47
 */

namespace Combodo\StripeV3\Tests\Action\Api;


use Combodo\StripeV3\Action\CheckoutCompletedEventAction;
use Combodo\StripeV3\Request\Api\ObtainToken;
use Combodo\StripeV3\Request\handleCheckoutCompletedEvent;
use Combodo\StripeV3\StripeV3GatewayFactory;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayFactoryInterface;
use Payum\Core\GatewayInterface;
use Payum\Core\Model\Payment;
use Payum\Core\Request\GetBinaryStatus;
use Payum\Core\Request\GetToken;
use Payum\Core\Security\TokenInterface;
use PHPUnit\Framework\TestCase;
use Stripe\Event;
use Sylius\Bundle\PayumBundle\Request\GetStatus;

class CheckoutCompletedEventActionTest extends TestCase
{
    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function canBeConstructedWithNoArgs()
    {
        $checkoutCompletedEventAction = new CheckoutCompletedEventAction();
    }

    /**
     * @test
     */
    public function shouldHaveDefaultStatusToNull()
    {
        $checkoutCompletedEventAction = new CheckoutCompletedEventAction();
        $this->assertNull($checkoutCompletedEventAction->getStatus());
    }

    /**
     * @test
     */
    public function shouldHaveDefaultTokenToNull()
    {
        $checkoutCompletedEventAction = new CheckoutCompletedEventAction();
        $this->assertNull($checkoutCompletedEventAction->getToken());
    }


    /**
     * @test
     */
    public function shouldSupportElligibleRequest()
    {
        $event = new Event();
        $supportedRequest = new handleCheckoutCompletedEvent($event);
        $checkoutCompletedEventAction = new CheckoutCompletedEventAction();
        $this->assertTrue($checkoutCompletedEventAction->supports($supportedRequest));
    }
    /**
     * @test
     * @expectedException Payum\Core\Exception\RequestNotSupportedException
     */
    public function shouldRefuseToExecNotElligibleRequestRequest()
    {
        $request = new \StdClass();
        $checkoutCompletedEventAction = new CheckoutCompletedEventAction();
        $result = $checkoutCompletedEventAction->execute($request);

    }

    /**
     * This test is overly complicated!
     * Maybe it should just be removed!
     *
     * @test
     */
    public function shouldSupportElligibleRequests()
    {
        $mockGateway = $this->createMock(GatewayInterface::class);
        $mockGateway
            ->expects($this->at(0))
            ->method('execute')
            ->will($this->returnCallback(function (GetToken $request)  {
                $token = $this->createMock(TokenInterface::class);
                $request->setToken($token);
                return $request;
            }))
        ;

        $mockGateway
            ->expects($this->at(1))
            ->method('execute')
            ->will($this->returnCallback(function (GetBinaryStatus $request)  {
                $paymentData = (object) [
                    'getDetails' => function() { return [];}
                ];
                $request->markNew();

                $forcedFirstModel = new Payment();
                //this code is awfully complicated! it access private/protected attribute using a trick in two steps :
                // step1: create a closure who modifiy the non public attribute
                // step2: use Closure::bind() to change the scope of the closure in order to tell php "the scope of this closure is the given object"
                // "et voila!" it can access non public attributes
                //see : https://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/
                $protectedAttributeAccessor = function (GetBinaryStatus $request) use ($forcedFirstModel){
                    $request->firstModel = $forcedFirstModel;
                };
                $protectedAttributeAccessor = \Closure::bind($protectedAttributeAccessor, null, $request);
                $protectedAttributeAccessor($request);
                return $request;
            }))
        ;

        $eventObject = (object) [
            'object' => (object) [
                'id' => '42',
                'client_reference_id' => 'foo',
                'payment_intent' => 'bar',
            ]
        ];
        $event = new Event();
        $event->data = $eventObject;
        $request = new handleCheckoutCompletedEvent($event);
        $checkoutCompletedEventAction = new CheckoutCompletedEventAction();
        $checkoutCompletedEventAction->setGateway($mockGateway);
        $result = $checkoutCompletedEventAction->execute($request);

        $this->assertTrue($checkoutCompletedEventAction->supports($request));
    }
}