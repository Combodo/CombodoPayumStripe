<?php
namespace Combodo\StripeV3;

class Keys
{
    /** @var string */
    private $publishable;

    /** @var string */
    private $secret;

    /** @var string $endpointSecret */
    private $endpointSecret;

    /**
     * @param string $publishable
     * @param string $secret
     * @param string $endpointSecret the stripe's webHook secret key
     */
    public function __construct(string $publishable, string $secret, string $endpointSecret)
    {
        $this->publishable      = $publishable;
        $this->secret           = $secret;
        $this->endpointSecret   = $endpointSecret;
    }

    /**
     * @return string
     */
    public function getSecretKey()
    {
        return $this->secret;
    }

    /**
     * @return string
     */
    public function getPublishableKey()
    {
        return $this->publishable;
    }
    /**
     * @return string
     */
    public function getEndpointSecretKey()
    {
        return $this->endpointSecret;
    }
}
