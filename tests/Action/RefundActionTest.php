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
    public function ShouldNotHaveRefunded()
    {
        $arrayMock = [
            'payment_intent_id' => 'ThePaymentIntentId',
            'amount' => 2
        ];

        $apiMock = $this->createApiMock();
        $this->action->setApi($apiMock);
        $this->action->execute($refund = new Refund($arrayMock));
        $this->assertArrayNotHasKey('refunded', $refund->getModel());
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
