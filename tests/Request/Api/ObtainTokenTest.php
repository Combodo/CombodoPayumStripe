<?php
namespace Combodo\StripeV3\Tests\Request\Api;

use Payum\Core\Request\Generic;
use Combodo\StripeV3\Request\Api\ObtainToken;

class ObtainTokenTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function shouldBeSubClassOfGeneric()
    {
        $rc = new \ReflectionClass(ObtainToken::class);

        $this->assertTrue($rc->isSubclassOf(Generic::class));
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function couldBeConstructedWithModelAsFirstArgument()
    {
        new ObtainToken($model = []);
    }
}
