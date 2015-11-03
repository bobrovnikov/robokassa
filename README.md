Robokassa Bundle
================
Bundle for [ROBOKASSA](http://robokassa.ru/en/Doc/En/Interface.aspx) usage. Can be used in a Symfony2 project, as well as in plain PHP.
### Usage
```php
$Robokassa = new Bobrovnikov\RobokassaBundle\Robokassa('login', 'pass1', 'pass2', $isShopActive = false);
$Robokassa
    ->setOutSum(299)
    ->setInvId(9876)
    ->setDesc('Payment for hosting services')
    ->addParam('IncCurrLabel', '')
    ->addCustomParam('user_id', 123)
    ;
```

There are multiple ways to bring user to payment page.
1. Form submit
```html
<form action="<?php echo $Robokassa->getEndpointAction() ?>" method="post">
    <?php foreach ($Robokassa->getAllParams() as $name => $value): ?>
        <input type="hidden" name="<?php echo $name ?>" value="<?php echo htmlspecialchars($value) ?>">
    <?php endforeach ?>
    <button type="submit">Pay</button>
</form>
```

2. Via link
```html
<a href="<?php echo $Robokassa->getEndpointQuery() ?>">Pay</a>
```

3. Redirect
```php
header('Location: ' . $Robokassa->getEndpointQuery());
```

Upon receiving data on Result URL script, we need to check security signature, set payment as successful in our own database and return specific response.
```php
if (!$Robokassa->isResultValid()) {
    exit('bad signature!');
}

// perform database query or billing request here
echo 'OK' . $requestInvId;
```
> It is the Result URL stage when payment is actually complete!

Finally there is Success URL page, where user is returned from payment gateway:
```php
if (!$Robokassa->isSuccessValid()) {
    exit('Something went wrong!');
}

// congratulate and thank user for his payment
```
