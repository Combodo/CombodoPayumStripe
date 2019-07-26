## Abstract


The gateway available in this repository is meant to provide you an integration with stripe [Checkout Server](https://stripe.com/docs/payments/checkout/server).

In order to achieve this, you will have to follow those steps:


# Installation

## Composer

```bash
composer require combodo/stripe-v3
```

Note: for now I do not plan to follow BC rules, use semantic versioning or other, so please check if the code is working after each upgrade.

## Register the gateway

See the service tagged `payum.gateway_factory_builder` [in the example of conf](./app/config/payum.yml).

You may also be interested with [Payum's doc](https://github.com/Payum/Payum/blob/master/docs/get-it-started.md), or even [sylius' doc](https://docs.payum.com/en/latest/book/orders/payments.html#payment-gateway-configuration) about payment gateway configuration.

# Customization

This gateway handle communication with stripe and changes the payment state.

As every Payum gateways, it can not know the workflow of your store, so it does require you to perform an integration between your store and this gateway:

This may be a little cumbersome, you'll find here how I did integrate this gateway with my Symfony project.


> :bangbang: beware: I tested it on my own highly customized symfony 3.4!

## Fulfill Stripe Checkout server requirements

Alas conventional data provided to Payum stripe checkout server require extra data: [line_items](https://stripe.com/docs/api/checkout/sessions/create#create_checkout_session-line_items).
You'll have to give them to payum.

> :bulb: When you use Sylius, you are already plugged in with hard coded values, my solution was to add an [extension that append those information](./src/AppBundle/Payment/StripeV3RequirementsFulfillerOnCaptureExtensions.php) (do not forget to [tag the service](./app/config/payum.yml)).

## Listen to Payum changes of the payment state and trigger your own logic

Once the payment is confirmed, you probably want to trigger a workflow.

> :bulb: When you use Payum, you have to complete the payment detail with hard coded values, my solution was to add an [extension](./src/AppBundle/Payment/StripeV3UpdatePaymentStateOnCheckoutCompletedEvent.php) (do not forget to [tag the service](./app/config/payum.yml)).

# Implementation details

This gateway provide one method to retrieve payments

- a check when the user is redirected after payment

Every implementations should execute a request `handleCheckoutCompletedEvent` handled by `CheckoutCompletedEventAction`.

> :loudspeaker: This is very important because as you can see in the extension [StripeV3UpdatePaymentStateOnCheckoutCompletedEvent](./src/AppBundle/Payment/StripeV3UpdatePaymentStateOnCheckoutCompletedEvent.php), you are supposed to plug your code onto this `CheckoutCompletedEventAction`.

## Redirect after payment

> :scream: Attention: while being the simpler to implement (and the only one available without extra work), this solution alone is really not sufficient. You must at least complete it with the cron.
