# type-identifier
Effective primitive type identifier.
A free library to identify and sanitize data from associative array, super global array or Http Request

## Prerequisites
This library can ben instaled inside a projeti with PHP 5.6, but is 100% ready for PHP 8.1 and PHP doc is PHP 8.1 oriented.
PHP 5.6 need this library more than newest version, but it can be useful inside a brand new project too to handle and sanitize,
HTTP Request values or heterogeneous associative array

## Some example

### Load dependencie
```php
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;
```

```php
$ept = new EffectivePrimitiveTypeIdentifierService();
$result = $ept->getTypedValue(1);   // Result will be 1 with type int
```

```php
$ept = new EffectivePrimitiveTypeIdentifierService();
$result = $ept->getTypedValue("1");   // Result will be 1 with type int
```

```php
$array["value"] = "1.1";
$ept = new EffectivePrimitiveTypeIdentifierService();
$result = $ept->getTypedValue($array["value"]);  // Result will be 1.1 with type float
```

```php
$value = "1.1a";
$array["value"] = $value;
$ept = new EffectivePrimitiveTypeIdentifierService();
$result = $ept->getTypedValue($array["value"]); // Result will be "1.1a" with type string
```

```php
$ept = new EffectivePrimitiveTypeIdentifierService();
$result = $ept->getTypedValue(1 === 1); // result will be true type bool
```

```php
$ept = new EffectivePrimitiveTypeIdentifierService();
$result = $ept->getTypedValue("snipershady       ", true); // Trim enabled. Result will be "snipershady" without any whitespace and type string
```

### Force string sanitizing
```php
$ept = new EffectivePrimitiveTypeIdentifierService();
$trim = true; // Can be false, is not mandatory if you want to force string sanitizing
$forceString = true;
$result = $ept->getTypedValue("1", $trim, $forceString);   // Result will be "1" with type string and will not handled as integer
```

### Array sanitizing with offset validation 

```php
$value = "snipershady";
$array["value"] = $value;
$ept = new EffectivePrimitiveTypeIdentifierService();
$result = $ept->getTypedValueFromArray("value", $array);  // Result will be "snipershady" sanitized and type string
```

### Array sanitizing retrurn null on invalid offset
```php
$value = "snipershady";
$array["value"] = $value;
$ept = new EffectivePrimitiveTypeIdentifierService();
$result = $ept->getTypedValueFromArray("invalid_offset", $array);  // Result null. "invalid_offset" is not a valid offset for the array.
```