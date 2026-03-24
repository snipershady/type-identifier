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
     * Returns the effective primitive type of a variable.
     *
     * @param mixed $data         variable to resolve and sanitize
     * @param bool  $trim         when true, string results are trimmed
     * @param bool  $forceString  when true, numeric strings are kept as strings
     * @param bool  $sanitizeHtml when true, HTML/XSS sanitization is applied to strings
     *
     * @return array<bool|int|float|string|null,bool|int|float|string|null>|bool|int|float|string|null
     */
    public function getTypedValue($data, $trim = false, $forceString = false, $sanitizeHtml = false);

    /**
     * Returns the typed value for a specific key from an arbitrary array.
     *
     * @param string            $needle       key to look up inside $array
     * @param array<mixed>|null $array        Source array. Returns null if not an array or key absent.
     * @param bool              $trim         passed through to {@see getTypedValue()}
     * @param bool              $forceString  passed through to {@see getTypedValue()}
     * @param bool              $sanitizeHtml passed through to {@see getTypedValue()}
     *
     * @return bool|int|float|string|null
     */
    public function getTypedValueFromArray($needle, $array, $trim = false, $forceString = false, $sanitizeHtml = false);

    /**
     * Returns the typed value for a key from the $_POST superglobal.
     *
     * @param string $needle       key to look up in $_POST
     * @param bool   $trim         passed through to {@see getTypedValue()}
     * @param bool   $forceString  passed through to {@see getTypedValue()}
     * @param bool   $sanitizeHtml passed through to {@see getTypedValue()}
     *
     * @return bool|int|float|string|null
     */
    public function getTypedValueFromPost($needle, $trim = false, $forceString = false, $sanitizeHtml = false);

    /**
     * Returns the typed value for a key from the $_GET superglobal.
     *
     * @param string $needle       key to look up in $_GET
     * @param bool   $trim         passed through to {@see getTypedValue()}
     * @param bool   $forceString  passed through to {@see getTypedValue()}
     * @param bool   $sanitizeHtml passed through to {@see getTypedValue()}
     *
     * @return bool|int|float|string|null
     */
    public function getTypedValueFromGet($needle, $trim = false, $forceString = false, $sanitizeHtml = false);

    /**
     * Returns the typed value for a key from the $_COOKIE superglobal.
     *
     * @param string $needle       key to look up in $_COOKIE
     * @param bool   $trim         passed through to {@see getTypedValue()}
     * @param bool   $forceString  passed through to {@see getTypedValue()}
     * @param bool   $sanitizeHtml passed through to {@see getTypedValue()}
     *
     * @return bool|int|float|string|null
     */
    public function getTypedValueFromCookie($needle, $trim = false, $forceString = false, $sanitizeHtml = false);

    /**
     * Returns the typed value for a key from the $_SERVER superglobal.
     *
     * @param string $needle       key to look up in $_SERVER
     * @param bool   $trim         passed through to {@see getTypedValue()}
     * @param bool   $forceString  passed through to {@see getTypedValue()}
     * @param bool   $sanitizeHtml passed through to {@see getTypedValue()}
     *
     * @return bool|int|float|string|null
     */
    public function getTypedValueFromServer($needle, $trim = false, $forceString = false, $sanitizeHtml = false);

    /**
     * Returns the typed value for a key from the $_ENV superglobal.
     *
     * @param string $needle       key to look up in $_ENV
     * @param bool   $trim         passed through to {@see getTypedValue()}
     * @param bool   $forceString  passed through to {@see getTypedValue()}
     * @param bool   $sanitizeHtml passed through to {@see getTypedValue()}
     *
     * @return bool|int|float|string|null
     */
    public function getTypedValueFromEnv($needle, $trim = false, $forceString = false, $sanitizeHtml = false);
}
