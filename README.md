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

- ✅ **Smart type detection**: Automatically identifies `int`, `float`, `bool`, `string`, `null` and typed arrays
- ✅ **Typed scalar accessors**: `getIntValue()`, `getFloatValue()`, `getBoolValue()`, `getStringValue()` return a guaranteed scalar — never `null`, never an array
- ✅ **Whitespace handling**: Optional trimming for clean string values
- ✅ **Type forcing**: Keep numeric-looking values as strings when needed (IDs, ZIP codes, ...)
- ✅ **HTML/XSS sanitization**: Optional tag/entity stripping for string results, delegated to an injectable sanitizer
- ✅ **Array-safe extraction**: Safely retrieve typed values from arrays without `isset()` checks, with the same `getInt/Float/Bool/StringValueFromArray()` accessors offered for the superglobals
- ✅ **Bounded recursion**: Nested arrays are walked up to `$maxDepth` (default 64); unbounded and self-referencing structures raise a catchable exception instead of killing the process
- ✅ **Superglobal helpers**: Built-in typed reads for `$_GET`, `$_POST`, `$_COOKIE`, `$_SERVER`, `$_ENV` (via `filter_input()` with a direct `$_*` fallback)
- ✅ **Strict & modern**: `declare(strict_types=1)`, `final readonly` service, full native type declarations, analysed at PHPStan `max`
- ✅ **Zero runtime dependencies**: Lightweight and focused

## Installation

```bash
composer require snipershady/typeidentifier
```

## Requirements

- **PHP 8.3 or higher** (tested up to PHP 8.4)
- No runtime dependencies

## Version 2.0

Version 2.0 is a modernization release and **drops support for legacy PHP**. Projects on PHP 5.6–8.2 should stay on a `1.x` release:

```bash
composer require snipershady/typeidentifier:^1.0
```

### What changed in 2.0

- **Minimum PHP raised to 8.3.** The library now relies on `readonly` classes, named arguments and the `#[\Override]` attribute.
- **Strict typing everywhere.** `declare(strict_types=1)` in every file, native parameter and return types on every method, `mixed` only where a value genuinely can be anything.
- **New typed scalar accessors** — `getIntValue()`, `getFloatValue()`, `getBoolValue()`, `getStringValue()` — that always return the requested scalar type.
- **New typed superglobal accessors** — `getIntValueFromPost()`, `getFloatValueFromEnv()`, `getStringValueFromServer()`, `getBoolValueFromCookie()`, … (`{Int,Float,String,Bool}Value` × `From{Post,Get,Cookie,Server,Env}`).
- **Three more superglobal sources:** `$_COOKIE`, `$_SERVER` and `$_ENV` join `$_POST` and `$_GET`, each read through `filter_input()` with a direct-array fallback for CLI / test contexts.
- **Optional HTML/XSS sanitization** through the `$sanitizeHtml` flag, delegated to an injectable `HtmlSanitizerServiceInterface`.
- **Quality gate:** PHPStan `max` + strict-rules, Rector, PHP-CS-Fixer (`@Symfony` + risky) and PHPUnit all run clean (`composer quality-check`).

The `getTypedValue*()` methods keep the same behaviour as 1.x, with one added trailing argument (`$sanitizeHtml`), so existing calls are source-compatible on PHP 8.3+.

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

### Typed Scalar Accessors

When you already know which scalar you expect, use the dedicated accessors instead of `getTypedValue()`. They never return `null` or an array — you always get the requested type back, with a safe zero-value default.

```php
$ept = new EffectivePrimitiveTypeIdentifierService();

// getIntValue(): mixed $data, bool $trim = false
$ept->getIntValue("42");             // int(42)
$ept->getIntValue("3.99");           // int(3)   — a float is truncated towards zero
$ept->getIntValue("not a number");   // int(0)
$ept->getIntValue(null);             // int(0)

// getFloatValue(): mixed $data, bool $trim = false
$ept->getFloatValue("3.14");         // float(3.14)
$ept->getFloatValue("42");           // float(42.0)
$ept->getFloatValue(null);           // float(0.0)

// getBoolValue(): mixed $data, bool $trim = false
$ept->getBoolValue("1");             // bool(true)
$ept->getBoolValue("0");             // bool(false)
$ept->getBoolValue("");              // bool(false)
$ept->getBoolValue("false");         // bool(true)  — a non-empty string is truthy

// getStringValue(): mixed $data, bool $trim = false, bool $forceString = false, bool $sanitizeHtml = false
$ept->getStringValue(42);            // string("42")
$ept->getStringValue(null);          // string("")
$ept->getStringValue(["a", "b"]);    // string("")  — arrays collapse to ""
$ept->getStringValue("  hi  ", trim: true); // string("hi")
```

