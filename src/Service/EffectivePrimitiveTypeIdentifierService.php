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
final class EffectivePrimitiveTypeIdentifierService {

    public function __construct() {
        
    }

    /**
     * <p>Returns strict effective primitive type of a variable</p>
     * @param mixed $data <p>Variable to sanitize and get again with right strict primitive type</p>
     * @param bool $trim <p>Trim value if type is an String</p>
     * @param bool $forceString  <p>Force string parsing for values like "1", so it will be handlet as String and not as integer</p>
     * @return bool|int|float|string|null
     */
    public function getTypedValue($data, $trim = false, $forceString = false) {
        if ($data === null) {
            return null;
        }

        if (!$forceString && is_bool($data)) {
            return $this->getSanitizedBool((bool) $data);
        }
        if (!$forceString && is_numeric($data)) {
            return $this->getSanitizedNumber($data);
        }
        if ($forceString || is_string($data)) {
            return $this->getSanitizedString((string) $data, $trim);
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
    public function getTypedValueFromArray($needle, array $array, $trim = false, $forceString = false) {
        return is_array($array) && array_key_exists($needle, $array) ? $this->getTypedValue($array[$needle], $trim, $forceString) : null;
    }

    /**
     * Return sanitized bool
     * @param bool $value
     * @return bool
     */
    private function getSanitizedBool($value) {

        return (bool) filter_var($value, FILTER_VALIDATE_BOOL);
    }

    /**
     * Return sanitized number
     * @param mixed $value
     *        must be "numeric"
     * @return int|float
     */
    private function getSanitizedNumber($value) {
        $numericvalue = $value + 0;
        if (is_int($numericvalue)) {
            return $this->getSanitizedIntValue((int) $numericvalue);
        } else {
            return $this->getSanitizedFloatValue((float) $numericvalue);
        }
    }

    /**
     * Return sanitized string
     * @param int $value
     * @return int
     */
    private function getSanitizedIntValue($value) {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Return sanitized string 
     * @param float $value
     * @return float
     */
    private function getSanitizedFloatValue($value) {
        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
     * Return sanitized string 
     * @param string $value
     * @param bool $trim
     * @return string
     */
    private function getSanitizedString($value, $trim = false) {
        $result = (string) filter_var($value, FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
        return $trim ? trim($result) : $result;
    }

}
