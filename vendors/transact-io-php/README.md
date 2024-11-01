## transact-io.php

This is a library for use with  transact.io

See an example in the demo/  folder


## Working with this repository
```
git clone  <repository URL>
cd transact-io-php
composer install
```

## Usage

Instancate a new instance of the TransactIoMsg class

```php
$transact = new TransactIoMsg(); // get new instance
```


**Required:** Set the secret that is in the Developer Settings / Keys 
of the transact publisher menu
```php
$transact->setSecret('Signing Secret'); // secret key
```


**Required:** Set the Account ID of who recieves the funds
```
$transact->setRecipient('5206507264147456'); // Account ID to pay
````


**Required:** set the URL of what is being purchased.   
```php
// Note that the host name MUST match the real name for cross site 
// messaging to work. 
$transact->setURL($_REQUEST['url']);
```

**Required:**  Set the price of the sale
```php
$transact->setPrice(2);
```

**Required**:  Set an item or product code.   This can be something
unique to the article or content you are selling.   The buyer 
will see this as part of the name of the charge
```php
$transact->setItem('ItemCode1'); // item code
```

**Optional**:  Set a Unique ID associated with this sale.  
```php
$transact->setUid('UniqueSaleID');
````


**Optional**:  Set meta data you want to save with this sale.  
```php
  // Set your own meta data
  // Note you must keep this short to avoid going over the 1024 byte limt
  // of the token URL
  $transact->setMeta(array(
    'your' => 'data',
    'anything' => 'you want'
  ));
```

### Get token for normal purchase

Get a token that will be passed to transact.js
```php
$transact->getToken();
```

### Get token for subscription

Get a token that will be passed to transact.js
```php
$transact->getSubscriptionToken();
```

-------

## Integrating  your HTML / javascript


See the [Example in the demo](/demo/) folder