`getIntValue()`, `getFloatValue()` and `getBoolValue()` accept only `($data, $trim)`: `$forceString` and `$sanitizeHtml` cannot change a numeric or boolean result. `getStringValue()` keeps the full flag set (`$data, $trim, $forceString, $sanitizeHtml`).

### HTML / XSS Sanitization

Pass `sanitizeHtml: true` (the 4th argument of `getTypedValue()`, `getStringValue()` and every `getTypedValueFrom*()` method) to strip tags, decode entities and drop dangerous characters from string results:

```php
$ept = new EffectivePrimitiveTypeIdentifierService();

$ept->getStringValue('<b>Hello</b> <script>alert(1)</script>', sanitizeHtml: true);
// string("Hello alert1")

$bio = $ept->getTypedValueFromPost("bio", trim: true, forceString: true, sanitizeHtml: true);
```

Sanitization is delegated to an `HtmlSanitizerServiceInterface`. Inject your own implementation through the constructor to customise it:

```php
$ept = new EffectivePrimitiveTypeIdentifierService(new MyHtmlSanitizer());
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

### Cookie, Server and Env Data

The same `getTypedValueFrom*()` contract is available for the three remaining input superglobals:

```php
$ept = new EffectivePrimitiveTypeIdentifierService();

$session = $ept->getTypedValueFromCookie("PHPSESSID");     // string|null
$agent   = $ept->getTypedValueFromServer("HTTP_USER_AGENT"); // string|null
$debug   = $ept->getTypedValueFromEnv("APP_DEBUG");          // int|bool|string|null
```

Each read goes through `filter_input()` first — the correct approach in a web SAPI — and falls back to reading the `$_*` array directly when `filter_input()` returns `null` (for example under CLI or PHPUnit). Array values (`field[]`) are read straight from the superglobal and typed recursively.

### Typed Superglobal Accessors

Every source also exposes the four typed accessors, so you get a guaranteed scalar and a safe default when the key is missing — no null checks, no manual casting:

```php
$ept = new EffectivePrimitiveTypeIdentifierService();

$page    = $ept->getIntValueFromGet("page");          // int(0) when ?page is absent
$ratio   = $ept->getFloatValueFromPost("ratio");      // float(0.0) when absent
$name    = $ept->getStringValueFromPost("name", trim: true); // "" when absent
$consent = $ept->getBoolValueFromCookie("consent");   // bool(false) when absent
$workers = $ept->getIntValueFromEnv("WORKER_COUNT");  // int
$length  = $ept->getIntValueFromServer("CONTENT_LENGTH"); // int(0) when absent
```

Full matrix — `{Int,Float,String,Bool}Value` × `From{Post,Get,Cookie,Server,Env}` (20 methods):
`getIntValueFromPost()`, `getFloatValueFromEnv()`, `getStringValueFromServer()`, `getBoolValueFromCookie()`, … The `Int`/`Float`/`Bool` variants take `($needle, $trim)`; the `String` variant also takes `$forceString` and `$sanitizeHtml`.

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

Every method is declared on `EffectivePrimitiveTypeIdentifierServiceInterface`.

### Common parameters

| Parameter | Type | Default | Meaning |
| --- | --- | --- | --- |
| `$trim` | `bool` | `false` | Trim leading/trailing whitespace from a string result |
| `$forceString` | `bool` | `false` | Keep numeric-looking strings (`"1"`, `"3.14"`) as strings instead of promoting them to `int`/`float` |
| `$sanitizeHtml` | `bool` | `false` | Strip tags, decode entities and remove dangerous characters from a string result |
| `$maxDepth` | `int` | `64` | Maximum array nesting level to walk. A scalar is depth 0, a flat array is depth 1. Exposed by the methods that can *return* a nested array; the scalar accessors apply the default |

### Core

#### `getTypedValue(mixed $data, bool $trim = false, bool $forceString = false, bool $sanitizeHtml = false, int $maxDepth = self::DEFAULT_MAX_DEPTH): array|bool|int|float|string|null`

Resolves the effective primitive type of `$data`. Arrays are processed recursively (each element is typed individually), up to `$maxDepth` nesting levels. Anything that is neither scalar nor array nor `null` returns `null`.

Throws:

| Exception | When |
| --- | --- |
| `TypeIdentifier\Exception\MaxDepthExceededException` (`\RuntimeException`) | `$data` nests deeper than `$maxDepth` |
| `\InvalidArgumentException` | `$maxDepth` is negative |

`MaxDepthExceededException` implements `TypeIdentifier\Exception\TypeIdentifierExceptionInterface`, the marker interface for everything this library throws in response to *data*. Catch it to reject a hostile payload:

```php
use TypeIdentifier\Exception\TypeIdentifierExceptionInterface;

