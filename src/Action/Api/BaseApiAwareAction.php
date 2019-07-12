<?php
namespace Combodo\StripeV3\Action\Api;

use Combodo\StripeV3\Keys;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Combodo\StripeV3\Api;

abstract class BaseApiAwareAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
{
    use GatewayAwareTrait;
    use ApiAwareTrait;


    public function __construct()
    {
        $this->apiClass = Keys::class;
    }

}
