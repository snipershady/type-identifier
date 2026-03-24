<?php

/*
 * Copyright (C) 2025  Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA 02110-1301 USA.
 */

namespace TypeIdentifier\Service;

use TypeIdentifier\Sanitizer\HtmlSanitizerService;
use TypeIdentifier\Sanitizer\HtmlSanitizerServiceInterface;

/**
 * Service to identify and return the effective primitive type of a variable.
 *
 * Inspects the actual runtime value of a variable and casts it to the most
 * appropriate PHP primitive type: bool, int, float, string, or null.
 * Numeric strings (e.g. "42" -> int, "3.14" -> float) are automatically
 * promoted to their numeric counterpart unless $forceString is set to true.
 *
 * Additional features:
 *  - Optional whitespace trimming for string values.
 *  - Optional HTML/XSS sanitization delegated to a HtmlSanitizerServiceInterface
 *    instance (defaults to HtmlSanitizerService when none is injected).
 *  - Recursive processing of arrays (all values are typed individually).
 *  - Typed reads directly from PHP superglobals ($_POST, $_GET, $_COOKIE,
 *    $_SERVER, $_ENV) via filter_input() with a $_* fallback.
 *  - Typed reads from any associative or indexed array.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class EffectivePrimitiveTypeIdentifierService implements EffectivePrimitiveTypeIdentifierServiceInterface
{
    /**
     * HTML/XSS sanitizer used when $sanitizeHtml is true.
     *
     * @var HtmlSanitizerServiceInterface
     */
    private $htmlSanitizer;

    /**
     * @param HtmlSanitizerServiceInterface|null $htmlSanitizer Custom sanitizer to use.
     *                                                          When null, HtmlSanitizerService is used.
     */
    public function __construct(?HtmlSanitizerServiceInterface $htmlSanitizer = null)
    {
        $this->htmlSanitizer = null !== $htmlSanitizer ? $htmlSanitizer : new HtmlSanitizerService();
    }

    /**
     * Returns the effective primitive type of a variable.
     *
     * Resolves the real PHP primitive type of $data and returns the sanitized
     * value cast to that type. Resolution order:
     *   1. null  -> null
     *   2. array -> each element is recursively resolved
     *   3. bool  -> bool  (skipped when $forceString is true)
     *   4. numeric (int/float string or number) -> int|float  (skipped when $forceString is true)
     *   5. string -> string (optionally trimmed and/or HTML-sanitized)
     *   6. anything else -> null
     *
     * @param mixed $data         variable to resolve and sanitize
     * @param bool  $trim         When true, the returned string is trimmed of leading/trailing whitespace.
     *                            Has no effect on non-string values.
     * @param bool  $forceString  When true, numeric-looking strings (e.g. "1", "3.14") are kept as
     *                            strings instead of being promoted to int or float.
     *                            Bool values are also treated as strings when this flag is set.
     * @param bool  $sanitizeHtml When true, the string is sanitized via the injected
     *                            HtmlSanitizerServiceInterface.
     *                            Has no effect on non-string values.
     *
     * @return array<bool|int|float|string|null,bool|int|float|string|null>|bool|int|float|string|null
     */
    public function getTypedValue($data, $trim = false, $forceString = false, $sanitizeHtml = false)
    {
        if (null === $data) {
            return null;
        }

        if (is_array($data)) {
            $res = [];
            foreach ($data as $key => $value) {
                $res[$key] = $this->getTypedValue($value, $trim, $forceString, $sanitizeHtml);
            }

            return $res;
        }

        if (!$forceString && is_bool($data)) {
            return $this->getSanitizedBool($data);
        }
        if (!$forceString && is_numeric($data)) {
            return $this->getSanitizedNumber($data);
        }
        if ($forceString || is_string($data)) {
            return $this->getSanitizedString((string) $data, $trim, $sanitizeHtml);
        }

        return null;
    }

    /**
     * Returns the typed value for a specific key from an arbitrary array.
     *
     * Looks up $needle in $array and, if found, returns the sanitized value
     * cast to its effective primitive type via getTypedValue().
     * Returns null when $array is not an array or when the key does not exist.
     *
     * @param string            $needle       key to look up inside $array
     * @param array<mixed>|null $array        Source array. If null or not an array, null is returned.
     * @param bool              $trim         passed through to getTypedValue()
     * @param bool              $forceString  passed through to getTypedValue()
     * @param bool              $sanitizeHtml passed through to getTypedValue()
     *
     * @return array<bool|int|float|string|null,bool|int|float|string|null>|bool|int|float|string|null the typed value at $needle, or null if the key is absent
     */
    public function getTypedValueFromArray($needle, $array, $trim = false, $forceString = false, $sanitizeHtml = false)
    {
        return is_array($array) && array_key_exists($needle, $array) ? $this->getTypedValue($array[$needle], $trim, $forceString, $sanitizeHtml) : null;
    }

    /**
     * Returns the typed value for a key from the $_POST superglobal.
     *
     * @param string $needle       Key to look up in $_POST.
     * @param bool   $trim         Passed through to getTypedValue().
     * @param bool   $forceString  Passed through to getTypedValue().
     * @param bool   $sanitizeHtml Passed through to getTypedValue().
     *
     * @return bool|int|float|string|null The typed value, or null if the key is absent.
     */
    public function getTypedValueFromPost($needle, $trim = false, $forceString = false, $sanitizeHtml = false)
    {
        return $this->readFromInput(INPUT_POST, $_POST, $needle, $trim, $forceString, $sanitizeHtml);
    }

    /**
     * Returns the typed value for a key from the $_SERVER superglobal.
     *
     * @param string $needle       Key to look up in $_SERVER.
     * @param bool   $trim         Passed through to getTypedValue().
     * @param bool   $forceString  Passed through to getTypedValue().
     * @param bool   $sanitizeHtml Passed through to getTypedValue().
     *
     * @return bool|int|float|string|null The typed value, or null if the key is absent.
     */
    public function getTypedValueFromServer($needle, $trim = false, $forceString = false, $sanitizeHtml = false)
    {
        return $this->readFromInput(INPUT_SERVER, $_SERVER, $needle, $trim, $forceString, $sanitizeHtml);
    }

    /**
     * Returns the typed value for a key from the $_GET superglobal.
     *
     * @param string $needle       Key to look up in $_GET.
     * @param bool   $trim         Passed through to getTypedValue().
     * @param bool   $forceString  Passed through to getTypedValue().
     * @param bool   $sanitizeHtml Passed through to getTypedValue().
     *
     * @return bool|int|float|string|null The typed value, or null if the key is absent.
     */
    public function getTypedValueFromGet($needle, $trim = false, $forceString = false, $sanitizeHtml = false)
    {
        return $this->readFromInput(INPUT_GET, $_GET, $needle, $trim, $forceString, $sanitizeHtml);
    }

    /**
     * Returns the typed value for a key from the $_COOKIE superglobal.
     *
     * @param string $needle       Key to look up in $_COOKIE.
     * @param bool   $trim         Passed through to getTypedValue().
     * @param bool   $forceString  Passed through to getTypedValue().
     * @param bool   $sanitizeHtml Passed through to getTypedValue().
     *
     * @return bool|int|float|string|null The typed value, or null if the key is absent.
     */
    public function getTypedValueFromCookie($needle, $trim = false, $forceString = false, $sanitizeHtml = false)
    {
        return $this->readFromInput(INPUT_COOKIE, $_COOKIE, $needle, $trim, $forceString, $sanitizeHtml);
    }

    /**
     * Returns the typed value for a key from the $_ENV superglobal.
     *
     * @param string $needle       Key to look up in $_ENV.
     * @param bool   $trim         Passed through to getTypedValue().
     * @param bool   $forceString  Passed through to getTypedValue().
     * @param bool   $sanitizeHtml Passed through to getTypedValue().
     *
     * @return bool|int|float|string|null The typed value, or null if the key is absent.
     */
    public function getTypedValueFromEnv($needle, $trim = false, $forceString = false, $sanitizeHtml = false)
    {
        return $this->readFromInput(INPUT_ENV, $_ENV, $needle, $trim, $forceString, $sanitizeHtml);
    }

    /**
     * Shared superglobal reader used by all getTypedValueFrom*() public methods.
     *
     * Attempts to read $needle from the SAPI input stream via filter_input()
     * first, which is the correct approach in web contexts. Falls back to direct
     * superglobal array access when filter_input() returns null (e.g. in CLI,
     * unit tests, or custom SAPI environments where the input stream is absent).
     *
     * @param int        $inputType  One of the INPUT_* constants (INPUT_POST, INPUT_GET, etc.).
     * @param array<mixed> $superglobal Reference to the corresponding $_* superglobal array.
     * @param string     $needle     Key to look up.
     * @param bool       $trim       Passed through to getTypedValue().
     * @param bool       $forceString Passed through to getTypedValue().
     * @param bool       $sanitizeHtml Passed through to getTypedValue().
     *
     * @return bool|int|float|string|null The typed value, or null if the key is absent.
     */
    private function readFromInput($inputType, array &$superglobal, $needle, $trim, $forceString, $sanitizeHtml)
    {
        $resultSAPI = filter_input($inputType, $needle, FILTER_UNSAFE_RAW);

        if (null !== $resultSAPI) {
            return $this->getTypedValue($resultSAPI, $trim, $forceString, $sanitizeHtml);
        }

        return array_key_exists($needle, $superglobal)
            ? $this->getTypedValue(filter_var($superglobal[$needle], FILTER_UNSAFE_RAW), $trim, $forceString, $sanitizeHtml)
            : null;
    }

    /**
     * Validates and returns a boolean value.
     *
     * Uses FILTER_VALIDATE_BOOL to ensure the value is a proper PHP bool.
     *
     * @param bool $value raw boolean value
     *
     * @return bool
     */
    private function getSanitizedBool($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    /**
     * Resolves a numeric value to either int or float.
     *
     * Adds 0 to promote a numeric string to its native numeric type, then
     * delegates to the appropriate int or float sanitizer.
     *
     * @param mixed $value must satisfy is_numeric(); behaviour is undefined otherwise
     *
     * @return int|float
     */
    private function getSanitizedNumber($value)
    {
        $numericValue = $value + 0;
        if (is_int($numericValue)) {
            return $this->getSanitizedIntValue($numericValue);
        }

        return $this->getSanitizedFloatValue($numericValue);
    }

    /**
     * Sanitizes and returns an integer value.
     *
     * Applies FILTER_SANITIZE_NUMBER_INT to strip any unexpected characters
     * before casting to int.
     *
     * @param int $value integer value to sanitize
     *
     * @return int
     */
    private function getSanitizedIntValue($value)
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitizes and returns a float value.
     *
     * Applies FILTER_SANITIZE_NUMBER_FLOAT with FILTER_FLAG_ALLOW_FRACTION to
     * preserve the decimal part while stripping unexpected characters.
     *
     * @param float $value float value to sanitize
     *
     * @return float
     */
    private function getSanitizedFloatValue($value)
    {
        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
     * Sanitizes and returns a string value.
     *
     * When $sanitizeHtml is true the string is passed through the injected
     * HtmlSanitizerServiceInterface implementation.
     * When $sanitizeHtml is false, FILTER_UNSAFE_RAW is applied (passthrough).
     * Optionally trims leading/trailing whitespace after sanitization.
     *
     * @param string $value        raw string value
     * @param bool   $trim         when true, the result is trimmed
     * @param bool   $sanitizeHtml when true, HTML/XSS sanitization is applied
     *
     * @return string
     */
    private function getSanitizedString($value, $trim = false, $sanitizeHtml = false)
    {
        $result = $sanitizeHtml ? $this->htmlSanitizer->sanitize($value) : filter_var($value, FILTER_UNSAFE_RAW);

        return $trim ? trim($result) : $result;
    }
}