try {
    $payload = $ept->getTypedValue(json_decode($body, true));
} catch (TypeIdentifierExceptionInterface $e) {
    http_response_code(400);
    exit('Payload rejected: ' . $e->getMessage());
}
```

The negative-`$maxDepth` `\InvalidArgumentException` deliberately stays outside that interface: it signals a bug in *your* code, not bad input, and should not be swallowed by the same catch block.

#### Which methods expose `$maxDepth`

Only the seven whose return type includes `array`, i.e. the ones that can hand a nested structure back to you:

`getTypedValue()`, `getTypedValueFromArray()`, `getTypedValueFromPost()`, `getTypedValueFromGet()`, `getTypedValueFromCookie()`, `getTypedValueFromServer()`, `getTypedValueFromEnv()`.

The typed scalar accessors (`getIntValue()`, `getBoolValueFromPost()`, …) do **not** take it, for the same reason they do not take `$forceString` or `$sanitizeHtml`: they collapse an array to `0`/`1`, `0.0`/`1.0`, `false`/`true` or `""`, so the nesting they walk never reaches the caller and there is nothing to tune. They remain protected by the `DEFAULT_MAX_DEPTH` ceiling.

The default of **64** mirrors PHP's own `max_input_nesting_level` ini setting, the hard cap PHP applies when it builds `$_GET` / `$_POST` / `$_COOKIE` from a request — so the guard never rejects a payload the SAPI was willing to hand you, while still bounding `json_decode()` output (which allows up to 512 levels) and hand-built structures. Raise it explicitly if you knowingly process deeper trusted data:

```php
$ept->getTypedValue($deepTrustedTree, maxDepth: 256);
```

### Typed scalar accessors

Convenience wrappers over `getTypedValue()` that guarantee a single scalar type.

| Method | Returns | Missing / non-matching input |
| --- | --- | --- |
| `getIntValue(mixed $data, bool $trim = false): int` | `int` | `0` (`1` for a non-empty array, float truncated) |
| `getFloatValue(mixed $data, bool $trim = false): float` | `float` | `0.0` (`1.0` for a non-empty array) |
| `getBoolValue(mixed $data, bool $trim = false): bool` | `bool` | `false` (PHP truthiness; `"false"` is truthy) |
| `getStringValue(mixed $data, bool $trim = false, bool $forceString = false, bool $sanitizeHtml = false): string` | `string` | `""` (arrays collapse to `""`) |

### Array source

#### `getTypedValueFromArray(int\|string $needle, ?array $array, bool $trim = false, bool $forceString = false, bool $sanitizeHtml = false, int $maxDepth = self::DEFAULT_MAX_DEPTH): array|bool|int|float|string|null`

Returns `getTypedValue()` for `$array[$needle]`, or `null` when `$array` is `null` or the key is absent. `$maxDepth` is forwarded, so a nested value can be bounded per call:

```php
$tree = $ept->getTypedValueFromArray('tree', $config, maxDepth: 8);
```

#### Typed array accessors

The counterparts of the superglobal accessors for an arbitrary array source. They carry the `($needle, $array)` pair instead of a lone `$needle` and never return `null`: a `null` `$array`, an absent key and a `null` value all resolve to the zero value of the target type.

| Method | Returns | Missing key / `null` array |
| --- | --- | --- |
| `getIntValueFromArray(int\|string $needle, ?array $array, bool $trim = false): int` | `int` | `0` |
| `getFloatValueFromArray(int\|string $needle, ?array $array, bool $trim = false): float` | `float` | `0.0` |
| `getBoolValueFromArray(int\|string $needle, ?array $array, bool $trim = false): bool` | `bool` | `false` |
| `getStringValueFromArray(int\|string $needle, ?array $array, bool $trim = false, bool $forceString = false, bool $sanitizeHtml = false): string` | `string` | `""` (nested arrays collapse to `""`) |

```php
$config = ['port' => '8080', 'ratio' => '1.5', 'name' => '  api  ', 'debug' => '0'];

