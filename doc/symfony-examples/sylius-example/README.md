# Sylius integration

This gateway handle communication with stripe and changes the payment state.

As every Payum gateways, it can not know the workflow of your store, so it does require you to perform an integration between your store and this gateway:

This may be a little cumbersome, but hey, we've got your back: the next steps will help you doing so!

This page is meant to guide you through the integration of this gateway into Sylius.
If you do not use sylius, please go back to the [root documentation](../../)

> :bangbang: beware: I tested it on my own highly customized sylius 1.2!
> Thus I had some feedback of person having integrated it on sylius 1.5, I haven't' tested it myself, and maybe you'll need extra work to make it work under your own configuration!



## Composer

```bash
composer require combodo/stripe-v3
```

Note: for now I do not plan to follow BC rules, use semantic versioning or other, so please check if the code is working after each upgrade.
   

## Register the gateway

See the service tagged `payum.gateway_factory_builder` [in the example of conf](./app/config/payum.yml).

You may also be interested with [Payum's doc](https://github.com/Payum/Payum/blob/master/docs/get-it-started.md), or even [sylius' doc](https://docs.sylius.com/en/latest/book/orders/payments.html#payment-gateway-configuration) about payment gateway configuration.


## add a Form handling the gateway configuration

See the service tagged both `sylius.gateway_configuration_type` and `form.type` [in the example of conf](./app/config/payum.yml).

## Fulfill Stripe Checkout server requirements

Alas conventional data provided to Payum stripe checkout server require extra data: [line_items](https://stripe.com/docs/api/checkout/sessions/create#create_checkout_session-line_items).
You'll have to give them to payum:

This is the role of : [StripeV3OnCaptureExtensions](./src/AppBundle/Payment/StripeV3OnCaptureExtensions.php) 

:bulb: do not forget to 
- implement the services it depend upon ([StripeV3LineItemsAppendDetailled](./src/AppBundle/Payment/StripeV3LineItemsAppendDetailled.php), [StripeV3LineItemsAppendIntoSingleLine](./src/AppBundle/Payment/StripeV3LineItemsAppendIntoSingleLine.php)),  
- [declare and tag all those services](./app/config/payum.yml),
- **adapt** the code to **your own** logic (_seriously I mean it: read this code and adapt it!_)

## Listen to Payum changes of the payment state and trigger your own logic

Once the payment is confirmed, you probably want to trigger a workflow.

This is the role of : [StripeV3UpdatePaymentStateOnCheckoutCompletedEvent](./src/AppBundle/Payment/StripeV3UpdatePaymentStateOnCheckoutCompletedEvent.php) to trigger the state machine change when needed.

:bulb: do not forget to 
- [tag the service](./app/config/payum.yml),
- **adapt** the code to **your own** logic  


### Implementation details

This gateway provide three different methods to retrieve payments

- a check when the user is redirected after payment
- a webhook
- the base of a command that should be called by a scheduled task

Every three implementations execute a request `handleCheckoutCompletedEvent` handled by `CheckoutCompletedEventAction`.

> :loudspeaker: This is very important because as you can see in the extension [StripeV3UpdatePaymentStateOnCheckoutCompletedEvent](./src/AppBundle/Payment/StripeV3UpdatePaymentStateOnCheckoutCompletedEvent.php), you are supposed to plug your code onto this `CheckoutCompletedEventAction`.


### optionally: store stripes transaction identifiers 
If you need to store Stripe's transaction identifier inside the Payment entity, you have two solutions:

make your own entity implement `StripePaymentDetails` so you can store here wherever you want.
> example: [Payment.php](./src/AppBundle/Entity/Payment.php), [Payment.orm.yml](./src/AppBundle/Resources/config/doctrine/Payment.orm.yml).
 
If you do not implement `StripePaymentDetails`, the code will try to write the identifiers into the details. Sadly, Payum erases those 
changes. But you may uses the Payment state machine to read those changes and store them elsewhere 

:bulb: there are no example for this use case, if you follow it, please help us with a PR improving this doc! 

## Redirect after payment

:scream: Attention: this solution alone is really not sufficient. You must at least complete it with the cron!


## Using the webhook

You need to activate it into stripe's admin panel (doc [here](https://stripe.com/docs/payments/checkout/fulfillment#webhooks) and [here](https://stripe.com/docs/webhooks/setup)).
Stripe will require you to write the webhook adresse.

> :bulb: It should be `/payment/notify/unsafe/{gateway}` (see `bin/console debug:router payum_notify_do_unsafe`)
> (it expand to `/payment/notify/unsafe/stripe_checkout_v3` if you followed the [configuration proposal](./app/config/payum.yml))

:information_source: Amongst the three, this method is the first one being called, and the redirect after payment URL require the token to be still present, so this is the only method that ask to not delete the token after a successful payment processing on your side.

> :fearful: Attention, this solution is not 100% reliable. You must complete it with the scheduled task.

## Scheduled task

(also known as `cron`)

To activate this method, you must :

-   create a command
    -   Symfony users, see this [example](./src/AppBundle/Command/FulfillLostPayments.php) a do not forget to [tag the service](./app/config/payum.yml)
-   call it within a cron

    -   This should work for you: `bin/console payum:stripev3:fulfill-lost-payments stripe_checkout_v3 --min_ctime="-3 day"`

This solution is more reliable than the two others because in case of a failure, you can re-play it multiple times. So your `min_ctime` should be _at least_ twice greater than the cron frequency
