## Extensions

There must be a way to extend the gateway with custom logic.
[Extension](https://github.com/Payum/Payum/blob/master/src/Payum/Core/Extension/ExtensionInterface.php) to the rescue.

With StripeV3, we need a few custom extensions in order to properly prepare and handle the payment. 

Here is an example on how to register your customs extensions (examples are provided in this [folder](./StripeV3/Extension)).

```php
<?php

use AppBundle\Extension\StripeV3\StripeV3PreparePaymentExtension;

/** @var \Payum\Core\Gateway $gateway */
$gateway->addExtension(new StripeV3PreparePaymentExtension());

// here is the place where the payment maybe prepared.
$gateway->execute(new FooRequest);
```