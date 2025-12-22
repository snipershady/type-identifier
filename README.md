# type-identifier

A lightweight, robust PHP library for identifying and sanitizing primitive data types in real-world scenarios.

Perfect for normalizing values from associative arrays, superglobal arrays, HTTP requests, and any untyped data sources.

## Badges

[![Latest Version](https://img.shields.io/packagist/v/snipershady/typeidentifier.svg)](https://packagist.org/packages/snipershady/typeidentifier)
[![PHP Version](https://img.shields.io/packagist/php-v/snipershady/typeidentifier.svg)](https://www.php.net/)
[![License: GPL v2](https://img.shields.io/badge/License-GPLv2-blue.svg)](./LICENSE)
[![Stars](https://img.shields.io/github/stars/snipershady/type-identifier)](https://github.com/snipershady/type-identifier/stargazers)
[![Issues](https://img.shields.io/github/issues/snipershady/type-identifier)](https://github.com/snipershady/type-identifier/issues)

## Why type-identifier?

When working with HTTP requests, legacy codebases, or loosely-typed data sources, you often receive everything as strings. This library intelligently detects the actual primitive type and returns properly typed values, eliminating the need for repetitive manual casting and validation.

## Features

- ✅ **Smart type detection**: Automatically identifies `int`, `float`, `bool`, `string`, and `null`
- ✅ **Whitespace handling**: Optional trimming for clean string values
- ✅ **Type forcing**: Force values to remain as strings when needed
- ✅ **Array-safe extraction**: Safely retrieve typed values from arrays without `isset()` checks
- ✅ **Superglobal helpers**: Built-in methods for `$_GET`, `$_POST`, `$_SERVER`
- ✅ **Consistent behavior**: Works reliably across all PHP versions
- ✅ **Legacy support**: Compatible with PHP 5.6+ through PHP 8.5+
- ✅ **Zero dependencies**: Lightweight and focused

## Installation

```bash
composer require snipershady/type-identifier
```

## Requirements

- **PHP 5.6+** (fully compatible with PHP 8.5+)

While this library is particularly valuable for legacy PHP 5.6 projects lacking modern type systems, it remains useful in modern PHP applications for safely handling HTTP request values and heterogeneous data structures.

## Quick Start

```php
use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;

$ept = new EffectivePrimitiveTypeIdentifierService();

// String "1" becomes int 1
$result = $ept->getTypedValue("1"); // int(1)

// String "1.5" becomes float 1.5
$result = $ept->getTypedValue("1.5"); // float(1.5)

// Non-numeric string stays string
$result = $ept->getTypedValue("hello"); // string("hello")

// Automatic whitespace trimming
$result = $ept->getTypedValue("  hello  ", true); // string("hello")
```

## Usage Examples

### Basic Type Identification

```php
$ept = new EffectivePrimitiveTypeIdentifierService();

// Integer detection
$result = $ept->getTypedValue("1");
// Result: 1 (int)

// Float detection
$result = $ept->getTypedValue("1.1");
// Result: 1.1 (float)

// String preservation (non-numeric)
$result = $ept->getTypedValue("1.1a");
// Result: "1.1a" (string)

// Boolean values
$result = $ept->getTypedValue(true);
// Result: true (bool)

// Null handling
$result = $ept->getTypedValue(null);
// Result: null
```

### String Trimming

```php
$ept = new EffectivePrimitiveTypeIdentifierService();

// Trim whitespace automatically
$result = $ept->getTypedValue("  snipershady  ", true);
// Result: "snipershady" (string)

// Preserves internal spaces
$result = $ept->getTypedValue("  hello world  ", true);
// Result: "hello world" (string)
```

### Force String Type

```php
$ept = new EffectivePrimitiveTypeIdentifierService();

$trim = true;
$forceString = true;

// Keep as string even if numeric
$result = $ept->getTypedValue("123", $trim, $forceString);
// Result: "123" (string, not int)

// Useful for IDs, codes, or values that should stay strings
$result = $ept->getTypedValue("007", $trim, $forceString);
// Result: "007" (string)
```

## Working with Arrays

### Safe Array Value Extraction

```php
$ept = new EffectivePrimitiveTypeIdentifierService();

$data = [
    "user_id" => "42",
    "username" => "  snipershady  ",
    "price" => "19.99"
];

// Valid key - returns typed value
$userId = $ept->getTypedValueFromArray("user_id", $data);
// Result: 42 (int)

// Non-existent key - returns null (no warnings/errors)
$missing = $ept->getTypedValueFromArray("invalid_key", $data);
// Result: null

// With trimming enabled
$username = $ept->getTypedValueFromArray("username", $data, true);
// Result: "snipershady" (string, trimmed)

// Float detection
$price = $ept->getTypedValueFromArray("price", $data);
// Result: 19.99 (float)
```

## HTTP Request Sanitization

### POST Data

```php
$ept = new EffectivePrimitiveTypeIdentifierService();

// Assuming $_POST = ["user_id" => "123", "active" => "1"]

// Retrieve and type-cast POST values
$userId = $ept->getTypedValueFromPost("user_id");
// Result: 123 (int)

// Non-existent keys return null
$missing = $ept->getTypedValueFromPost("nonexistent");
// Result: null

// With trimming
$name = $ept->getTypedValueFromPost("username", true);
// Automatically trims whitespace
```

### GET Data

```php
$ept = new EffectivePrimitiveTypeIdentifierService();

// Assuming $_GET = ["page" => "2", "sort" => "name"]

// Retrieve and type-cast GET values
$page = $ept->getTypedValueFromGet("page");
// Result: 2 (int)

$sort = $ept->getTypedValueFromGet("sort");
// Result: "name" (string)

// Missing parameter
$filter = $ept->getTypedValueFromGet("filter");
// Result: null
```

## Real-World Use Cases

### Form Processing

```php
$ept = new EffectivePrimitiveTypeIdentifierService();

// Process form submission with automatic type detection
$age = $ept->getTypedValueFromPost("age"); // int or null
$name = $ept->getTypedValueFromPost("name", true); // trimmed string
$price = $ept->getTypedValueFromPost("price"); // float or null
$agreed = $ept->getTypedValueFromPost("terms"); // bool or null

if ($age !== null && $age >= 18) {
    // Safe integer comparison without manual casting
}
```

### API Parameter Handling

```php
$ept = new EffectivePrimitiveTypeIdentifierService();

// Clean API query parameters
$limit = $ept->getTypedValueFromGet("limit") ?? 10;
$offset = $ept->getTypedValueFromGet("offset") ?? 0;
$search = $ept->getTypedValueFromGet("q", true) ?? "";

// All values are properly typed for database queries
```

### Configuration Arrays

```php
$ept = new EffectivePrimitiveTypeIdentifierService();

$config = [
    "max_attempts" => "3",
    "timeout" => "30.5",
    "enabled" => "true",
    "api_key" => "  abc123xyz  "
];

$maxAttempts = $ept->getTypedValueFromArray("max_attempts", $config); // int(3)
$timeout = $ept->getTypedValueFromArray("timeout", $config); // float(30.5)
$apiKey = $ept->getTypedValueFromArray("api_key", $config, true); // string("abc123xyz")
```

## API Reference

### Main Methods

#### `getTypedValue($value, $trim = false, $forceString = false)`

Identifies and returns the primitive type of a given value.

- **Parameters:**
  - `$value` (mixed): The value to type-check
  - `$trim` (bool): Whether to trim string values (default: false)
  - `$forceString` (bool): Force return as string type (default: false)
- **Returns:** Typed primitive value or null

#### `getTypedValueFromArray($key, array $array, $trim = false, $forceString = false)`

Safely extracts and types a value from an array.

- **Parameters:**
  - `$key` (string): Array key to retrieve
  - `$array` (array): Source array
  - `$trim` (bool): Whether to trim string values (default: false)
  - `$forceString` (bool): Force return as string type (default: false)
- **Returns:** Typed value or null if key doesn't exist

#### `getTypedValueFromPost($key, $trim = false, $forceString = false)`

Retrieves and types a value from `$_POST`.

- **Parameters:**
  - `$key` (string): POST parameter name
  - `$trim` (bool): Whether to trim string values (default: false)
  - `$forceString` (bool): Force return as string type (default: false)
- **Returns:** Typed value or null

#### `getTypedValueFromGet($key, $trim = false, $forceString = false)`

Retrieves and types a value from `$_GET`.

- **Parameters:**
  - `$key` (string): GET parameter name
  - `$trim` (bool): Whether to trim string values (default: false)
  - `$forceString` (bool): Force return as string type (default: false)
- **Returns:** Typed value or null

## Testing

If you have a development environment set up with PHP 8.4, set the host file ‘endpoint-test’ to point to 127.0.0.1 and run

```bash
composer test
```
Otherwise, use the docker found in the project that sets up the environment and runs the test suite, executing  

```bash
docker compose run --rm --remove-orphans --build do-tests && docker compose down
```

## License

This project is released under **GPLv2**. See the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! To contribute:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

Please ensure your code follows PSR-12 coding standards and includes appropriate tests.

## Author

Created and maintained by [snipershady](https://github.com/snipershady)

Best contributor [DamImpr](https://github.com/DamImpr)

## Support

If you find this library helpful, please consider:
- ⭐ Starring the repository
- 🐛 Reporting issues
- 📖 Improving documentation
- 🔧 Contributing code

---

**Made with ❤️ for the PHP community**
