<?php
namespace Combodo\StripeV3\Tests\Action\Api;

use Payum\Core\ApiAwareInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayInterface;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\RenderTemplate;
use Combodo\StripeV3\Action\Api\ObtainTokenAction;
use Combodo\StripeV3\Keys;
use Combodo\StripeV3\Request\Api\ObtainToken;

class ObtainTokenActionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function shouldImplementGatewayAwareInterface()
    {
        $rc = new \ReflectionClass(ObtainTokenAction::class);

        $this->assertTrue($rc->implementsInterface(GatewayAwareInterface::class));
    }

    /**
     * @test
     */
    public function shouldImplementsApiAwareInterface()
    {
        $rc = new \ReflectionClass(ObtainTokenAction::class);

        $this->assertTrue($rc->isSubclassOf(ApiAwareInterface::class));
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function couldBeConstructedWithTemplateAsFirstArgument()
    {
        new ObtainTokenAction('aTemplateName');
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function shouldAllowSetKeysAsApi()
    {
        $action = new ObtainTokenAction('aTemplateName');

        $action->setApi(new Keys('publishableKey', 'secretKey', 'endpointKey'));
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\UnsupportedApiException
     */
    public function throwNotSupportedApiIfNotKeysGivenAsApi()
    {
        $action = new ObtainTokenAction('aTemplateName');

        $action->setApi('not keys instance');
    }

    /**
     * @test
     */
    public function shouldSupportObtainTokenRequestWithArrayAccessModel()
    {
        $action = new ObtainTokenAction('aTemplateName');

        $this->assertTrue($action->supports(new ObtainToken(array())));
    }

    /**
     * @test
     */
    public function shouldNotSupportObtainTokenRequestWithNotArrayAccessModel()
    {
        $action = new ObtainTokenAction('aTemplateName');

        $this->assertFalse($action->supports(new ObtainToken(new \stdClass())));
    }

    /**
     * @test
     */
    public function shouldNotSupportNotObtainTokenRequest()
    {
        $action = new ObtainTokenAction('aTemplateName');

        $this->assertFalse($action->supports(new \stdClass()));
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\RequestNotSupportedException
     * @expectedExceptionMessage Action ObtainTokenAction is not supported the request stdClass.
     */
    public function throwRequestNotSupportedIfNotSupportedGiven()
    {
        $action = new ObtainTokenAction('aTemplateName');

        $action->execute(new \stdClass());
    }
    
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|GatewayInterface
     */
    protected function createGatewayMock()
    {
        return $this->createMock(GatewayInterface::class);
    }
}
