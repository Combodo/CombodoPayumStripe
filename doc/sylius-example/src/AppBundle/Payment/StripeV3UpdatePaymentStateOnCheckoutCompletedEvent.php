<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace AppBundle\Payment;

use Combodo\StripeV3\Action\CheckoutCompletedEventAction;
use Combodo\StripeV3\Request\handleCheckoutCompletedEvent;
use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Payum;
use Psr\Log\LoggerInterface;
use SM\Factory\FactoryInterface;
use Sylius\Component\Payment\Model\PaymentInterface;
use Sylius\Component\Payment\PaymentTransitions;
use Sylius\Component\Resource\StateMachine\StateMachineInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Webmozart\Assert\Assert;

class StripeV3UpdatePaymentStateOnCheckoutCompletedEvent implements ExtensionInterface
{
    /** @var FactoryInterface */
    private $factory;
    /** @var Payum $payum */
    private $payum;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(FactoryInterface $factory, Payum $payum, LoggerInterface $logger)
    {
        $this->factory = $factory;
        $this->payum = $payum;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function onPreExecute(Context $context): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onExecute(Context $context): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onPostExecute(Context $context): void
    {
        $action = $context->getAction();
        if (!$action instanceof CheckoutCompletedEventAction) {
            return;
        }
        if ($context->getException() !== null) {
            return;
        }

        $token      = $action->getToken();
        $status     = $action->getStatus();

        if (empty($token) ) {
            throw new BadRequestHttpException('The token provided was not found! (see previous exceptions)');
        }
        if (empty($status)) {
            throw new \LogicException('The request status could not be retrieved! (see previous exceptions)');
        }

        if (! $status->isCaptured()) {
            return;//this return is pretty important!! DO NOT REMOVE IT!!!! if you do so, the user who cancels their payment will have the payment granted anyway!
        }

        $payment = $status->getFirstModel();

        if ($payment->getState() !== PaymentInterface::STATE_COMPLETED) {
            $this->updatePaymentState($payment, PaymentInterface::STATE_COMPLETED);
        } else {
            $this->logger->debug("Transition skipped", [
                'target state'      => PaymentInterface::STATE_COMPLETED,
                'payment object'    => $payment,
            ]);
        }

        /** @var handleCheckoutCompletedEvent $request */
        $request = $context->getRequest();
        if ($request->canTokenBeInvalidated()) {
            $this->payum->getHttpRequestVerifier()->invalidate($token);
        } else {
            $this->logger->debug('The request asked to keep the token, it was not invalidated');
        }
    }

    private function updatePaymentState(PaymentInterface $payment, string $nextState): void
    {
        /** @var StateMachineInterface $stateMachine */
        $stateMachine = $this->factory->get($payment, PaymentTransitions::GRAPH);

        Assert::isInstanceOf($stateMachine, StateMachineInterface::class);

        if (null !== $transition = $stateMachine->getTransitionToState($nextState)) {
            $stateMachine->apply($transition);
            $this->logger->debug("Transition applied", [
                'next state'        => $nextState,
                'transition'        => $transition,
                'payment object'    => $payment,
            ]);
        } else {
            $this->logger->debug("No Transition to apply ?!?", [
                'next state'        => $nextState,
                'transition'        => $transition,
                'payment object'    => $payment,
            ]);
        }
    }
}
