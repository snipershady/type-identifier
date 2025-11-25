# type-identifier
Effective primitive type identifier.
A lightweight library for identifying and sanitizing primitive data types.
It helps normalize values coming from associative arrays, superglobal arrays, or HTTP requests.

## Badges

[![Latest
Version](https://img.shields.io/packagist/v/snipershady/typeidentifier.svg)](https://packagist.org/packages/snipershady/typeidentifier)\
[![PHP
Versions](https://img.shields.io/badge/PHP-5.6%2B%20%7C%208.5%2B-blue)](#)\
[![License: GPL v2](https://img.shields.io/badge/License-GPLv2-blue.svg)](./LICENSE)


## Features

-   ✔ Automatically detects: **int**, **float**, **bool**, **string**, **null**\
-   ✔ Optional whitespace trimming\
-   ✔ Type forcing (e.g., force string)\
-   ✔ Safe extraction from arrays and superglobals (`$_GET`, `$_POST`)\
-   ✔ Consistent behavior across PHP versions\
-   ✔ Works even on legacy PHP 5.6 projects

## Installation

``` bash
composer require snipershady/type-identifier
```

## Requirements

This library supports:

-   **PHP 5.6+**
-   Fully compatible with **PHP 8.5+**, and the documentation is PHP 8
    oriented.

Although PHP 5.6 benefits greatly from this type of utility, the library
is also useful in modern PHP projects to safely handle and sanitize HTTP
request values or heterogeneous associative arrays.

## Usage Examples

### Import the service
```php
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;
```
## Usage Examples

### Import the service

``` php
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;
```

### Basic type identification

``` php
$ept = new EffectivePrimitiveTypeIdentifierService();

// Result: 1 (int)
$result = $ept->getTypedValue(1);
```

``` php
$ept = new EffectivePrimitiveTypeIdentifierService();

// Result: 1 (int)
$result = $ept->getTypedValue("1");
```

``` php
$array["value"] = "1.1";

// Result: 1.1 (float)
$result = $ept->getTypedValue($array["value"]);
```

``` php
$array["value"] = "1.1a";

// Result: "1.1a" (string)
$result = $ept->getTypedValue($array["value"]);
```

### Boolean evaluation

``` php
$ept = new EffectivePrimitiveTypeIdentifierService();

// Result: true (bool)
$result = $ept->getTypedValue(1 === 1);
```

### Automatic trimming

``` php
$ept = new EffectivePrimitiveTypeIdentifierService();

// Result: "snipershady" (string)
$result = $ept->getTypedValue("snipershady       ", true);
```

### Force string sanitization

``` php
$trim = true;
$forceString = true;

$ept = new EffectivePrimitiveTypeIdentifierService();

// Result: "1" (string)
$result = $ept->getTypedValue("1", $trim, $forceString);
```

## Array Sanitizing

### Valid key

``` php
$array["value"] = "snipershady";
$ept = new EffectivePrimitiveTypeIdentifierService();

// Result: "snipershady" (string)
$result = $ept->getTypedValueFromArray("value", $array);
```

### Invalid key → returns `null`

``` php
$ept = new EffectivePrimitiveTypeIdentifierService();

// Result: null
$result = $ept->getTypedValueFromArray("invalid_offset", $array);
```

## GET/POST Request Sanitizing

### From `$_POST`

``` php
$ept = new EffectivePrimitiveTypeIdentifierService();

$result = $ept->getTypedValueFromPost("user_id"); // typed value or null
$result = $ept->getTypedValueFromPost("invalid_offset"); // null
```

### From `$_GET`

``` php
$ept = new EffectivePrimitiveTypeIdentifierService();

$result = $ept->getTypedValueFromGet("user_id"); // typed value or null
$result = $ept->getTypedValueFromGet("invalid_offset"); // null
```

## License

This project is released under the **GPLv2**.\
See the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome!\
Feel free to open an issue or submit a pull request.
