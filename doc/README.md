## Abstract
The gateway available in this repository it meant to provide you an integration with stripe [Checkout Server](https://stripe.com/docs/payments/checkout/server).
 

In order to achieve this, 

# installation

## add the dependency using composer

```bash
composer require combodo/stripe-v3
```

note: for now I do not plan to follow BC rules, use semantic versioning or other, so please check if the code is working after each upgrade

## register the gateway

See the service tagged `payum.gateway_factory_builder` [in the example of conf](./sylius-example/app/config/payum.yml).


You may also be interested with [Payum's doc](https://github.com/Payum/Payum/blob/master/docs/get-it-started.md), or even [sylius' doc](https://docs.sylius.com/en/latest/book/orders/payments.html#payment-gateway-configuration) about payment gateway configuration


## Optionally: add a Form handling the gateway configuration

See the service tagged both `sylius.gateway_configuration_type` and `form.type` [in the example of conf](./sylius-example/app/config/payum.yml).

> :bangbang: This example is specific to sylius, if you use another e-commerce platform, you'll have to adapt this.

If you want to implement your own solution, please follow Payum's documentation: [1](https://github.com/Payum/Payum/blob/master/docs/encrypt-gateway-configs-stored-in-database.md) and [2](https://github.com/Payum/Payum/blob/master/docs/configure-gateway-in-backend.md)

# Customization 

This gateway handle communication with stripe and changes the payment state.
Has avery Payum gateways, it cannont know the details of your integration, so it will need an extra work on your integration side:


as this may be a little cumbersome, you'll find how I did integrate this gateway with my Sylius project [under the subdirectory sylius-example](./sylius-example).

> :bangbang: beware: I tested it on my own highly customized sylius 1.2! 
Thus I had some feedback of person having integrated it on sylius 1.5, I haven't' tested it myself, and maybe you'll need extra work to make it work under your own configuration!


## fulfill stripe checkout server requirements
 
alas conventional data provided to Payum stripe checkout server require extra data: [line_items](https://stripe.com/docs/api/checkout/sessions/create#create_checkout_session-line_items).
You'll have to give them to payum.

> :bulb: When you use Sylius, you are already plugged in with hard coded values, 
my solution was to add an [extension that append those information](./sylius-example/src/AppBundle/Payment/StripeV3RequirementsFulfillerOnCaptureExtensions.php)  (do not forget to [tag the service](./sylius-example/app/config/payum.yml)).

## listen to Payum changes of the payment state and trigger your own logic
Once the payment is confirmed, you probably want to trigger a workflow.
> :bulb: When you use Sylius, you are already plugged in with hard coded values, my solution was to add an [extension that trigger the state machine change when needed](./sylius-example/src/AppBundle/Payment/StripeV3UpdatePaymentStateOnCheckoutCompletedEvent.php) (do not forget to [tag the service](./sylius-example/app/config/payum.yml)).


# implementation details
This gateway provide three different methods to retrieve payments
 - a check when the user is redirected after payment
 - a webhook
 - the base of a command that should be called by a scheduled task
 
every three implementations execute a request `handleCheckoutCompletedEvent` handled by `CheckoutCompletedEventAction`.
> :loudspeaker: This is very important because as you can see in the extension [StripeV3UpdatePaymentStateOnCheckoutCompletedEvent](./sylius-example/src/AppBundle/Payment/StripeV3UpdatePaymentStateOnCheckoutCompletedEvent.php), you are supposed to plug your code onto this `CheckoutCompletedEventAction`. 
 

> :fearful: Attention, this solution is not 100% reliable. You must complete it with the cron.
  
## Redirect after payment

> :scream: Attention: while being the simpler to implement (and the only on available without extra work), this solution alone is really not sufficient. You must at least complete it with the cron. 

## using the webhook
 
You need to activate it into stripe's admin panel (doc [here](https://stripe.com/docs/payments/checkout/fulfillment#webhooks) and [here](https://stripe.com/docs/webhooks/setup)).
Stripe will require you to write the webhook adresse.
  
 > :bulb: if you use Symfony, it should be `/payment/notify/unsafe/{gateway}` (see `bin/console debug:router payum_notify_do_unsafe`)
(it expand to `/payment/notify/unsafe/stripe_checkout_v3` if you followed the [configuration proposal](./sylius-example/app/config/payum.yml))


## scheduled task
(also known as `cron`)

to activate this method, you must :
 - create a command 
   - Symphony users, see this [example](./sylius-example/src/AppBundle/Command/FulfillLostPayments.php) a do not forget to [tag the service](./sylius-example/app/config/payum.yml)
 - call it within a cron
   - Symphony users, this should work for you: `bin/console payum:stripev3:fulfill-lost-payments stripe_checkout_v3 --min_ctime="-3 day"
`
 
