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
        $supportedRequest = new handleCheckoutCompletedEvent($event, true);
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
     * This test is overly complicated! Sorry :(
     *
     * @dataProvider dataProviderForTestExecutionWithinAGateway
     *
     * @test
     */
    public function testExecutionWithinAGateway(?string $expectException, GatewayInterface $mockGateway, bool $canTokenBeInvalidated, object $eventObject, $tokenMock)
    {
        if (!empty($expectException)) {
            $this->expectException($expectException);
        }

        $event = new Event();
        $event->data = $eventObject;

        $request = new handleCheckoutCompletedEvent($event, $canTokenBeInvalidated);
        $checkoutCompletedEventAction = new CheckoutCompletedEventAction();
        $checkoutCompletedEventAction->setGateway($mockGateway);

        $result = $checkoutCompletedEventAction->execute($request);

        //this is already tested by shouldSupportElligibleRequest, should I remove it?
        $this->assertTrue($checkoutCompletedEventAction->supports($request));

        //assertion over the request object exposed methods
        $this->assertSame($event, $request->getEvent());
        $this->assertSame($canTokenBeInvalidated, $request->canTokenBeInvalidated());

        //assertions over the action exposed methods & mutations
        $this->assertSame($tokenMock, $checkoutCompletedEventAction->getToken());
        $this->assertTrue($checkoutCompletedEventAction->getStatus()->isCaptured());
        $this->assertEquals(['checkout_session_id' => $eventObject->object->id, 'payment_intent_id' => $eventObject->object->payment_intent], $checkoutCompletedEventAction->getStatus()->getFirstModel()->getDetails());
    }

    public function dataProviderForTestExecutionWithinAGateway()
    {
        $tokenMock = $token = $this->createMock(TokenInterface::class);

        $defaultEventObject = (object) [
            'object' => (object) [
                'id' => '42',
                'client_reference_id' => 'foo',
                'payment_intent' => 'bar',
            ]
        ];

        $withIntegerValueEventObject = clone $defaultEventObject;
        $withIntegerValueEventObject->object->id = 142;

        return [
            'test_canTokenBeInvalidated_true' => [
                'expectException'       => null,
                'mockGateway'           => $this->createMockGateway($tokenMock),
                'canTokenBeInvalidated' => true,
                'eventObject'           => $defaultEventObject,
                'tokenMock'             => $tokenMock,
            ],
            'test_canTokenBeInvalidated_false' => [
                'expectException'       => null,
                'mockGateway'           => $this->createMockGateway($tokenMock),
                'canTokenBeInvalidated' => false,
                'eventObject'           => $defaultEventObject,
                'tokenMock'             => $tokenMock,
            ],
            'test_canTokenBeInvalidated_with_integer_in_event' => [
                'expectException'       => null,
                'mockGateway'           => $this->createMockGateway($tokenMock),
                'canTokenBeInvalidated' => false,
                'eventObject'           => $withIntegerValueEventObject,
                'tokenMock'             => $tokenMock,
            ],
            'test_canTokenBeInvalidated_with_null_token' => [
                'expectException'       => \TypeError::class,
                'mockGateway'           => $this->createMockGateway(null),
                'canTokenBeInvalidated' => false,
                'eventObject'           => $defaultEventObject,
                'tokenMock'             => null,
            ],

        ];
    }

    /**
     * @param $tokenMock
     *
     * @return GatewayInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createMockGateway(?TokenInterface $tokenMock)
    {
        if (empty($tokenMock)) {
            return $this->createMockGatewayWithNullToken();
        }

        return $this->createMockGatewayDefault($tokenMock);
    }

    /**
     * @return GatewayInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createMockGatewayWithNullToken()
    {
        $mockGatewayWithNullToken = $this->createMock(GatewayInterface::class);
        $mockGatewayWithNullToken
            ->expects($this->at(0))
            ->method('execute')
            ->will(
                $this->returnCallback(
                    function (GetToken $request) {

                        $request->setToken(null);

                        return $request;
                    }
                )
            );

        return $mockGatewayWithNullToken;
    }

    /**
     * @param TokenInterface|null $tokenMock
     *
     * @return GatewayInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createMockGatewayDefault(?TokenInterface $tokenMock)
    {
        $mockGateway = $this->createMock(GatewayInterface::class);
        $mockGateway
            ->expects($this->at(0))
            ->method('execute')
            ->will(
                $this->returnCallback(
                    function (GetToken $request) use ($tokenMock) {

                        $request->setToken($tokenMock);

                        return $request;
                    }
                )
            );
        $mockGateway
            ->expects($this->at(1))
            ->method('execute')
            ->will(
                $this->returnCallback(
                    function (GetBinaryStatus $request) {
                        $paymentData = (object)[
                            'getDetails' => function () {
                                return [];
                            }
                        ];
                        $request->markNew();

                        $forcedFirstModel = new Payment();
                        //this code is awfully complicated! it access private/protected attribute using a trick in two steps :
                        // step1: create a closure who modifiy the non public attribute
                        // step2: use Closure::bind() to change the scope of the closure in order to tell php "the scope of this closure is the given object"
                        // "et voila!" it can access non public attributes
                        //see : https://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/
                        $protectedAttributeAccessor = function (GetBinaryStatus $request) use ($forcedFirstModel) {
                            $request->firstModel = $forcedFirstModel;
                        };
                        $protectedAttributeAccessor = \Closure::bind($protectedAttributeAccessor, null, $request);
                        $protectedAttributeAccessor($request);

                        return $request;
                    }
                )
            );

        return $mockGateway;
    }


}