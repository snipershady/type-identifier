<?php

/*
 * Copyright (C) 2022 Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace TypeIdentifier\Service;

/**
 * Description of EffectivePrimitiveTypeIdentifierService
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class EffectivePrimitiveTypeIdentifierService
{
    /**
     * <p>Returns strict effective primitive type of a variable</p>
     * @param mixed $data <p>Variable to sanitize and get again with right strict primitive type</p>
     * @param bool $trim <p>Trim value if type is an String</p>
     * @param bool $forceString  <p>Force string parsing for values like "1", so it will be handlet as String and not as integer</p>
     * @param bool $sanitizeHtml <p>When true, the string will be sanitized from HTML tags</p>
     * @return bool|int|float|string|null
     */
    public function getTypedValue($data, $trim = false, $forceString = false, $sanitizeHtml = false)
    {
        if ($data === null) {
            return null;
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
     * <p>Returns value from a needle of an array, sanitized and with effective primitive strict type</p>
     * @param string $needle <p>Value to check.</p>
     * @param array<mixed> $array <p>An array with keys to check.</p>
     * @param bool $trim <p>Trim value if type is an String</p>
     * @param bool $forceString  <p>Force string parsing for values like "1"</p>
     * @return bool|int|float|string|null <p>Returns primitive type from the needle. NULL if key does not exists</p>
     */
    public function getTypedValueFromArray($needle, array $array, $trim = false, $forceString = false, $sanitizeHtml = false)
    {
        return is_array($array) && array_key_exists($needle, $array) ? $this->getTypedValue($array[$needle], $trim, $forceString, $sanitizeHtml) : null;
    }

    /**
     * <p>Returns value from a needle POST, sanitized and with effective primitive strict type</p>
     * @param string $needle <p>Value to check.</p>
     * @param bool $trim <p>Trim value if type is an String</p>
     * @param bool $forceString  <p>Force string parsing for values like "1"</p>
     * @return bool|int|float|string|null <p>Returns primitive type from the needle. NULL if key does not exists</p>
     */
    public function getTypedValueFromPost($needle, $trim = false, $forceString = false, $sanitizeHtml = false)
    {
        $inputPost = filter_input(INPUT_POST, $needle, FILTER_NULL_ON_FAILURE);
        return $this->getTypedValue($inputPost, $trim, $forceString, $sanitizeHtml);
    }

    /**
     * <p>Returns value from a needle GET, sanitized and with effective primitive strict type</p>
     * @param string $needle <p>Value to check.</p>
     * @param bool $trim <p>Trim value if type is an String</p>
     * @param bool $forceString  <p>Force string parsing for values like "1"</p>
     * @return bool|int|float|string|null <p>Returns primitive type from the needle. NULL if key does not exists</p>
     */
    public function getTypedValueFromGet($needle, $trim = false, $forceString = false, $sanitizeHtml = false)
    {
        $inputGet = filter_input(INPUT_GET, $needle, FILTER_NULL_ON_FAILURE);
        return $this->getTypedValue($inputGet, $trim, $forceString, $sanitizeHtml);
    }

    /**
     * Return sanitized bool
     * @param bool $value
     * @return bool
     */
    private function getSanitizedBool($value)
    {

        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    /**
     * Return sanitized number
     * @param mixed $value
     *        must be "numeric"
     * @return int|float
     */
    private function getSanitizedNumber($value)
    {
        $numericvalue = $value + 0;
        if (is_int($numericvalue)) {
            return $this->getSanitizedIntValue($numericvalue);
        } else {
            return $this->getSanitizedFloatValue($numericvalue);
        }
    }

    /**
     * Return sanitized string
     * @param int $value
     * @return int
     */
    private function getSanitizedIntValue($value)
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Return sanitized string
     * @param float $value
     * @return float
     */
    private function getSanitizedFloatValue($value)
    {
        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
     * Return sanitized string
     * @param string $value
     * @param bool $trim
     * @param bool $sanitizeHtml
     * @return string
     */
    private function getSanitizedString($value, $trim = false, $sanitizeHtml = false)
    {
        $result = $sanitizeHtml ? $this->sanitizeHtml($value) : filter_var($value, FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
        return $trim ? trim($result) : $result;
    }

    /**
     *
     * @param string $string
     * @return string
     */
    private function sanitizeHtml($string)
    {

        $stringFiltered = (string) filter_var(
            $string,
            FILTER_UNSAFE_RAW,
            FILTER_NULL_ON_FAILURE | FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK
        );
        $stringStripped = strip_tags($stringFiltered);
        $pattern = [
            '/\&/',
            '/</',
            "/>/",
            '/"/',
            "/%/",
            '/\(/',
            '/\)/',
            '/\+/'
        ];
        $replacement = "";

        return (string) preg_replace($pattern, $replacement, $stringStripped);
    }
}
