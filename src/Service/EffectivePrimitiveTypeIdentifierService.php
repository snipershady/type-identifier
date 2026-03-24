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

/**
 * Service to identify and return the effective primitive type of a variable.
 *
 * Inspects the actual runtime value of a variable and casts it to the most
 * appropriate PHP primitive type: bool, int, float, string, or null.
 * Numeric strings (e.g. "42" → int, "3.14" → float) are automatically
 * promoted to their numeric counterpart unless $forceString is set to true.
 *
 * Additional features:
 *  - Optional whitespace trimming for string values.
 *  - Optional HTML/XSS sanitization that strips tags and removes dangerous
 *    characters (&, <, >, ", %, (, ), +, backtick, low/high bytes).
 *  - Recursive processing of arrays (all values are typed individually).
 *  - Typed reads directly from PHP superglobals ($_POST, $_GET, $_COOKIE,
 *    $_SERVER, $_ENV) via filter_input() with a $_* fallback.
 *  - Typed reads from any associative or indexed array.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class EffectivePrimitiveTypeIdentifierService
{
    /**
     * Returns the effective primitive type of a variable.
     *
     * Resolves the real PHP primitive type of $data and returns the sanitized
     * value cast to that type.  Resolution order:
     *   1. null  → null
     *   2. array → each element is recursively resolved
     *   3. bool  → bool  (skipped when $forceString is true)
     *   4. numeric (int/float string or number) → int|float  (skipped when $forceString is true)
     *   5. string → string (optionally trimmed and/or HTML-sanitized)
     *   6. anything else → null
     *
     * @param mixed $data         Variable to resolve and sanitize.
     * @param bool  $trim         When true, the returned string is trimmed of leading/trailing whitespace.
     *                            Has no effect on non-string values.
     * @param bool  $forceString  When true, numeric-looking strings (e.g. "1", "3.14") are kept as
     *                            strings instead of being promoted to int or float.
     *                            Bool values are also treated as strings when this flag is set.
     * @param bool  $sanitizeHtml When true, the string is stripped of HTML tags and dangerous characters
     *                            (&, <, >, ", %, (, ), +, backtick, low/high bytes).
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
     * cast to its effective primitive type via {@see getTypedValue()}.
     * Returns null when $array is not an array or when the key does not exist.
     *
     * @param string            $needle       Key to look up inside $array.
     * @param array<mixed>|null $array        Source array. If null or not an array, null is returned.
     * @param bool              $trim         Passed through to {@see getTypedValue()}.
     * @param bool              $forceString  Passed through to {@see getTypedValue()}.
     * @param bool              $sanitizeHtml Passed through to {@see getTypedValue()}.
     *
     * @return bool|int|float|string|null The typed value at $needle, or null if the key is absent.
     */
    public function getTypedValueFromArray($needle, $array, $trim = false, $forceString = false, $sanitizeHtml = false)
    {
        return is_array($array) && array_key_exists($needle, $array) ? $this->getTypedValue($array[$needle], $trim, $forceString, $sanitizeHtml) : null;
    }

    /**
     * Returns the typed value for a key from the $_POST superglobal.
     *
     * Reads $needle from INPUT_POST via filter_input() first; falls back to
     * direct $_POST access when filter_input() returns null (e.g. CLI or unit
     * test environments where SAPI input is unavailable).
     *
     * @param string $needle       Key to look up in $_POST.
     * @param bool   $trim         Passed through to {@see getTypedValue()}.
     * @param bool   $forceString  Passed through to {@see getTypedValue()}.
     * @param bool   $sanitizeHtml Passed through to {@see getTypedValue()}.
     *
     * @return bool|int|float|string|null The typed value, or null if the key is absent.
     */
    public function getTypedValueFromPost($needle, $trim = false, $forceString = false, $sanitizeHtml = false)
    {
        $resultSAPI = filter_input(INPUT_POST, $needle, FILTER_UNSAFE_RAW);

        if (null !== $resultSAPI) {
            return $this->getTypedValue($resultSAPI, $trim, $forceString, $sanitizeHtml);
        }

        return array_key_exists($needle, $_POST) ? $this->getTypedValue(filter_var($_POST[$needle]), $trim, $forceString, $sanitizeHtml) : null;
    }

    /**
     * Returns the typed value for a key from the $_SERVER superglobal.
     *
     * Reads $needle from INPUT_SERVER via filter_input() first; falls back to
     * direct $_SERVER access when filter_input() returns null.
     *
     * @param string $needle       Key to look up in $_SERVER.
     * @param bool   $trim         Passed through to {@see getTypedValue()}.
     * @param bool   $forceString  Passed through to {@see getTypedValue()}.
     * @param bool   $sanitizeHtml Passed through to {@see getTypedValue()}.
     *
     * @return bool|int|float|string|null The typed value, or null if the key is absent.
     */
    public function getTypedValueFromServer($needle, $trim = false, $forceString = false, $sanitizeHtml = false)
    {
        $resultSAPI = filter_input(INPUT_SERVER, $needle, FILTER_UNSAFE_RAW);

        if (null !== $resultSAPI) {
            return $this->getTypedValue($resultSAPI, $trim, $forceString, $sanitizeHtml);
        }

        return array_key_exists($needle, $_SERVER) ? $this->getTypedValue(filter_var($_SERVER[$needle]), $trim, $forceString, $sanitizeHtml) : null;
    }

    /**
     * Returns the typed value for a key from the $_GET superglobal.
     *
     * Reads $needle from INPUT_GET via filter_input() first; falls back to
     * direct $_GET access when filter_input() returns null.
     *
     * @param string $needle       Key to look up in $_GET.
     * @param bool   $trim         Passed through to {@see getTypedValue()}.
     * @param bool   $forceString  Passed through to {@see getTypedValue()}.
     * @param bool   $sanitizeHtml Passed through to {@see getTypedValue()}.
     *
     * @return bool|int|float|string|null The typed value, or null if the key is absent.
     */
    public function getTypedValueFromGet($needle, $trim = false, $forceString = false, $sanitizeHtml = false)
    {
        $resultSAPI = filter_input(INPUT_GET, $needle, FILTER_UNSAFE_RAW);

        if (null !== $resultSAPI) {
            return $this->getTypedValue($resultSAPI, $trim, $forceString, $sanitizeHtml);
        }

        return array_key_exists($needle, $_GET) ? $this->getTypedValue(filter_var($_GET[$needle]), $trim, $forceString, $sanitizeHtml) : null;
    }

    /**
     * Returns the typed value for a key from the $_COOKIE superglobal.
     *
     * Reads $needle from INPUT_COOKIE via filter_input() first; falls back to
     * direct $_COOKIE access when filter_input() returns null.
     *
     * @param string $needle       Key to look up in $_COOKIE.
     * @param bool   $trim         Passed through to {@see getTypedValue()}.
     * @param bool   $forceString  Passed through to {@see getTypedValue()}.
     * @param bool   $sanitizeHtml Passed through to {@see getTypedValue()}.
     *
     * @return bool|int|float|string|null The typed value, or null if the key is absent.
     */
    public function getTypedValueFromCookie($needle, $trim = false, $forceString = false, $sanitizeHtml = false)
    {
        $resultSAPI = filter_input(INPUT_COOKIE, $needle, FILTER_UNSAFE_RAW);

        if (null !== $resultSAPI) {
            return $this->getTypedValue($resultSAPI, $trim, $forceString, $sanitizeHtml);
        }

        return array_key_exists($needle, $_COOKIE) ? $this->getTypedValue(filter_var($_COOKIE[$needle]), $trim, $forceString, $sanitizeHtml) : null;
    }

    /**
     * Returns the typed value for a key from the $_ENV superglobal.
     *
     * Reads $needle from INPUT_ENV via filter_input() first; falls back to
     * direct $_ENV access when filter_input() returns null.
     *
     * @param string $needle       Key to look up in $_ENV.
     * @param bool   $trim         Passed through to {@see getTypedValue()}.
     * @param bool   $forceString  Passed through to {@see getTypedValue()}.
     * @param bool   $sanitizeHtml Passed through to {@see getTypedValue()}.
     *
     * @return bool|int|float|string|null The typed value, or null if the key is absent.
     */
    public function getTypedValueFromEnv($needle, $trim = false, $forceString = false, $sanitizeHtml = false)
    {
        $resultSAPI = filter_input(INPUT_ENV, $needle, FILTER_UNSAFE_RAW);

        if (null !== $resultSAPI) {
            return $this->getTypedValue($resultSAPI, $trim, $forceString, $sanitizeHtml);
        }

        return array_key_exists($needle, $_ENV) ? $this->getTypedValue(filter_var($_ENV[$needle]), $trim, $forceString, $sanitizeHtml) : null;
    }

    /**
     * Validates and returns a boolean value.
     *
     * Uses FILTER_VALIDATE_BOOL to ensure the value is a proper PHP bool.
     *
     * @param bool $value Raw boolean value.
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
     * @param mixed $value Must satisfy is_numeric(); behaviour is undefined otherwise.
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
     * @param int $value Integer value to sanitize.
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
     * @param float $value Float value to sanitize.
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
     * When $sanitizeHtml is true the string is passed through {@see sanitizeHtml()}
     * which strips HTML tags and removes dangerous characters.
     * When $sanitizeHtml is false, FILTER_UNSAFE_RAW is
     * applied (passthrough, no conversion).
     * Optionally trims leading/trailing whitespace after sanitization.
     *
     * @param string $value        Raw string value.
     * @param bool   $trim         When true, the result is trimmed.
     * @param bool   $sanitizeHtml When true, HTML/XSS sanitization is applied.
     *
     * @return string
     */
    private function getSanitizedString($value, $trim = false, $sanitizeHtml = false)
    {
        $result = $sanitizeHtml ? $this->sanitizeHtml($value) : filter_var($value, FILTER_UNSAFE_RAW);

        return $trim ? trim($result) : $result;
    }

    /**
     * Strips HTML tags and dangerous characters from a string.
     *
     * Processing pipeline:
     *  1. FILTER_UNSAFE_RAW with FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH |
     *     FILTER_FLAG_STRIP_BACKTICK — removes control bytes, high bytes and backticks.
     *  2. strip_tags() — removes all HTML/XML tags.
     *  3. html_entity_decode() — converts HTML entities to their UTF-8 characters
     *     (e.g. &amp; → &) so that double-encoded payloads are also neutralised.
     *  4. preg_replace() — removes &, <, >, ", %, (, ), + characters.
     *
     * @param string $string Raw string potentially containing HTML or XSS payloads.
     *
     * @return string Sanitized plain-text string.
     */
    private function sanitizeHtml($string)
    {
        $stringFiltered = (string) filter_var(
            $string,
            FILTER_UNSAFE_RAW,
            FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK
        );
        $stringDecoded = html_entity_decode($stringFiltered);
        $stringStripped = strip_tags($stringDecoded);
        
        $pattern = [
            '/\&/',
            '/</',
            '/>/',
            '/"/',
            '/%/',
            '/\(/',
            '/\)/',
            '/\+/',
        ];
        $replacement = '';

        return (string) preg_replace($pattern, $replacement, $stringStripped);
    }
}
