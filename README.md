# type-identifier
Effective primitive type identifier.
A free library to identify and sanitize data from associative array, super global array or Http Request


```php
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;

$value = 1;
$ept = new EffectivePrimitiveTypeIdentifierService();
$result = $ept->returnStrictType($value);   // Result will be 1 with type int

```

```php
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;

$value = "1";
$ept = new EffectivePrimitiveTypeIdentifierService();
$result = $ept->returnStrictType($value);   // Result will be 1 with type int

```

```php
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;

$value = "1.1";
$array["value"] = $value;
$ept = new EffectivePrimitiveTypeIdentifierService();
$result = $ept->returnStrictType($array["value"]);  // Result will be 1.1 with type float

```

```php
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;

$value = "1.1a";
$array["value"] = $value;
$ept = new EffectivePrimitiveTypeIdentifierService();
$result = $ept->returnStrictType($array["value"]); // Result will be "1.1a" with type string
```

```php
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;

$value = 1 === 1;
$ept = new EffectivePrimitiveTypeIdentifierService();
$result = $ept->returnStrictType($value); // result will be true type bool
```

```php
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;

$value = "snipershady";
$whitespaces = "      ";
$ept = new EffectivePrimitiveTypeIdentifierService();
$result = $ept->returnStrictType($value.$whitespaces, true); // Trim enabled. Result will be "snipershady" without any whitespace and type string
```