# Airalo PHP SDK
Airalo's PHP SDK provides extremely simple integration with the RESTful API and adds extra layer of security on top.<br>
The SDK supports:
- auto authentication and encryption<br>
- auto caching and rate limit handling<br>
- packages fetching of local, global, country and all combined<br>
- packages auto pagination on endpoints<br>
- package ordering<br>
- package bulk order allowing different packages and quantities to be bought at once<br>
- package topup ordering<br>
- unified response format of type `EasyAccess` which allows key access as associative array and/or object at the same time without json_decode usage<br>
- compatible with Unix, macOS, Windows operating systems<br>

# Requisites
- PHP version >= `7.4`
- `cURL` extension enabled in php.ini (enabled by default)
- `sodium` extension enabled in php.ini (enabled by default)

# Autoloading
We highly encourage to always use `composer` as a dependency managed and simply require Airalo's SDK by simply running this CLI command:
```
composer require airalo/sdk
```
Then in your codebase you will have access to the `Airalo` class by having required `autoload.php` file already.

For legacy projects or projects which still do not use composer, please make sure to require `alo.php` custom autoloader from the SDK:
```php
<?php

require_once __DIR__ . '/alo.php';
```

# Initialization
- Object usage:
```php
<?php

require __DIR__ . '/vendor/autoload.php';
// require_once __DIR__ . '/alo.php'; // if not using composer

use Airalo\Airalo;

$alo = new Airalo([
    'client_id' => '<YOUR_API_CLIENT_ID>',              // mandatory
    'client_secret' => '<YOUR_API_CLIENT_SECRET>',      // mandatory
    'env' => 'sandbox',                                 // optional, defaults to `production`
]);

$allPackages = $alo->getAllPackages(true);

```
- Static usage:
```php
<?php

require __DIR__ . '/vendor/autoload.php';
// require_once __DIR__ . '/alo.php'; // if not using composer

use Airalo\AiraloStatic;

// `init` must be called before using any of the methods otherwise an AiraloException will be thrown
AiraloStatic::init([
    'client_id' => '<YOUR_API_CLIENT_ID>',              // mandatory
    'client_secret' => '<YOUR_API_CLIENT_SECRET>',      // mandatory
    'env' => 'sandbox',                                 // optional, defaults to `production`
]);

$allPackages = AiraloStatic::getAllPackages(true);
```

# EasyAccess responses
The SDK provides simple and yet powerful way to interact with the response objects.<br>
By default you will be able to access them as an associative array or object without the need to modify them.<br>

Example:
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Airalo\Airalo;
use Airalo\AiraloStatic;

$alo = new Airalo([
    'client_id' => '<YOUR_API_CLIENT_ID>',
    'client_secret' => '<YOUR_API_CLIENT_SECRET>',
]);

$allPackages = $alo->getAllPackages(true);
// fully accessed as an object without any changes of the response
$packageId = $allPackages->data->{0}->package_id;
// fully accessed as an associative array without any changes of the response
$packageId = $allPackages['data'][0]['package_id'];
// mixed usage
$packageId = $allPackages['data'][0]->package_id;

