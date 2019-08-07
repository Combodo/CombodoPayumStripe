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

use AppBundle\Entity\Payment;
use Combodo\StripeV3\Action\CheckoutCompletedEventAction;
use Combodo\StripeV3\Action\CheckoutCompletedInformationProvider;
use Combodo\StripeV3\Request\handleCheckoutCompletedEvent;
use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Payum;
use Payum\Core\Security\HttpRequestVerifierInterface;
use Payum\Core\Security\TokenInterface;
use Psr\Log\LoggerInterface;
use SM\Factory\FactoryInterface;
use Sylius\Bundle\PayumBundle\Action\CapturePaymentAction;
use Sylius\Component\Payment\Model\PaymentInterface;
use Sylius\Component\Payment\PaymentTransitions;
use Sylius\Component\Resource\StateMachine\StateMachineInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Webmozart\Assert\Assert;

class StripeV3UpdatePaymentStateOnCheckoutCompletedEvent implements ExtensionInterface
{
    /** @var FactoryInterface */
    private $factory;
    /** @var HttpRequestVerifierInterface $httpRequestVerifier */
    private $httpRequestVerifier;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(FactoryInterface $factory, LoggerInterface $logger)
    {
        $this->factory              = $factory;
        $this->logger               = $logger;
        $this->httpRequestVerifier  = null;
    }

    /**
     * This method is used by the dependency injection to avoid a false positive circular reference.
     * Please, you must not call this method
     *
     * @param HttpRequestVerifierInterface $httpRequestVerifier
     */
    public function setHttpRequestVerifier(HttpRequestVerifierInterface $httpRequestVerifier): void
    {
        if (null !== $this->httpRequestVerifier) {
            throw new \LogicException(__METHOD__.' is not meant to be called outside of the dependency injection!');
        }
        $this->httpRequestVerifier  = $httpRequestVerifier;
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
        if (!$action instanceof CheckoutCompletedInformationProvider) {
            return;
        }
        if ($context->getException() !== null) {
            return;
        }

        $token              = $action->getToken();
        $status             = $action->getStatus();

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

        $this->runPaymentWorkflow($payment);

        $this->invalidateToken($context, $token);
    }



    /**
     * @param $payment
     */
    private function runPaymentWorkflow($payment): void
    {
        if ($payment->getState() !== PaymentInterface::STATE_COMPLETED) {
            $this->applyTransition($payment, PaymentInterface::STATE_COMPLETED);
        } else {
            $this->logger->debug(
                "Transition skipped",
                [
                    'target state' => PaymentInterface::STATE_COMPLETED,
                    'payment object' => $payment,
                ]
            );
        }
    }

    private function applyTransition(PaymentInterface $payment, string $nextState): void
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



    /**
     * @param Context        $context
     * @param TokenInterface $token
     */
    private function invalidateToken(Context $context, TokenInterface $token): void
    {
        /** @var handleCheckoutCompletedEvent $request */
        $request = $context->getRequest();
        if ($request->canTokenBeInvalidated()) {
            $this->httpRequestVerifier->invalidate($token);
        } else {
            $this->logger->debug('The request asked to keep the token, it was not invalidated');
        }
    }



}