$port  = $ept->getIntValueFromArray('port', $config);              // int(8080)
$ratio = $ept->getFloatValueFromArray('ratio', $config);           // float(1.5)
$name  = $ept->getStringValueFromArray('name', $config, trim: true); // string("api")
$debug = $ept->getBoolValueFromArray('debug', $config);            // bool(false)
$miss  = $ept->getIntValueFromArray('nope', $config);              // int(0)
```

### Superglobal sources

For each of `Post`, `Get`, `Cookie`, `Server`, `Env`:

#### `getTypedValueFrom{Source}(string $needle, bool $trim = false, bool $forceString = false, bool $sanitizeHtml = false, int $maxDepth = self::DEFAULT_MAX_DEPTH): array|bool|int|float|string|null`

Reads `$needle` from the matching superglobal via `filter_input()`, falling back to the `$_*` array (CLI / tests). Returns `null` when the key is absent. `$maxDepth` is forwarded, which matters for `field[]`-style inputs that arrive as arrays:

```php
$tags = $ept->getTypedValueFromPost('tags', maxDepth: 2);
```

#### Typed superglobal accessors

| Method | Returns | Missing key |
| --- | --- | --- |
| `getIntValueFrom{Source}(string $needle, bool $trim = false): int` | `int` | `0` |
| `getFloatValueFrom{Source}(string $needle, bool $trim = false): float` | `float` | `0.0` |
| `getBoolValueFrom{Source}(string $needle, bool $trim = false): bool` | `bool` | `false` |
| `getStringValueFrom{Source}(string $needle, bool $trim = false, bool $forceString = false, bool $sanitizeHtml = false): string` | `string` | `""` |

Example concrete names: `getTypedValueFromServer()`, `getIntValueFromPost()`, `getFloatValueFromEnv()`, `getStringValueFromServer()`, `getBoolValueFromCookie()`.

## Testing

The suite is split in two, because half of `readFromInput()` cannot be reached from CLI.

```bash
composer test              # everything; the integration tests skip if no endpoint answers
composer test:unit         # unit only, no I/O, runnable anywhere
composer test:integration  # requires a live endpoint (see below)
```

### Why an integration suite exists

`getTypedValueFrom{Get,Post,Server,…}()` reads the SAPI input stream with `filter_input()` and only falls back to the `$_*` array when that returns `null`. Under CLI `filter_input()` **always** returns `null`, so a pure unit suite exercises the fallback and never the branch that actually runs in production. The integration tests drive `tests/entrypoint.php` over real HTTP to cover it.

Covered over a real request: `$_GET` and `$_POST` (query string / post fields), `$_SERVER` (fed by `X-Test-*` headers) and `$_COOKIE` (fed through `CURLOPT_COOKIE`) — each with its untyped `getTypedValueFrom*()` read and all four typed accessors.

They go one step further: with the `X-Test-Sapi-Only` header the endpoint empties the superglobal entries before reading, which disarms the fallback — a value that still comes back can only have been read by `filter_input()`. That turns "the SAPI branch is covered" from an assumption into an assertion.

> Note: the branch executes in the web-server process, which the CLI coverage driver cannot observe, so it does not show up in the `--coverage-text` figures. The `X-Test-Sapi-Only` tests are what actually pin it down.

### Running the integration suite

Against PHP's built-in server, no docker needed:

```bash
php -S 127.0.0.1:8080 -t . &
TYPEIDENTIFIER_ENDPOINT=http://127.0.0.1:8080/tests/entrypoint.php composer test:integration
```

Or with the bundled docker-compose stack, which serves the endpoint on the `endpoint-test` host (the default when `TYPEIDENTIFIER_ENDPOINT` is unset):

```bash
docker compose run --rm --remove-orphans --build do-tests && docker compose down
```

When nothing answers, the integration tests skip themselves so `composer test` stays green with no server around. CI runs them with `--fail-on-skipped`, so a missing endpoint turns the build red instead of quietly leaving the SAPI paths untested.

### What CI runs

| Job | PHP | Steps |
| --- | --- | --- |
| `quality` | 8.3 | `composer validate --strict`, PHPStan `max`, PHP-CS-Fixer, Rector |
| `test` | 8.3, 8.4 | unit suite, then integration suite against `php -S` with `--fail-on-skipped`, then coverage |

## License

This project is released under **GPLv2**. See the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! To contribute:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

Before opening a PR, make sure the quality gate is green:

```bash
composer quality        # apply Rector + PHP-CS-Fixer fixes
composer quality-check   # Rector (dry-run) + PHP-CS-Fixer (dry-run) + PHPStan (max)
composer test            # PHPUnit
```

New behaviour must come with tests, and PHPStan `max` + strict-rules must stay clean.

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