// easy string convert to raw JSON format
$jsonString = (string)$allPackages;
```

# Methods Interface
<h2> Packages </h2>

>**_NOTE:_**<br>
>Passing `true` to `$flat` parameter makes the response significantly more compact and easy to handle. However it differes than the main one returned from the endpoints. Be mindful in which occassions you will need the original and in which the compact version. Happy coding!

`public function getAllPackages(bool $flat = false, $limit = null, $page = null): ?EasyAccess`<br>
Fetching all of Airalo's packages. By default the response will be the same as the one from packages REST endpoint (more here: https://partners-doc.airalo.com/#d775be27-4c08-45d1-9faa-8ec2c4f97bf5). Passing `$flat` as true will return package objects data in a single data object, example:
```json
{
  "data": [
    {
      "package_id": "meraki-mobile-7days-1gb",
      "slug": "greece",
      "type": "sim",
      "price": 5,
      "net_price": 4,
      "amount": 1024,
      "day": 7,
      "is_unlimited": false,
      "title": "1 GB - 7 Days",
      "data": "1 GB",
      "short_info": "This eSIM doesn't come with a phone number.",
      "voice": null,
      "text": null,
      "plan_type": "data",
      "activation_policy": "first-usage",
      "operator": {
        "title": "Meraki Mobile",
        "is_roaming": true,
        "info": [
          "LTE Data-only eSIM.",
          "Rechargeable online with no expiry.",
          "Operates on the Wind network in Greece."
        ]
      },
      "countries": [
            "GR"
        ]
    },
    {
      "package_id": "meraki-mobile-7days-1gb-topup",
      "slug": "greece",
      "type": "topup",
      "price": 5,
      "net_price": 4,
      "amount": 1024,
      "day": 7,
      "is_unlimited": false,
      "title": "1 GB - 7 Days",
      "data": "1 GB",
      "short_info": null,
      "voice": null,
      "text": null,
      "plan_type": "data",
      "activation_policy": "first-usage",
      "operator": {
        "title": "Meraki Mobile",
        "is_roaming": true,
        "info": [
          "LTE Data-only eSIM.",
          "Rechargeable online with no expiry.",
          "Operates on the Wind network in Greece."
        ]
      },
      "countries": [
        "GR"
      ]
    }
  ]
}
```
By default no limit number of packages will be applied if `$limit` is empty<br>
By default it will paginate all pages (multiple calls) or if `$page` is provided it will be the starting pagination index.


`public function getLocalPackages(bool $flat = false, $limit = null, $page = null): ?EasyAccess`<br>
Fetching local Airalo packages. By default the response will be the same as the one from packages REST endpoint (more here: https://partners-doc.airalo.com/#d775be27-4c08-45d1-9faa-8ec2c4f97bf5). Passing `$flat` as true will return package objects data in a single data object.<br>
By default no limit number of packages will be applied if `$limit` is empty<br>
By default it will paginate all pages (multiple calls) or if `$page` is provided it will be the starting pagination index.<br>


`public function getGlobalPackages(bool $flat = false, $limit = null, $page = null): ?EasyAccess`<br>
Fetching global Airalo packages. By default the response will be the same as the one from packages REST endpoint (more here: https://partners-doc.airalo.com/#d775be27-4c08-45d1-9faa-8ec2c4f97bf5). Passing `$flat` as true will return package objects data in a single data object.<br>
By default no limit number of packages will be applied if `$limit` is empty<br>
By default it will paginate all pages (multiple calls) or if `$page` is provided it will be the starting pagination index.<br>


`public function getCountryPackages(string $countryCode, bool $flat = false, $limit = null): ?EasyAccess`<br>
Fetching country specific Airalo packages. By default the response will be the same as the one from packages REST endpoint (more here: https://partners-doc.airalo.com/#d775be27-4c08-45d1-9faa-8ec2c4f97bf5). Passing `$flat` as true will return package objects data in a single data object.<br>
By default no limit number of packages will be applied if `$limit` is empty<br>
By default it will paginate all pages (multiple calls) or if `$page` is provided it will be the starting pagination index.<br>

`public function getSimPackages(bool $flat = false, $limit = null, $page = null): ?EasyAccess`<br>
Fetching Sim only Airalo packages without top ups. By default the response will be the same as the one from packages REST endpoint (more here: https://partners-doc.airalo.com/#d775be27-4c08-45d1-9faa-8ec2c4f97bf5). Passing `$flat` as true will return package objects data in a single data object.<br>
By default no limit number of packages will be applied if `$limit` is empty<br>
By default it will paginate all pages (multiple calls) or if `$page` is provided it will be the starting pagination index.<br>

<h2> Orders </h2>

`public function order(string $packageId, int $quantity, ?string $description = null): ?EasyAccess`<br>
Places an order for a given package id (fetched from any of the packages calls) and calls `order` endpoint of the REST API.
Full response example can be found here: https://partners-doc.airalo.com/#768fbbc7-b649-4fb5-9755-be579333a2d9<br>
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Airalo\Airalo;
use Airalo\AiraloStatic;

$alo = new Airalo([
    'client_id' => '<YOUR_API_CLIENT_ID>',
    'client_secret' => '<YOUR_API_CLIENT_SECRET>',
]);

$allPackages = $alo->getAllPackages(true);
$packageId = $allPackages->data->{0}->package_id;

$order = $alo->order($packageId, 1);

//
// Static usage
//
AiraloStatic::init([
    'client_id' => '<YOUR_API_CLIENT_ID>',
    'client_secret' => '<YOUR_API_CLIENT_SECRET>',
]);

$allPackages = AiraloStatic::getAllPackages(true);
$packageId = $allPackages->data->{0}->package_id;

$order = AiraloStatic::order($packageId, 1);
```


