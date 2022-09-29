<?php

namespace TypeIdentifier\Tests;

use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;

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

/**
 * Description of EffectivePrimitiveTypeTest
 * @example ./vendor/phpunit/phpunit/phpunit --verbose tests/EffectivePrimitiveTypeTest.php 
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
class EffectivePrimitiveTypeTest extends AbstractTestCase {

    public function testPositiveInt(): void {
        $value = 1;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsInt($result);
    }

    public function testNegativeInt(): void {
        $value = -1;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsInt($result);
    }

    public function testZeroInt(): void {
        $value = 0;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertEquals($value, $result);
        $this->assertIsInt($result);
    }

    public function testBoolTrue(): void {
        $value = true;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsBool($result);
    }

    public function testBoolTrueAsString(): void {
        $value = "true";
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsString($result);
    }

    public function testBoolFalse(): void {
        $value = false;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertEquals($value, $result);
        $this->assertIsBool($result);
    }

    public function testBoolTrueCondition(): void {
        $value = 1 === 1;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsBool($result);
    }

    public function testFalseTrueCondition(): void {
        $value = 1 === 0;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsBool($result);
    }

    public function testPositiveFloat(): void {
        $value = 1.1;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsFloat($result);
    }

    public function testNegativeFloat(): void {
        $value = -1.1;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsFloat($result);
    }

    public function testZeroFloat(): void {
        $value = 0.0;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsFloat($result);
    }

    public function testEmptyString(): void {
        $value = "";
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsString($result);
    }

    public function testStringWithSpace(): void {
        $value = "  ";
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsString($result);
    }

    public function testString(): void {
        $value = "snipershady";
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsString($result);
    }

    public function testStringTrimmed(): void {
        $value = "snipershady";
        $whitespaces = "      ";
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value . $whitespaces, true); // Trim enabled
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsString($result);
    }

    public function testStringWithIntegerPrefix(): void {
        $int = 1;
        $value = $int . "snipershady";
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsString($result);
    }

    public function testStringWithIntegerSuffix(): void {
        $int = 1;
        $value = "snipershady" . $int;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsString($result);
    }

    public function testNullValue(): void {
        $value = null;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertNull($result);
    }

    public function testAssociativeArrayIntValue(): void {
        $value = 1;
        $array["value"] = $value;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($array["value"]);
        $this->assertTrue($array["value"] === $result);
        $this->assertEquals($array["value"], $result);
        $this->assertIsInt($result);
    }

    public function testAssociativeArrayIntAsCharValue(): void {
        $value = "1";
        $array["value"] = $value;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($array["value"]);
        $this->assertTrue($array["value"] == $result);
        $this->assertEquals($array["value"], $result);
        $this->assertIsInt($result);
    }

    public function testAssociativeArrayIntWithCharValue(): void {
        $value = "1a";
        $array["value"] = $value;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($array["value"]);
        $this->assertTrue($array["value"] == $result);
        $this->assertEquals($array["value"], $result);
        $this->assertIsString($result);
    }

    public function testAssociativeArrayFloatValue(): void {
        $value = "1.1";
        $array["value"] = $value;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($array["value"]);
        $this->assertTrue($array["value"] == $result);
        $this->assertEquals($array["value"], $result);
        $this->assertIsFloat($result);
    }

    public function testAssociativeArrayFloatWithCharValue(): void {
        $value = "1.1a";
        $array["value"] = $value;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($array["value"]);
        $this->assertTrue($array["value"] === $result);
        $this->assertEquals($array["value"], $result);
        $this->assertIsString($result);
    }

    public function testAssociativeArrayStringValue(): void {
        $value = "snipershady";
        $array["value"] = $value;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($array["value"]);
        $this->assertTrue($array["value"] === $result);
        $this->assertEquals($array["value"], $result);
        $this->assertIsString($result);
    }

    public function testAssociativeArraySantizieMethod(): void {
        $value = "snipershady";
        $array["value"] = $value;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValueFromArray("value", $array);
        $this->assertTrue($array["value"] === $result);
        $this->assertEquals($array["value"], $result);
        $this->assertIsString($result);
    }
    
    public function testAssociativeArraySantizieWitTrimMethod(): void {
        $value = "snipershady    ";
        $array["value"] = $value;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValueFromArray("value", $array, true);
        $this->assertTrue(trim($array["value"]) === $result);
        $this->assertEquals(trim($array["value"]), $result);
        $this->assertIsString($result);
    }
    
    public function testAssociativeArraySantizieIntAsStringWitTrimMethod(): void {
        $value = "1";
        $array["value"] = $value;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValueFromArray("value", $array, true, true);
        $this->assertTrue(trim($array["value"]) === $result);
        $this->assertEquals(trim($array["value"]), $result);
        $this->assertIsString($result);
    }
    
    public function testAssociativeArraySantizieIntWitTrimMethod(): void {
        $value = "1 ";
        $array["value"] = $value;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValueFromArray("value", $array, true);
        $this->assertTrue((int)($array["value"]) === $result);
        $this->assertEquals((int)($array["value"]), $result);
        $this->assertIsInt($result);
    }

}
