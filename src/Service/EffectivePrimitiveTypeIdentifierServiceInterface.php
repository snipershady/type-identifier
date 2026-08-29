<?php

declare(strict_types=1);

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

use TypeIdentifier\Exception\MaxDepthExceededException;

/**
 * Contract for a service that identifies and returns the effective primitive
 * type of a variable.
 *
 * Implementations must inspect the actual runtime value of a variable and
 * return it cast to the most appropriate PHP primitive type (bool, int, float,
 * string, null, or a recursively typed array).
 *
 * Optional flags control trimming, forced-string mode and HTML sanitization:
 *  - $trim         — trims leading/trailing whitespace from string results.
 *  - $forceString  — keeps numeric-looking strings (e.g. "1") as strings.
 *  - $sanitizeHtml — strips HTML tags and dangerous characters from strings.
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
interface EffectivePrimitiveTypeIdentifierServiceInterface
{
    /**
     * Default maximum nesting level accepted by {@see getTypedValue()}.
     *
     * 64 mirrors PHP's own `max_input_nesting_level` ini default, which is the
     * hard cap PHP applies when it builds $_GET / $_POST / $_COOKIE from a
     * request. Anything the SAPI is willing to hand over is therefore accepted
     * unchanged, while hand-built arrays, decoded JSON (json_decode() allows up
     * to 512 levels) and self-referencing structures stay bounded.
     */
    public const int DEFAULT_MAX_DEPTH = 64;

    /**
     * Returns the effective primitive type of a variable.
     *
     * @param bool $trim         when true, string results are trimmed
     * @param bool $forceString  when true, numeric strings are kept as strings
     * @param bool $sanitizeHtml when true, HTML/XSS sanitization is applied to strings
     * @param int  $maxDepth     Maximum array nesting level to walk. A scalar is depth 0,
     *                           a flat array is depth 1. Guards against unbounded and
     *                           self-referencing structures. Must be >= 0: the value is
     *                           validated at runtime because it routinely comes from
     *                           configuration rather than from a literal.
     *
     * @return array<array-key,mixed>|bool|int|float|string|null
     *
     * @throws \InvalidArgumentException if $maxDepth is negative
     * @throws MaxDepthExceededException if $data nests deeper than $maxDepth
     */
    public function getTypedValue(mixed $data, bool $trim = false, bool $forceString = false, bool $sanitizeHtml = false, int $maxDepth = self::DEFAULT_MAX_DEPTH): array|bool|int|float|string|null;

    /**
     * Casts the result of {@see getTypedValue()} to int.
     *
     * $forceString and $sanitizeHtml are not accepted: they cannot change an
     * integer outcome. null, non-numeric strings and the empty array resolve to
     * 0; a non-empty array resolves to 1; a float is truncated towards zero.
     *
     * @param bool $trim when true, a string value is trimmed before the cast
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getIntValue(mixed $data, bool $trim = false): int;

    /**
     * Casts the result of {@see getTypedValue()} to string.
     *
     * Keeps the full flag set because each one still shapes a string result.
     * An array value (e.g. from "field[]" input) yields an empty string rather
     * than the literal "Array"; null yields an empty string.
     *
     * @param bool $trim         when true, the string result is trimmed
     * @param bool $forceString  when true, numeric strings are kept as-is instead of being re-cast
     * @param bool $sanitizeHtml when true, HTML/XSS sanitization is applied to the string
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getStringValue(mixed $data, bool $trim = false, bool $forceString = false, bool $sanitizeHtml = false): string;

    /**
     * Casts the result of {@see getTypedValue()} to float.
     *
     * $forceString and $sanitizeHtml are not accepted: they cannot change a
     * float outcome. null, non-numeric strings and the empty array resolve to
     * 0.0; a non-empty array resolves to 1.0.
     *
     * @param bool $trim when true, a string value is trimmed before the cast
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getFloatValue(mixed $data, bool $trim = false): float;

    /**
     * Casts the result of {@see getTypedValue()} to bool.
     *
     * $forceString and $sanitizeHtml are not accepted: they cannot change a
     * boolean outcome. PHP truthiness is applied to the typed value, so 0, 0.0,
     * "0", "", null and [] give false while every other value — including the
     * non-empty string "false" — gives true.
     *
     * @param bool $trim when true, a string value is trimmed before the cast
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getBoolValue(mixed $data, bool $trim = false): bool;

    /**
     * Returns the typed value for a specific key from an arbitrary array.
     *
     * @param int|string        $needle       key to look up inside $array
     * @param array<mixed>|null $array        Source array. Returns null if not an array or key absent.
     * @param bool              $trim         passed through to {@see getTypedValue()}
     * @param bool              $forceString  passed through to {@see getTypedValue()}
     * @param bool              $sanitizeHtml passed through to {@see getTypedValue()}
     * @param int               $maxDepth     passed through to {@see getTypedValue()}
     *
     * @return array<array-key,mixed>|bool|int|float|string|null
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than $maxDepth
     */
    public function getTypedValueFromArray(int|string $needle, ?array $array, bool $trim = false, bool $forceString = false, bool $sanitizeHtml = false, int $maxDepth = self::DEFAULT_MAX_DEPTH): array|bool|int|float|string|null;

    /**
     * Returns the int value for a key from an arbitrary array.
     *
     * Convenience wrapper that casts the result of {@see getTypedValueFromArray()} to int.
     * A null $array, an absent key and non-numeric values all resolve to 0.
     *
     * @param int|string        $needle key to look up inside $array
     * @param array<mixed>|null $array  Source array. If null, 0 is returned.
     * @param bool              $trim   passed through to {@see getTypedValue()}
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getIntValueFromArray(int|string $needle, ?array $array, bool $trim = false): int;

    /**
     * Returns the float value for a key from an arbitrary array.
     *
     * Convenience wrapper that casts the result of {@see getTypedValueFromArray()} to float.
     * A null $array, an absent key and non-numeric values all resolve to 0.0.
     *
     * @param int|string        $needle key to look up inside $array
     * @param array<mixed>|null $array  Source array. If null, 0.0 is returned.
     * @param bool              $trim   passed through to {@see getTypedValue()}
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getFloatValueFromArray(int|string $needle, ?array $array, bool $trim = false): float;

    /**
     * Returns the string value for a key from an arbitrary array.
     *
     * Convenience wrapper that casts the result of {@see getTypedValueFromArray()} to string.
     * A null $array and an absent key resolve to an empty string; a nested array
     * value collapses to "" rather than to the literal "Array".
     *
     * @param int|string        $needle       key to look up inside $array
     * @param array<mixed>|null $array        Source array. If null, "" is returned.
     * @param bool              $trim         passed through to {@see getTypedValue()}
     * @param bool              $forceString  passed through to {@see getTypedValue()}
     * @param bool              $sanitizeHtml passed through to {@see getTypedValue()}
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getStringValueFromArray(int|string $needle, ?array $array, bool $trim = false, bool $forceString = false, bool $sanitizeHtml = false): string;

    /**
     * Returns the bool value for a key from an arbitrary array.
     *
     * Convenience wrapper that casts the result of {@see getTypedValueFromArray()} to bool.
     * A null $array and an absent key resolve to false. PHP truthiness is applied,
     * so 0, 0.0, "0", "" and [] give false while the non-empty string "false" gives true.
     *
     * @param int|string        $needle key to look up inside $array
     * @param array<mixed>|null $array  Source array. If null, false is returned.
     * @param bool              $trim   passed through to {@see getTypedValue()}
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getBoolValueFromArray(int|string $needle, ?array $array, bool $trim = false): bool;

    /**
     * Returns the typed value for a key from the $_POST superglobal.
     *
     * @param string $needle       key to look up in $_POST
     * @param bool   $trim         passed through to {@see getTypedValue()}
     * @param bool   $forceString  passed through to {@see getTypedValue()}
     * @param bool   $sanitizeHtml passed through to {@see getTypedValue()}
     * @param int    $maxDepth     passed through to {@see getTypedValue()}
     *
     * @return array<array-key,mixed>|bool|int|float|string|null
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than $maxDepth
     */
    public function getTypedValueFromPost(string $needle, bool $trim = false, bool $forceString = false, bool $sanitizeHtml = false, int $maxDepth = self::DEFAULT_MAX_DEPTH): array|bool|int|float|string|null;

    /**
     * Returns the typed value for a key from the $_GET superglobal.
     *
     * @param string $needle       key to look up in $_GET
     * @param bool   $trim         passed through to {@see getTypedValue()}
     * @param bool   $forceString  passed through to {@see getTypedValue()}
     * @param bool   $sanitizeHtml passed through to {@see getTypedValue()}
     * @param int    $maxDepth     passed through to {@see getTypedValue()}
     *
     * @return array<array-key,mixed>|bool|int|float|string|null
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than $maxDepth
     */
    public function getTypedValueFromGet(string $needle, bool $trim = false, bool $forceString = false, bool $sanitizeHtml = false, int $maxDepth = self::DEFAULT_MAX_DEPTH): array|bool|int|float|string|null;

    /**
     * Returns the typed value for a key from the $_COOKIE superglobal.
     *
     * @param string $needle       key to look up in $_COOKIE
     * @param bool   $trim         passed through to {@see getTypedValue()}
     * @param bool   $forceString  passed through to {@see getTypedValue()}
     * @param bool   $sanitizeHtml passed through to {@see getTypedValue()}
     * @param int    $maxDepth     passed through to {@see getTypedValue()}
     *
     * @return array<array-key,mixed>|bool|int|float|string|null
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than $maxDepth
     */
    public function getTypedValueFromCookie(string $needle, bool $trim = false, bool $forceString = false, bool $sanitizeHtml = false, int $maxDepth = self::DEFAULT_MAX_DEPTH): array|bool|int|float|string|null;

    /**
     * Returns the typed value for a key from the $_SERVER superglobal.
     *
     * @param string $needle       key to look up in $_SERVER
     * @param bool   $trim         passed through to {@see getTypedValue()}
     * @param bool   $forceString  passed through to {@see getTypedValue()}
     * @param bool   $sanitizeHtml passed through to {@see getTypedValue()}
     * @param int    $maxDepth     passed through to {@see getTypedValue()}
     *
     * @return array<array-key,mixed>|bool|int|float|string|null
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than $maxDepth
     */
    public function getTypedValueFromServer(string $needle, bool $trim = false, bool $forceString = false, bool $sanitizeHtml = false, int $maxDepth = self::DEFAULT_MAX_DEPTH): array|bool|int|float|string|null;

    /**
     * Returns the typed value for a key from the $_ENV superglobal.
     *
     * @param string $needle       key to look up in $_ENV
     * @param bool   $trim         passed through to {@see getTypedValue()}
     * @param bool   $forceString  passed through to {@see getTypedValue()}
     * @param bool   $sanitizeHtml passed through to {@see getTypedValue()}
     * @param int    $maxDepth     passed through to {@see getTypedValue()}
     *
     * @return array<array-key,mixed>|bool|int|float|string|null
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than $maxDepth
     */
    public function getTypedValueFromEnv(string $needle, bool $trim = false, bool $forceString = false, bool $sanitizeHtml = false, int $maxDepth = self::DEFAULT_MAX_DEPTH): array|bool|int|float|string|null;

    /**
     * Returns the int value for a key from the $_POST superglobal.
     *
     * Convenience wrapper that casts the result of {@see getTypedValueFromPost()} to int.
     * Missing keys and non-numeric values resolve to 0.
     *
     * @param string $needle key to look up in $_POST
     * @param bool   $trim   passed through to {@see getTypedValue()}
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getIntValueFromPost(string $needle, bool $trim = false): int;

    /**
     * Returns the float value for a key from the $_POST superglobal.
     *
     * Convenience wrapper that casts the result of {@see getTypedValueFromPost()} to float.
     * Missing keys and non-numeric values resolve to 0.0.
     *
     * @param string $needle key to look up in $_POST
     * @param bool   $trim   passed through to {@see getTypedValue()}
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getFloatValueFromPost(string $needle, bool $trim = false): float;

    /**
     * Returns the string value for a key from the $_POST superglobal.
     *
     * Convenience wrapper that casts the result of {@see getTypedValueFromPost()} to string.
     * Missing keys resolve to an empty string.
     *
     * @param string $needle       key to look up in $_POST
     * @param bool   $trim         passed through to {@see getTypedValue()}
     * @param bool   $forceString  passed through to {@see getTypedValue()}
     * @param bool   $sanitizeHtml passed through to {@see getTypedValue()}
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getStringValueFromPost(string $needle, bool $trim = false, bool $forceString = false, bool $sanitizeHtml = false): string;

    /**
     * Returns the bool value for a key from the $_POST superglobal.
     *
     * Convenience wrapper that casts the result of {@see getTypedValueFromPost()} to bool.
     * Missing keys resolve to false.
     *
     * @param string $needle key to look up in $_POST
     * @param bool   $trim   passed through to {@see getTypedValue()}
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getBoolValueFromPost(string $needle, bool $trim = false): bool;

    /**
     * Returns the int value for a key from the $_GET superglobal.
     *
     * Convenience wrapper that casts the result of {@see getTypedValueFromGet()} to int.
     * Missing keys and non-numeric values resolve to 0.
     *
     * @param string $needle key to look up in $_GET
     * @param bool   $trim   passed through to {@see getTypedValue()}
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getIntValueFromGet(string $needle, bool $trim = false): int;

    /**
     * Returns the float value for a key from the $_GET superglobal.
     *
     * Convenience wrapper that casts the result of {@see getTypedValueFromGet()} to float.
     * Missing keys and non-numeric values resolve to 0.0.
     *
     * @param string $needle key to look up in $_GET
     * @param bool   $trim   passed through to {@see getTypedValue()}
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getFloatValueFromGet(string $needle, bool $trim = false): float;

    /**
     * Returns the string value for a key from the $_GET superglobal.
     *
     * Convenience wrapper that casts the result of {@see getTypedValueFromGet()} to string.
     * Missing keys resolve to an empty string.
     *
     * @param string $needle       key to look up in $_GET
     * @param bool   $trim         passed through to {@see getTypedValue()}
     * @param bool   $forceString  passed through to {@see getTypedValue()}
     * @param bool   $sanitizeHtml passed through to {@see getTypedValue()}
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getStringValueFromGet(string $needle, bool $trim = false, bool $forceString = false, bool $sanitizeHtml = false): string;

    /**
     * Returns the bool value for a key from the $_GET superglobal.
     *
     * Convenience wrapper that casts the result of {@see getTypedValueFromGet()} to bool.
     * Missing keys resolve to false.
     *
     * @param string $needle key to look up in $_GET
     * @param bool   $trim   passed through to {@see getTypedValue()}
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getBoolValueFromGet(string $needle, bool $trim = false): bool;

    /**
     * Returns the int value for a key from the $_COOKIE superglobal.
     *
     * Convenience wrapper that casts the result of {@see getTypedValueFromCookie()} to int.
     * Missing keys and non-numeric values resolve to 0.
     *
     * @param string $needle key to look up in $_COOKIE
     * @param bool   $trim   passed through to {@see getTypedValue()}
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getIntValueFromCookie(string $needle, bool $trim = false): int;

    /**
     * Returns the float value for a key from the $_COOKIE superglobal.
     *
     * Convenience wrapper that casts the result of {@see getTypedValueFromCookie()} to float.
     * Missing keys and non-numeric values resolve to 0.0.
     *
     * @param string $needle key to look up in $_COOKIE
     * @param bool   $trim   passed through to {@see getTypedValue()}
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getFloatValueFromCookie(string $needle, bool $trim = false): float;

    /**
     * Returns the string value for a key from the $_COOKIE superglobal.
     *
     * Convenience wrapper that casts the result of {@see getTypedValueFromCookie()} to string.
     * Missing keys resolve to an empty string.
     *
     * @param string $needle       key to look up in $_COOKIE
     * @param bool   $trim         passed through to {@see getTypedValue()}
     * @param bool   $forceString  passed through to {@see getTypedValue()}
     * @param bool   $sanitizeHtml passed through to {@see getTypedValue()}
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getStringValueFromCookie(string $needle, bool $trim = false, bool $forceString = false, bool $sanitizeHtml = false): string;

    /**
     * Returns the bool value for a key from the $_COOKIE superglobal.
     *
     * Convenience wrapper that casts the result of {@see getTypedValueFromCookie()} to bool.
     * Missing keys resolve to false.
     *
     * @param string $needle key to look up in $_COOKIE
     * @param bool   $trim   passed through to {@see getTypedValue()}
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getBoolValueFromCookie(string $needle, bool $trim = false): bool;

    /**
     * Returns the int value for a key from the $_SERVER superglobal.
     *
     * Convenience wrapper that casts the result of {@see getTypedValueFromServer()} to int.
     * Missing keys and non-numeric values resolve to 0.
     *
     * @param string $needle key to look up in $_SERVER
     * @param bool   $trim   passed through to {@see getTypedValue()}
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getIntValueFromServer(string $needle, bool $trim = false): int;

    /**
     * Returns the float value for a key from the $_SERVER superglobal.
     *
     * Convenience wrapper that casts the result of {@see getTypedValueFromServer()} to float.
     * Missing keys and non-numeric values resolve to 0.0.
     *
     * @param string $needle key to look up in $_SERVER
     * @param bool   $trim   passed through to {@see getTypedValue()}
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getFloatValueFromServer(string $needle, bool $trim = false): float;

    /**
     * Returns the string value for a key from the $_SERVER superglobal.
     *
     * Convenience wrapper that casts the result of {@see getTypedValueFromServer()} to string.
     * Missing keys resolve to an empty string.
     *
     * @param string $needle       key to look up in $_SERVER
     * @param bool   $trim         passed through to {@see getTypedValue()}
     * @param bool   $forceString  passed through to {@see getTypedValue()}
     * @param bool   $sanitizeHtml passed through to {@see getTypedValue()}
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getStringValueFromServer(string $needle, bool $trim = false, bool $forceString = false, bool $sanitizeHtml = false): string;

    /**
     * Returns the bool value for a key from the $_SERVER superglobal.
     *
     * Convenience wrapper that casts the result of {@see getTypedValueFromServer()} to bool.
     * Missing keys resolve to false.
     *
     * @param string $needle key to look up in $_SERVER
     * @param bool   $trim   passed through to {@see getTypedValue()}
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getBoolValueFromServer(string $needle, bool $trim = false): bool;

    /**
     * Returns the int value for a key from the $_ENV superglobal.
     *
     * Convenience wrapper that casts the result of {@see getTypedValueFromEnv()} to int.
     * Missing keys and non-numeric values resolve to 0.
     *
     * @param string $needle key to look up in $_ENV
     * @param bool   $trim   passed through to {@see getTypedValue()}
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getIntValueFromEnv(string $needle, bool $trim = false): int;

    /**
     * Returns the float value for a key from the $_ENV superglobal.
     *
     * Convenience wrapper that casts the result of {@see getTypedValueFromEnv()} to float.
     * Missing keys and non-numeric values resolve to 0.0.
     *
     * @param string $needle key to look up in $_ENV
     * @param bool   $trim   passed through to {@see getTypedValue()}
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getFloatValueFromEnv(string $needle, bool $trim = false): float;

    /**
     * Returns the string value for a key from the $_ENV superglobal.
     *
     * Convenience wrapper that casts the result of {@see getTypedValueFromEnv()} to string.
     * Missing keys resolve to an empty string.
     *
     * @param string $needle       key to look up in $_ENV
     * @param bool   $trim         passed through to {@see getTypedValue()}
     * @param bool   $forceString  passed through to {@see getTypedValue()}
     * @param bool   $sanitizeHtml passed through to {@see getTypedValue()}
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getStringValueFromEnv(string $needle, bool $trim = false, bool $forceString = false, bool $sanitizeHtml = false): string;

    /**
     * Returns the bool value for a key from the $_ENV superglobal.
     *
     * Convenience wrapper that casts the result of {@see getTypedValueFromEnv()} to bool.
     * Missing keys resolve to false.
     *
     * @param string $needle key to look up in $_ENV
     * @param bool   $trim   passed through to {@see getTypedValue()}
     *
     * @throws MaxDepthExceededException if the resolved value nests deeper than DEFAULT_MAX_DEPTH
     */
    public function getBoolValueFromEnv(string $needle, bool $trim = false): bool;
}