`public function orderBulk(array $packages, ?string $description = null): ?EasyAccess`<br>
Parameters: array `$packages` where the key is the package name and the value represents the desired quantity.
Parallel ordering for multiple packages (up to 50 different package ids) within the same function call. Example usage:<br>
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Airalo\Airalo;
use Airalo\AiraloStatic;

$alo = new Airalo([
    'client_id' => '<YOUR_API_CLIENT_ID>',
    'client_secret' => '<YOUR_API_CLIENT_SECRET>',
]);

$allPackages = $alo->getAllPackages(true);
$firstPackageId = $allPackages['data'][0]->package_id;
$secondPackageId = $allPackages['data'][1]->package_id;
$thirdPackageId = $allPackages['data'][2]->package_id;

$orders = $aa->orderBulk([
    $firstPackageId => 2,
    $secondPackageId => 1,
]);

//
// Static usage
//
AiraloStatic::init([
    'client_id' => '<YOUR_API_CLIENT_ID>',
    'client_secret' => '<YOUR_API_CLIENT_SECRET>',
]);

$allPackages = AiraloStatic::getAllPackages(true);
$firstPackageId = $allPackages['data'][0]->package_id;
$secondPackageId = $allPackages['data'][1]->package_id;
$thirdPackageId = $allPackages['data'][2]->package_id;

$orders = AiraloStatic::orderBulk([
    $firstPackageId => 2,
    $secondPackageId => 1,
]);

