<?php
/**
 * Created by Bruno DA SILVA, working for Combodo
 * Date: 25/07/19
 * Time: 10:17
 */

namespace Combodo\StripeV3\Tests\Action;

use Combodo\StripeV3\Action\HandleLostPaymentsAction;
use Combodo\StripeV3\Action\NotifyUnsafeAction;
use Combodo\StripeV3\Keys;
use Combodo\StripeV3\Request\handleCheckoutCompletedEvent;
use Combodo\StripeV3\Request\HandleLostPayments;
use Combodo\StripeV3\Request\PollFullfilledPayments;
use Combodo\StripeV3\Tests\invokeNonPublicMethodTrait;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\LogicException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayInterface;
use Payum\Core\Request\Generic;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\Notify;
use Payum\Core\Tests\GenericActionTest;
use phpDocumentor\Reflection\Types\Void_;
use Stripe\Collection;
use Stripe\Event;
use Stripe\Util\AutoPagingIterator;

/**
 * Class HandleLostPaymentsActionTest
 * @package Combodo\StripeV3\Tests\Action
 *
 * @property HandleLostPaymentsAction $action
 */
class HandleLostPaymentsActionTest extends GenericActionTest
{
    use invokeNonPublicMethodTrait;

    protected $actionClass = HandleLostPaymentsAction::class;

    protected $requestClass = HandleLostPayments::class;

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
    public function shouldNotImplementApiAwareInterface()
    {
        $rc = new \ReflectionClass($this->actionClass);

        $this->assertFalse($rc->implementsInterface(ApiAwareInterface::class));
    }

    /**
     * @test
     * @expectedException \Error
     */
    public function shouldNotAllowSetApi()
    {
        $expectedApi = $this->createApiMock();

        $this->action->setApi($expectedApi);

        $this->assertAttributeSame($expectedApi, 'api', $this->action);
    }



    /**
     * @test
     */
    public function shouldExecutePollFullfilledPaymentsDuringFetchEventsCollection()
    {
        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(PollFullfilledPayments::class))
            ->willReturnCallback(function (PollFullfilledPayments $fullfilledPayements) {
                $fullfilledPayements->setEvents($this->createMock(Collection::class));
            });
        ;
        $this->action->setGateway($gatewayMock);

        $requestHandleLostPayments = $this->createMock(HandleLostPayments::class);
        $this->invokeNonPublicMethod($this->action, 'fetchEventsCollection', [$requestHandleLostPayments]);
    }

    /**
     * @test
     * @dataProvider dataProviderArrayOfEvents
     */
    public function shouldExecuteHandleCheckoutCompletedEventForEachEvent(?string $expectedException, $eventsList)
    {
        if (isset($expectedException)) {
            $this->expectException($expectedException);
        }
        $eventCollectionMock = $this->createMock(Collection::class);
        $eventCollectionMock
            ->method('autoPagingIterator')
            ->willReturnCallback(function() use($eventsList) {

                return new \ArrayIterator($eventsList);
            })
        ;
        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects(
                $expectedException ?
                    $this->any() :
                    $this->exactly(count($eventsList))
            )
            ->method('execute')
            ->with($this->isInstanceOf(handleCheckoutCompletedEvent::class))
        ;
        $this->action->setGateway($gatewayMock);

        list($tokenNotFoundCounter, $tokenFoundCounter) = $this->invokeNonPublicMethod($this->action, 'iterateCollection', [$eventCollectionMock]);

        if ($expectedException) {
            return;
        }
        $this->assertEquals(0, $tokenNotFoundCounter);
        $this->assertEquals(count($eventsList), $tokenFoundCounter);

    }

    public function dataProviderArrayOfEvents()
    {
        return [
            'empty array' => [
                'expectedException' => null,
                'eventsList' => [],
            ],
            'single value' => [
                'expectedException' => null,
                'eventsList' => [
                    $this->createMock(Event::class),
                ],
            ],
            'two values' => [
                'expectedException' => null,
                'eventsList' => [
                    $this->createMock(Event::class),
                    $this->createMock(Event::class),
                ],
            ],
            'ten values' => [
                'expectedException' => null,
                'eventsList' => [
                    $this->createMock(Event::class),
                    $this->createMock(Event::class),
                    $this->createMock(Event::class),
                    $this->createMock(Event::class),
                    $this->createMock(Event::class),
                    $this->createMock(Event::class),
                    $this->createMock(Event::class),
                    $this->createMock(Event::class),
                    $this->createMock(Event::class),
                    $this->createMock(Event::class),
                ],
            ],
            'invalid value 1' => [
                'expectedException' => \TypeError::class,
                'eventsList' => [
                    'foo',
                ],
            ],
            'invalid value 2' => [
                'expectedException' => \TypeError::class,
                'eventsList' =>  [
                    new \stdClass(),
                ],
            ],
        ];
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
        return [
            [new $this->requestClass()],
            [new $this->requestClass(null)],
            [new $this->requestClass(time())],
            [new $this->requestClass('now')],
            [new $this->requestClass('first sunday of last year')],
        ];
    }

    public function provideNotSupportedRequests()
    {
        return [
            ['foo'],
            [['foo']],
            [new \stdClass()],
            [$this->getMockForAbstractClass(Generic::class, [[]])],
        ];
    }
}