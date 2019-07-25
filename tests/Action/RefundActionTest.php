<?php
namespace Combodo\StripeV3\Tests\Action;

use Combodo\StripeV3\Action\RefundAction;
use Combodo\StripeV3\Keys;
use Combodo\StripeV3\Request\handleCheckoutCompletedEvent;
use Combodo\StripeV3\Tests\invokeNonPublicMethodTrait;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\GatewayInterface;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\Refund;
use Payum\Core\Tests\GenericActionTest;
use Payum\Core\Exception\LogicException;
use Stripe\Event;

class RefundActionTest extends GenericActionTest
{
    use invokeNonPublicMethodTrait;

    /**
     * @var RefundAction
     */
    protected $action;

    protected $actionClass = RefundAction::class;

    protected $requestClass = Refund::class;

    /**
     * @test
     */
    public function shouldImplementApiAwareInterface()
    {
        $rc = new \ReflectionClass($this->actionClass);

        $this->assertTrue($rc->implementsInterface(ApiAwareInterface::class));
    }

    /**
     * @test
     */
    public function shouldAllowSetApi()
    {
        $expectedApi = $this->createApiMock();

        $this->action->setApi($expectedApi);

        $this->assertAttributeSame($expectedApi, 'keys', $this->action);
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\UnsupportedApiException
     */
    public function throwIfUnsupportedApiGiven()
    {

        $this->action->setApi(new \stdClass());
    }

    /**
     * @test
     * @expectedException LogicException
     * @expectedExceptionMessage The payment intent id id has to be set.
     */
    public function throwIfNoStripePaymentIntentId()
    {
//        $gatewayMock = $this->createGatewayMock();
//        $gatewayMock
//            ->expects($this->once())
//            ->method('execute')
//            ->with($this->isInstanceOf(GetHttpRequest::class))
////            ->will($this->returnCallback(function (GetHttpRequest $request) {
////                $request->query = ['expected be2bill query'];
////            }))
//        ;

        $apiMock = $this->createApiMock();

        $this->action->setApi($apiMock);

        $this->action->execute(new Refund([]));
    }

    /**
     * @test
     * @expectedException LogicException
     * @expectedExceptionMessage The amount is invalid.
     */
    public function throwIfBadStringAmountButPaymentIntentId()
    {
        $arrayMock = [
            'payment_intent_id' => 'ThePaymentIntentId',
            'amount' => 'test'
        ];

        $apiMock = $this->createApiMock();

        $this->action->setApi($apiMock);

        $this->action->execute(new Refund($arrayMock));
    }

    /**
     * @test
     * @expectedException LogicException
     * @expectedExceptionMessage The amount is invalid.
     */
    public function throwIfBadAmountButPaymentIntentId()
    {
        $arrayMock = [
            'payment_intent_id' => 'ThePaymentIntentId',
            'amount' => 0
        ];

        $apiMock = $this->createApiMock();

        $this->action->setApi($apiMock);

        $this->action->execute(new Refund($arrayMock));
    }

    /**
     * @test
     */
    public function throwIfInvalidSignature()
    {
        $arrayMock = [
            'payment_intent_id' => 'ThePaymentIntentId',
            'amount' => 2
        ];

        $apiMock = $this->createApiMock();
        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(GetHttpRequest::class))
//            ->will($this->returnCallback(function (GetHttpRequest $request) {
//                $request->query = ['expected be2bill query'];
//            }))
        ;
        $this->action->setApi($apiMock);


//        try {
            $this->action->execute(new Refund($arrayMock));
            var_dump($arrayMock);
//        } catch (LogicException $exception) {
//            $this->assertSame('Invalid signature', $exception->getMessage());
//
//            return;
//        } finally {
//            unset($_SERVER['HTTP_STRIPE_SIGNATURE']);
//        }
//
//        $this->fail('An expected exception did not occur Oo!');
    }



    /**
     * @test
     */
    public function shouldExecuteHandleCheckoutCompletedEventIfEventRetrieved()
    {
        $apiMock = $this->createApiMock();
        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(handleCheckoutCompletedEvent::class))
        ;

        $class = new \ReflectionClass($this->actionClass);
        $methodHandleStripeEvent = $class->getMethod('handleStripeEvent');
        $methodHandleStripeEvent->setAccessible(true);

        $this->action->setGateway($gatewayMock);
        $this->action->setApi($apiMock);

        $model = new \ArrayObject([
            'AMOUNT' => 1.0,
            'FOO' => 'FOOOLD',
        ]);

        $event = $this->createMock(Event::class);

        //this call the private method being tested
        $methodHandleStripeEvent->invokeArgs($this->action, [$event, false]);
    }

    /**
     * @test
     * @expectedException LogicException
     */
    public function throwIfWrongStripeEventType()
    {
        $event = $this->createMock(Event::class);
        $event->type = 'foo';

        $instance = $this->action;
        $methodParams = [$event];
        $methodName = 'checkStripeEventType';
        $this->invokeNonPublicMethod($instance, $methodName, $methodParams);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Keys
     */
    protected function createApiMock()
    {
        return $this->createMock(Keys::class, ['public', 'secret', 'endpoint'], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|GatewayInterface
     */
    protected function createGatewayMock()
    {
        return $this->createMock(GatewayInterface::class);
    }


}