```

Example response:<br>
```json
{
  "change-7days-1gb": {
    "data": {
      "id": 77670,
      "code": "20240514-077670",
      "currency": "USD",
      "package_id": "change-7days-1gb",
      "quantity": 1,
      "type": "sim",
      "description": "Bulk order placed via Airalo PHP SDK",
      "esim_type": "Prepaid",
      "validity": 7,
      "package": "Change-1 GB - 7 Days",
      "data": "1 GB",
      "price": 4.5,
      "created_at": "2024-05-14 11:48:47",
      "manual_installation": "<p><b>eSIM name:</b> Change</p><p><b>Coverage: </b>United States</p><p><b>To manually activate the eSIM on your eSIM capable device:</b></p><ol><li>Settings > Cellular/Mobile > Add Cellular/Mobile Plan.</li><li>Manually enter the SM-DP+ Address and activation code.</li><li>Confirm eSIM plan details.</li><li>Label the eSIM.</li></ol><p><b>To access Data:</b></p><ol><li>Enable data roaming.</li></ol>",
      "qrcode_installation": "<p><b>eSIM name:</b> Change</p><p><b>Coverage: </b>United States</p><p><b>To activate the eSIM by scanning the QR code on your eSIM capable device you need to print or display this QR code on other device:</b></p><ol><li>Settings > Cellular/Mobile > Add Cellular/Mobile Plan.</li><li>Scan QR code.</li><li>Confirm eSIM plan details.</li><li>Label the eSIM.</li></ol><p><b>To access Data:</b></p><ol><li>Enable data roaming.</li></ol>",
      "installation_guides": {
        "en": "https://www.airalo.com/help/getting-started-with-airalo"
      },
      "text": null,
      "voice": null,
      "net_price": 3.6,
      "sims": [
        {
          "id": 102795,
          "created_at": "2024-05-14 11:48:47",
          "iccid": "893000000000034143",
          "lpa": "lpa.airalo.com",
          "imsis": null,
          "matching_id": "TEST",
          "qrcode": "LPA:1$lpa.airalo.com$TEST",
          "qrcode_url": "https://sandbox.airalo.com/qr?expires=1802000927&id=137975&signature=b4e731d218fdc707b677c89d54d41773d250a38c160cf7d97f6e9493b5fec0ee",
          "airalo_code": null,
          "apn_type": "automatic",
          "apn_value": null,
          "is_roaming": true,
          "confirmation_code": null,
          "apn": {
            "ios": {
              "apn_type": "automatic",
              "apn_value": null
            },
            "android": {
              "apn_type": "automatic",
              "apn_value": null
            }
          },
          "msisdn": null
        },
        {
          "id": 102795,
          "created_at": "2024-05-14 11:48:47",
          "iccid": "893000000000034143",
          "lpa": "lpa.airalo.com",
          "imsis": null,
          "matching_id": "TEST",
          "qrcode": "LPA:1$lpa.airalo.com$TEST",
          "qrcode_url": "https://sandbox.airalo.com/qr?expires=1802000927&id=137975&signature=b4e731d218fdc707b677c89d54d41773d250a38c160cf7d97f6e9493b5fec0ee",
          "airalo_code": null,
          "apn_type": "automatic",
          "apn_value": null,
          "is_roaming": true,
          "confirmation_code": null,
          "apn": {
            "ios": {
              "apn_type": "automatic",
              "apn_value": null
            },
            "android": {
              "apn_type": "automatic",
              "apn_value": null
            }
          },
          "msisdn": null
        }
      ]
    },
    "meta": {
      "message": "success"
    }
  },
  "change-7days-1gb-topup": {
    "data": {
      "id": 77671,
      "code": "20240514-077671",
      "currency": "USD",
      "package_id": "change-7days-1gb-topup",
      "quantity": 1,
      "type": "sim",
      "description": "Bulk order placed via Airalo PHP SDK",
      "esim_type": "Prepaid",
      "validity": 7,
      "package": "Change-1 GB - 7 Days",
      "data": "1 GB",
      "price": 4.5,
      "created_at": "2024-05-14 11:48:47",
      "manual_installation": "<p><b>eSIM name:</b> Change</p><p><b>Coverage: </b>United States</p><p><b>To manually activate the eSIM on your eSIM capable device:</b></p><ol><li>Settings > Cellular/Mobile > Add Cellular/Mobile Plan.</li><li>Manually enter the SM-DP+ Address and activation code.</li><li>Confirm eSIM plan details.</li><li>Label the eSIM.</li></ol><p><b>To access Data:</b></p><ol><li>Enable data roaming.</li></ol>",
      "qrcode_installation": "<p><b>eSIM name:</b> Change</p><p><b>Coverage: </b>United States</p><p><b>To activate the eSIM by scanning the QR code on your eSIM capable device you need to print or display this QR code on other device:</b></p><ol><li>Settings > Cellular/Mobile > Add Cellular/Mobile Plan.</li><li>Scan QR code.</li><li>Confirm eSIM plan details.</li><li>Label the eSIM.</li></ol><p><b>To access Data:</b></p><ol><li>Enable data roaming.</li></ol>",
      "installation_guides": {
        "en": "https://www.airalo.com/help/getting-started-with-airalo"
      },
      "text": null,
      "voice": null,
      "net_price": 3.6,
      "sims": [
        {
          "id": 102796,
          "created_at": "2024-05-14 11:48:47",
          "iccid": "893000000000034144",
          "lpa": "lpa.airalo.com",
          "imsis": null,
          "matching_id": "TEST",
          "qrcode": "LPA:1$lpa.airalo.com$TEST",
          "qrcode_url": "https://sandbox.airalo.com/qr?expires=1802000927&id=137976&signature=978adede2174b6de7d2502841d6d901d417d643570dd6172c71733cde5f72503",
          "airalo_code": null,
          "apn_type": "automatic",
          "apn_value": null,
          "is_roaming": true,
          "confirmation_code": null,
          "apn": {
            "ios": {
              "apn_type": "automatic",
              "apn_value": null
            },
            "android": {
              "apn_type": "automatic",
              "apn_value": null
            }
          },
          "msisdn": null
        }
      ]
    },
    "meta": {
      "message": "success"
    }
  }
}
```
>**_NOTE:_**<br>
>Each package id is a key in the returned response. The quantity of `sims` object represents the ordered quantity from the initial call.
><br><b>If an error occurs in one of the parallel orders, the error REST response will be assigned to the package id key, so you must make sure to validate each response</b>

Example:
```json
{
  "change-7days-1gb": {"data":{"quantity":"The quantity may not be greater than 50."},"meta":{"message":"the parameter is invalid"}},
  "change-7days-1gb-topup": {
    "data": {
      "id": 77671,
      "code": "20240514-077671",
      "currency": "USD",
      "package_id": "change-7days-1gb-topup",
      "quantity": 1,
      "type": "sim",
      "description": "Bulk order placed via Airalo PHP SDK",
      "esim_type": "Prepaid",
      "validity": 7,
      "package": "Change-1 GB - 7 Days",
      "data": "1 GB",
      "price": 4.5,
      "created_at": "2024-05-14 11:48:47",
      "manual_installation": "<p><b>eSIM name:</b> Change</p><p><b>Coverage: </b>United States</p><p><b>To manually activate the eSIM on your eSIM capable device:</b></p><ol><li>Settings > Cellular/Mobile > Add Cellular/Mobile Plan.</li><li>Manually enter the SM-DP+ Address and activation code.</li><li>Confirm eSIM plan details.</li><li>Label the eSIM.</li></ol><p><b>To access Data:</b></p><ol><li>Enable data roaming.</li></ol>",
      "qrcode_installation": "<p><b>eSIM name:</b> Change</p><p><b>Coverage: </b>United States</p><p><b>To activate the eSIM by scanning the QR code on your eSIM capable device you need to print or display this QR code on other device:</b></p><ol><li>Settings > Cellular/Mobile > Add Cellular/Mobile Plan.</li><li>Scan QR code.</li><li>Confirm eSIM plan details.</li><li>Label the eSIM.</li></ol><p><b>To access Data:</b></p><ol><li>Enable data roaming.</li></ol>",
      "installation_guides": {
        "en": "https://www.airalo.com/help/getting-started-with-airalo"
      },
      "text": null,
      "voice": null,
      "net_price": 3.6,
      "sims": [
        {
          "id": 102796,
          "created_at": "2024-05-14 11:48:47",
          "iccid": "893000000000034144",
          "lpa": "lpa.airalo.com",
          "imsis": null,
          "matching_id": "TEST",
          "qrcode": "LPA:1$lpa.airalo.com$TEST",
          "qrcode_url": "https://sandbox.airalo.com/qr?expires=1802000927&id=137976&signature=978adede2174b6de7d2502841d6d901d417d643570dd6172c71733cde5f72503",
          "airalo_code": null,
          "apn_type": "automatic",
          "apn_value": null,
          "is_roaming": true,
          "confirmation_code": null,
          "apn": {
            "ios": {
              "apn_type": "automatic",
              "apn_value": null
            },
            "android": {
              "apn_type": "automatic",
              "apn_value": null
            }
          },
          "msisdn": null
        }
      ]
    },
    "meta": {
      "message": "success"
    }
  }
}
```
<h2> Vouchers </h2>

`public function voucher(int $usageLimit, int $amount, int $quantity, ?bool $isPaid = false, string $voucherCode = null): ?EasyAccess`<br>
calls `voucher` endpoint of the REST API.
Full response example can be found here: https://partners-doc.airalo.com/#768fbbc7-b649-4fb5-9755-be579333a2d9<br>
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Airalo\Airalo;
use Airalo\AiraloStatic;

$alo = new Airalo([
    'client_id' => '<YOUR_API_CLIENT_ID>',
    'client_secret' => '<YOUR_API_CLIENT_SECRET>',
]);


$vouchers = $alo->voucher( 40, 22, 1, false,'ABC111');

//
// Static usage
//
AiraloStatic::init([
    'client_id' => '<YOUR_API_CLIENT_ID>',
    'client_secret' => '<YOUR_API_CLIENT_SECRET>',
]);


$vouchers = AiraloStatic::voucher(40, 22, 1, false,'ABC111');
```


