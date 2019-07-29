<?php
namespace Combodo\StripeV3\Tests\Action;

use Combodo\StripeV3\Action\NotifyUnsafeAction;
use Combodo\StripeV3\Keys;
use Combodo\StripeV3\Request\handleCheckoutCompletedEvent;
use Combodo\StripeV3\Tests\invokeNonPublicMethodTrait;
use Payum\Core\ApiAwareInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayInterface;
use Payum\Core\Request\Generic;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\Notify;
use Payum\Core\Tests\GenericActionTest;
use Payum\Core\Exception\LogicException;
use Stripe\Event;

class NotifyUnsafeActionTest extends GenericActionTest
{
    use invokeNonPublicMethodTrait;

    protected $actionClass = NotifyUnsafeAction::class;

    protected $requestClass = Notify::class;

    /**
     * @test
     */
    public function shouldImplementGatewayAwareInterface()
    {
        $rc = new \ReflectionClass($this->actionClass);

        $this->assertTrue($rc->implementsInterface(GatewayAwareInterface::class));
    }

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

        $this->assertAttributeSame($expectedApi, 'api', $this->action);
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
     * @expectedExceptionMessage The stripe signature is mandatory
     */
    public function throwIfNoStripeSignature()
    {
        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(GetHttpRequest::class))
//            ->will($this->returnCallback(function (GetHttpRequest $request) {
//                $request->query = ['expected be2bill query'];
//            }))
        ;

        $apiMock = $this->createApiMock();

        $this->action->setGateway($gatewayMock);
        $this->action->setApi($apiMock);

        $this->action->execute(new Notify([]));
    }

    /**
     * @test
     */
    public function throwIfInvalidSignature()
    {
        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(GetHttpRequest::class))
//            ->will($this->returnCallback(function (GetHttpRequest $request) {
//                $request->query = ['expected be2bill query'];
//            }))
        ;

        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('getEndpointSecretKey')
            ->willReturn('fooBar')
        ;

        $this->action->setGateway($gatewayMock);
        $this->action->setApi($apiMock);

        $_SERVER['HTTP_STRIPE_SIGNATURE'] = 'foo';

        try {
            $this->action->execute(new Notify([]));
        } catch (LogicException $exception) {
            $this->assertSame('Invalid signature', $exception->getMessage());

            return;
        } finally {
            unset($_SERVER['HTTP_STRIPE_SIGNATURE']);
        }

        $this->fail('An expected exception did not occur Oo!');
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

    public function provideSupportedRequests()
    {
        return array(
            array(new $this->requestClass(null)),
        );
    }

    public function provideNotSupportedRequests()
    {
        return array(
            array('foo'),
            array(array('foo')),
            array(new \stdClass()),
            array(new $this->requestClass('foo')),
            array(new $this->requestClass(new \stdClass())),
            array($this->getMockForAbstractClass(Generic::class, array(array()))),

            //normally, this should work, but this is not a standard action:
            array(new $this->requestClass(new \ArrayObject())),
            array(new $this->requestClass([])),
        );
    }

    
}
