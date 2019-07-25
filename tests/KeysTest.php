<?php
namespace Combodo\StripeV3\Tests;

use Combodo\StripeV3\Keys;

class KeysTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function couldBeConstructedWithPublishableKeyAndSecretOne()
    {
        $keys = new Keys('aPublishableKey', 'aSecretKey', 'aEndopointSecret');
    }

    /**
     * @test
     */
    public function souldAllowGetPublishableKeySetInConstructor()
    {
        $keys = new Keys('thePublishableKey', 'aSecretKey', 'aEndopointSecret');

        $this->assertEquals('thePublishableKey', $keys->getPublishableKey());
    }

    /**
     * @test
     */
    public function shouldAllowGetSecretKeySetInConstructor()
    {
        $keys = new Keys('aPublishableKey', 'theSecretKey', 'aEndopointSecret');

        $this->assertEquals('theSecretKey', $keys->getSecretKey());
    }


    /**
     * @test
     */
    public function shouldAllowGetEndpointSecretKeySetInConstructor()
    {
        $keys = new Keys('aPublishableKey', 'theSecretKey', 'aEndopointSecret');

        $this->assertEquals('aEndopointSecret', $keys->getEndpointSecretKey());
    }
}