Example response:<br>
```json
{
  "data": {
    "id": 8,
    "code": "ABC111",
    "usage_limit": 40,
    "amount": 22,
    "is_paid": false,
    "created_at": "2024-06-10 07:23:24"
  },
  "meta": {
    "message": "success"
  }
}
```



<h2> Topups </h2>

`public function topup(string $packageId, string $iccid, ?string $description = null): ?EasyAccess`<br>

Places a topup for a given package id and iccid of an eSIM and calls `topups` endpoint of the REST API.<br>
Full response example can be found here: https://partners-doc.airalo.com/#e411d932-2993-463f-a548-754c47ac7c00<br>

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Airalo\Airalo;
use Airalo\AiraloStatic;

$alo = new Airalo([
    'client_id' => '<YOUR_API_CLIENT_ID>',
    'client_secret' => '<YOUR_API_CLIENT_SECRET>',
]);

$allPackages = $alo->getAllPackages(true);
$packageId = $allPackages->data->{0}->package_id;

$order = $alo->order($packageId, 1);
$iccid = $orders['bul-7gb-3days']['data']['sims'][0]['iccid'];

$topup = $alo->topup($packageId, $iccid);

//
// Static usage
//
AiraloStatic::init([
    'client_id' => '<YOUR_API_CLIENT_ID>',
    'client_secret' => '<YOUR_API_CLIENT_SECRET>',
]);

$allPackages = AiraloStatic::getAllPackages(true);
$packageId = $allPackages->data->{0}->package_id;

$order = AiraloStatic::order($packageId, 1);
$iccid = $orders['bul-7gb-3days']['data']['sims'][0]['iccid'];

$topup = AiraloStatic::topup($packageId, $iccid);
```

<h2> Sim Usage </h2>

`public function simUsage(string $iccid): ?EasyAccess`<br>

Places an $iccid with user iccid  and calls `simUsage` endpoint of the REST API. <br>
Full response example can be found here: https://partners-doc.airalo.com/#e411d932-2993-463f-a548-754c47ac7c00<br>

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Airalo\Airalo;
use Airalo\AiraloStatic;

$alo = new Airalo([
    'client_id' => '<YOUR_API_CLIENT_ID>',
    'client_secret' => '<YOUR_API_CLIENT_SECRET>',
]);


$topup = $alo->simUsage($iccid);

//
// Static usage
//
AiraloStatic::init([
    'client_id' => '<YOUR_API_CLIENT_ID>',
    'client_secret' => '<YOUR_API_CLIENT_SECRET>',
]);


$topup = AiraloStatic::simUsage($iccid);
```

Example response can be found in the API documentation (link above). <br>
<h2> Sim Instructions </h2>

`public function getSimInstructions(string $iccid, string $language = "en"): ?EasyAccess`<br>
Places an $iccid with user iccid & $language with language like en,de. by default its en and calls `getSimInstructions` endpoint of the REST API.
Full response example can be found here: https://partners-doc.airalo.com/#768fbbc7-b649-4fb5-9755-be579333a2d9<br>
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Airalo\Airalo;
use Airalo\AiraloStatic;

$alo = new Airalo([
    'client_id' => '<YOUR_API_CLIENT_ID>',
    'client_secret' => '<YOUR_API_CLIENT_SECRET>',
]);

$instructions = $alo->getSimInstructions('893000000000002115');

//
// Static usage
//
AiraloStatic::init([
    'client_id' => '<YOUR_API_CLIENT_ID>',
    'client_secret' => '<YOUR_API_CLIENT_SECRET>',
]);


$order = AiraloStatic::getSimInstructions('893000000000002115');
```
Example response:<br>
```json
{
  "data": {
    "instructions": {
      "language": "EN",
      "ios": [
        {
          "model": null,
          "version": "15.0,14.0.,13.0,12.0",
          "installation_via_qr_code": {
            "steps": {
              "1": "Go to Settings > Cellular/Mobile > Add Cellular/Mobile Plan.",
              "2": "Scan the QR Code.",
              "3": "Tap on 'Add Cellular Plan'.",
              "4": "Label the eSIM.",
              "5": "Choose preferred default line to call or send messages.",
              "6": "Choose the preferred line to use with iMessage, FaceTime, and Apple ID.",
              "7": "Choose the eSIM plan as your default line for Cellular Data and do not turn on 'Allow Cellular Data Switching' to prevent charges on your other line.",
              "8": "Your eSIM has been installed successfully, please scroll down to see the settings for accessing data."
            },
            "qr_code_data": "5a30d830-cfa9-4353-8d76-f103351d53b6",
            "qr_code_url": "https://www.conroy.biz/earum-dolor-qui-molestiae-at"
          },
          "installation_manual": {
            "steps": {
              "1": "Go to Settings > Cellular/Mobile > Add Cellular/Mobile Plan.",
              "2": "Tap on 'Enter Details Manually'.",
              "3": "Enter your SM-DP+ Address and Activation Code.",
              "4": "Tap on 'Add Cellular Plan'.",
              "5": "Label the eSIM.",
              "6": "Choose preferred default line to call or send messages.",
              "7": "Choose the preferred line to use with iMessage, FaceTime, and Apple ID.",
              "8": "Choose the eSIM plan as your default line for Cellular Data and do not turn on 'Allow Cellular Data Switching' to prevent charges on your other line.",
              "9": "Your eSIM has been installed successfully, please scroll down to see the settings for accessing data."
            },
            "smdp_address_and_activation_code": "6a7f7ab6-6469-461d-8b17-2ee5c0207d22"
          },
          "network_setup": {
            "steps": {
              "1": "Select your  eSIM under 'Cellular Plans'.",
              "2": "Ensure that 'Turn On This Line' is toggled on.",
              "3": "Go to 'Network Selection' and select the supported network.",
              "4": "Need help? Chat with us."
            },
            "apn_type": "manual",
            "apn_value": "globaldata",
            "is_roaming": null
          }
        },
        {
          "model": null,
          "version": null,
          "installation_via_qr_code": {
            "steps": {
              "1": "Go to Settings > Cellular/Mobile Data > Add eSIM or Set up Cellular/Mobile Service > Use QR Code on your device. ",
              "2": "Scan the QR code available on the  app, then tap “Continue” twice and wait for a while. Your eSIM will connect to the network, this may take a few minutes, then tap “Done”.",
              "3": "Choose a label for your new eSIM plan.",
              "4": "Choose “Primary” for your default line, then tap “Continue”.",
              "5": "Choose “Primary” you want to use with iMessage and FaceTime for your Apple ID, then tap “Continue”.",
              "6": "Choose your new eSIM plan for cellular/mobile data, then tap “Continue”."
            },
            "qr_code_data": "5a30d830-cfa9-4353-8d76-f103351d53b6",
            "qr_code_url": "https://www.conroy.biz/earum-dolor-qui-molestiae-at"
          },
          "installation_manual": {
            "steps": {
              "1": "Go to Settings > Cellular/Mobile Data > Add eSIM or Set up Cellular/Mobile Service > Use QR Code on your device.",
              "2": "Tap “Enter Details Manually” and enter the SM-DP+ Address and Activation Code available on the  app by copying them, tap “Next”, then tap “Continue” twice and wait for a while. Your eSIM will connect to the network, this may take a few minutes, then tap “Done”.",
              "3": "Choose a label for your new eSIM plan.",
              "4": "Choose “Primary” for your default line, then tap “Continue”.",
              "5": "Choose “Primary” you want to use with iMessage and FaceTime for your Apple ID, then tap “Continue”.",
              "6": "Choose your new eSIM plan for cellular/mobile data, then tap “Continue”."
            },
            "smdp_address_and_activation_code": "6a7f7ab6-6469-461d-8b17-2ee5c0207d22"
          },
          "network_setup": {
            "steps": {
              "1": "Go to “Cellular/Mobile Data”, then select the recently downloaded eSIM on your device. Enable the “Turn On This Line” toggle, then select your new eSIM plan for cellular/mobile data. ",
              "2": "Tap “Network Selection”, disable the “Automatic” toggle, then select the supported network available on the  app manually if your eSIM has connected to the wrong network."
            },
            "apn_type": "manual",
            "apn_value": "globaldata",
            "is_roaming": null
          }
        }
      ],
      "android": [
        {
          "model": null,
          "version": null,
          "installation_via_qr_code": {
            "steps": {
              "1": "Go to Settings > Connections > SIM Card Manager.",
              "2": "Tap on 'Add Mobile Plan'.",
              "3": "Tap on 'Scan Carrier QR Code' and tap on 'Add'.",
              "4": "When the plan has been registered, tap 'Ok' to turn on a new mobile plan.",
              "5": "Your eSIM has been installed successfully, please scroll down to see the settings for accessing data."
            },
            "qr_code_data": "5a30d830-cfa9-4353-8d76-f103351d53b6",
            "qr_code_url": "https://www.conroy.biz/earum-dolor-qui-molestiae-at"
          },
          "installation_manual": {
            "steps": {
              "1": "Go to Settings > Connections > SIM Card Manager.",
              "2": "Tap on 'Add Mobile Plan'.",
              "3": "Tap on 'Scan Carrier QR Code' and tap on 'Enter code instead'.",
              "4": "Enter the Activation Code (SM-DP+ Address & Activation Code).",
              "5": "When the plan has been registered, tap 'Ok' to turn on a new mobile plan.",
              "6": "Your eSIM has been installed successfully, please scroll down to see the settings for accessing data."
            },
            "smdp_address_and_activation_code": "6a7f7ab6-6469-461d-8b17-2ee5c0207d22"
          },
          "network_setup": {
            "steps": {
              "1": "In the 'SIM Card Manager' select your  eSIM.",
              "2": "Ensure that your eSIM is turned on under 'Mobile Networks'.",
              "3": "Enable the Mobile Data.",
              "4": "Go to Settings > Connections > Mobile networks > Network Operators.",
              "5": "Ensure that the supported network is selected.",
              "6": "Need help? Chat with us."
            },
            "apn_type": "automatic",
            "apn_value": "globaldata",
            "is_roaming": null
          }
        },
        {
          "model": null,
          "version": null,
          "installation_via_qr_code": {
            "steps": {
              "1": "Go to Settings > Network & internet.",
              "2": "Tap on the '+' (Add) icon next to the Mobile network.",
              "3": "Tap 'Next' when asked, “Don’t have a SIM card?”.",
              "4": "Scan the QR Code.",
              "5": "Your eSIM has been installed successfully, please scroll down to see the settings for accessing data."
            },
            "qr_code_data": "5a30d830-cfa9-4353-8d76-f103351d53b6",
            "qr_code_url": "https://www.conroy.biz/earum-dolor-qui-molestiae-at"
          },
          "installation_manual": {
            "steps": {
              "1": "Go to Settings > Network & internet.",
              "2": "Tap on the '+' (Add) icon next to the Mobile network.",
              "3": "Tap on 'Next' when asked, “Don’t have a SIM card?”.",
              "4": "Tap 'Enter Code Manually'. You will be asked to enter your Activation Code (SM-DP+ Adress & Activation Code).",
              "5": "Your eSIM has been installed successfully, please scroll down to see the settings for accessing data."
            },
            "smdp_address_and_activation_code": "6a7f7ab6-6469-461d-8b17-2ee5c0207d22"
          },
          "network_setup": {
            "steps": {
              "1": "Go to Network & internet and tap on 'Mobile network'.",
              "2": "Connect manually to the supported network.",
              "3": "Turn on eSIM under 'Mobile network'.",
              "4": "Enable the Mobile Data.",
              "5": "Need help? Chat with us."
            },
            "apn_type": "automatic",
            "apn_value": "globaldata",
            "is_roaming": null
          }
        },
        {
          "model": "Galaxy",
          "version": "1",
          "installation_via_qr_code": {
            "steps": {
              "1": "Go to “Settings”, tap “Connections”, then tap “SIM card manager” on your device.",
              "2": "Tap “Add mobile plan”, then tap “Scan carrier QR code”.",
              "3": "Tap “Enter activation code”.",
              "4": "Enter the SM-DP+ Address & Activation Code by copying it, tap “Connect”, then tap “Confirm”."
            },
            "qr_code_data": "5a30d830-cfa9-4353-8d76-f103351d53b6",
            "qr_code_url": "https://www.conroy.biz/earum-dolor-qui-molestiae-at"
          },
          "installation_manual": {
            "steps": {
              "1": "Go to “Settings”, tap “Connections”, then tap “SIM card manager” on your device.",
              "2": "Tap “Add mobile plan”, then tap “Scan carrier QR code”.",
              "3": "Tap “Enter activation code”.",
              "4": "Enter the SM-DP+ Address & Activation Code by copying it, tap “Connect”, then tap “Confirm”."
            },
            "smdp_address_and_activation_code": "6a7f7ab6-6469-461d-8b17-2ee5c0207d22"
          },
          "network_setup": {
            "steps": {
              "1": "Go to “Settings”, tap “Connections”, then tap “SIM card manager” on your device.",
              "2": "Tap “Add mobile plan”, then tap “Scan carrier QR code”.",
              "3": "Tap “Enter activation code”.",
              "4": "Enter the SM-DP+ Address & Activation Code by copying it, tap “Connect”, then tap “Confirm”."
            },
            "apn_type": "automatic",
            "apn_value": "globaldata",
            "is_roaming": null
          }
        }
      ]
    }
  },
  "meta": {
    "message": "success"
  }
}
```



# Techincal notes
- Encrypted auth tokens are automatically cached in filesystem for 24h.
- Caching is automatically stored in filesystem for 1h.
- Utilize the `mock()` methods in Airalo and AiraloStatic for seamless stubbing with your own unit tests.
- All exceptions thrown by the SDK are instance of `AiraloException`.
- To clear all cache (not recommended to clear cache on production often) you can just do the following:
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Airalo\Helpers\Cached;

Cached::clearCache();
```
