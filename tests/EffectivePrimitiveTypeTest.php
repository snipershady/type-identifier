<?php

namespace TypeIdentifier\Tests;

use TypeIdentifier\Service\EffectivePrimitiveTypeIdentifierService;

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

/**
 * Unit tests for EffectivePrimitiveTypeIdentifierService::getTypedValue() and
 * EffectivePrimitiveTypeIdentifierService::getTypedValueFromArray().
 *
 * Each test group exercises a specific type-resolution or sanitization path:
 *
 *  - Integer literals and numeric strings → int
 *  - Float literals and numeric strings   → float
 *  - Boolean literals                     → bool
 *  - String literals, incl. trim support  → string
 *  - Null                                 → null
 *  - Arrays (flat and nested)             → typed array
 *  - $forceString = true                  → numeric strings kept as string
 *  - $sanitizeHtml = true                 → HTML/XSS characters stripped
 *  - getTypedValueFromArray edge cases    → missing key, null source array
 *
 * @example ./vendor/bin/phpunit tests/EffectivePrimitiveTypeTest.php
 * @example ./vendor/bin/phpunit tests/EffectivePrimitiveTypeTest.php --colors="auto" --debug
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
class EffectivePrimitiveTypeTest extends AbstractTestCase
{
    public function testPositiveInt(): void
    {
        $value = 1;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsInt($result);
    }

    public function testNegativeInt(): void
    {
        $value = -1;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsInt($result);
    }

    public function testZeroInt(): void
    {
        $value = 0;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertEquals($value, $result);
        $this->assertIsInt($result);
    }

    public function testBoolTrue(): void
    {
        $value = true;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsBool($result);
    }

    public function testBoolTrueAsString(): void
    {
        $value = 'true';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsString($result);
    }

    public function testBoolFalse(): void
    {
        $value = false;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertEquals($value, $result);
        $this->assertIsBool($result);
    }

    public function testBoolTrueCondition(): void
    {
        $value = 1 === 1;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsBool($result);
    }

    public function testFalseTrueCondition(): void
    {
        $value = 1 === 0;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsBool($result);
    }

    public function testPositiveFloat(): void
    {
        $value = 1.1;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsFloat($result);
    }

    public function testNegativeFloat(): void
    {
        $value = -1.1;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsFloat($result);
    }

    public function testZeroFloat(): void
    {
        $value = 0.0;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsFloat($result);
    }

    public function testEmptyString(): void
    {
        $value = '';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsString($result);
    }

    public function testStringWithSpace(): void
    {
        $value = '  ';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsString($result);
    }

    public function testString(): void
    {
        $value = 'snipershady';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsString($result);
    }

    public function testStringTrimmed(): void
    {
        $value = 'snipershady';
        $whitespaces = '      ';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value.$whitespaces, true); // Trim enabled
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsString($result);
    }

    public function testStringWithIntegerPrefix(): void
    {
        $int = 1;
        $value = $int.'snipershady';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsString($result);
    }

    public function testStringWithIntegerSuffix(): void
    {
        $int = 1;
        $value = 'snipershady'.$int;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsString($result);
    }

    public function testNullValue(): void
    {
        $value = null;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertNull($result);
    }

    public function testAssociativeArrayIntValue(): void
    {
        $value = 1;
        $array['value'] = $value;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($array['value']);
        $this->assertTrue($array['value'] === $result);
        $this->assertEquals($array['value'], $result);
        $this->assertIsInt($result);
    }

    public function testAssociativeArrayIntAsCharValue(): void
    {
        $value = '1';
        $array['value'] = $value;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($array['value']);
        $this->assertTrue($array['value'] == $result);
        $this->assertEquals($array['value'], $result);
        $this->assertIsInt($result);
    }

    public function testAssociativeArrayIntWithCharValue(): void
    {
        $value = '1a';
        $array['value'] = $value;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($array['value']);
        $this->assertTrue($array['value'] == $result);
        $this->assertEquals($array['value'], $result);
        $this->assertIsString($result);
    }

    public function testAssociativeArrayFloatValue(): void
    {
        $value = '1.1';
        $array['value'] = $value;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($array['value']);
        $this->assertTrue($array['value'] == $result);
        $this->assertEquals($array['value'], $result);
        $this->assertIsFloat($result);
    }

    public function testAssociativeArrayFloatWithCharValue(): void
    {
        $value = '1.1a';
        $array['value'] = $value;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($array['value']);
        $this->assertTrue($array['value'] === $result);
        $this->assertEquals($array['value'], $result);
        $this->assertIsString($result);
    }

    public function testAssociativeArrayStringValue(): void
    {
        $value = 'snipershady';
        $array['value'] = $value;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($array['value']);
        $this->assertTrue($array['value'] === $result);
        $this->assertEquals($array['value'], $result);
        $this->assertIsString($result);
    }

    public function testAssociativeArraySantizieMethod(): void
    {
        $value = 'snipershady';
        $array['value'] = $value;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValueFromArray('value', $array);
        $this->assertIsString($result);
        $this->assertTrue($array['value'] === $result);
        $this->assertEquals($array['value'], $result);
    }

    public function testAssociativeArraySantizieWitTrimMethod(): void
    {
        $value = 'snipershady    ';
        $array['value'] = $value;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValueFromArray('value', $array, true);
        $this->assertTrue(trim($array['value']) === $result);
        $this->assertEquals(trim($array['value']), $result);
        $this->assertIsString($result);
    }

    public function testAssociativeArraySantizieIntAsStringWitTrimMethod(): void
    {
        $value = '1';
        $array['value'] = $value;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValueFromArray('value', $array, true, true);
        $this->assertTrue(trim($array['value']) === $result);
        $this->assertEquals(trim($array['value']), $result);
        $this->assertIsString($result);
    }

    public function testAssociativeArraySantizieIntWitTrimMethod(): void
    {
        $value = '1 ';
        $array['value'] = $value;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValueFromArray('value', $array, true);
        $this->assertTrue((int) $array['value'] === $result);
        $this->assertEquals((int) $array['value'], $result);
        $this->assertIsInt($result);
    }

    public function testStringWithHTML(): void
    {
        $value = 'asd"><Svg Only=1 OnLoad=confirm(document.cookie)>';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value, true, true, true);
        $this->assertTrue($value !== $result);
        $this->assertNotEquals($value, $result);
        $this->assertIsString($result);
    }

    public function testStringWithHTMLTwo(): void
    {
        $value = '><svg/onload=confirm(1)';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value, true, true, true);
        $this->assertTrue($value !== $result);
        $this->assertNotEquals($value, $result);
        $this->assertIsString($result);
    }

    public function testStringWithHTMLThree(): void
    {
        $value = 'asd"><Svg Only=1 OnLoad=confirm(document.cookie)>';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value, true, true, true);
        $this->assertTrue($value !== $result);
        $this->assertNotEquals($value, $result);
        $this->assertIsString($result);
        $expected = 'asd';
        $this->assertEquals($expected, $result);
    }

    public function testStringWithHTMLFour(): void
    {
        $value = '<p>Ciao <b>Stefano</b>! <script>alert("XSS");</script> &copy;</p>';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value, true, true, true);
        $this->assertTrue($value !== $result);
        $this->assertNotEquals($value, $result);
        $this->assertIsString($result);
        $expected = 'Ciao Stefano! alertXSS; ©';
        $this->assertEquals($expected, $result);
    }

    public function testPlainStringWithHtmlSanitizer(): void
    {
        $value = "C'era una volta, cappuccetto rosso. E c'era anche un lupo cattivo!";
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value, true, true, true);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsString($result);
    }

    public function testStringWithHTMLNoSanitizing(): void
    {
        $value = 'asd"><Svg Only=1 OnLoad=confirm(document.cookie)>';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value, true, true);
        $this->assertTrue($value === $result);
        $this->assertEquals($value, $result);
        $this->assertIsString($result);
        $result = $ept->getTypedValue($value, true, true, true);
        $this->assertTrue($value !== $result);
        $this->assertNotEquals($value, $result);
        $this->assertIsString($result);
    }

    public function testArray(): void
    {
        $value = [
            'key1' => [
                'key1.1' => '1', 'key2.1' => '1',
            ],
        ];
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value['key1'], true);
        // test is array
        $this->assertIsArray($result);
        // test expected result
        $expected = $value['key1'];
        $this->assertEquals($result, $expected);
        // test type
        $this->assertIsInt($result['key1.1']);
    }

    public function testMultidimensionalArray(): void
    {
        $value = [
            [
                'id' => 1,
                'nome' => 'Stefano',
            ],
        ];

        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValueFromArray('0', $value);
        // test is array
        $this->assertIsArray($result);

        // test expected result
        $resultArrayId = $ept->getTypedValueFromArray('id', $result);
        $resultArrayNome = $ept->getTypedValueFromArray('nome', $result);

        $this->assertEquals(1, $resultArrayId);
        $this->assertEquals('Stefano', $resultArrayNome);
    }

    // -------------------------------------------------------------------------
    // forceString = true
    // -------------------------------------------------------------------------

    /**
     * A numeric string "1" must be returned as string when $forceString is true.
     */
    public function testForceStringWithNumericIntString(): void
    {
        $value = '1';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value, false, true);
        $this->assertIsString($result);
        $this->assertSame($value, $result);
    }

    /**
     * A numeric string "3.14" must be returned as string when $forceString is true.
     */
    public function testForceStringWithNumericFloatString(): void
    {
        $value = '3.14';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value, false, true);
        $this->assertIsString($result);
        $this->assertSame($value, $result);
    }

    /**
     * A negative numeric string "-42" must be returned as string when $forceString is true.
     */
    public function testForceStringWithNegativeNumericString(): void
    {
        $value = '-42';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value, false, true);
        $this->assertIsString($result);
        $this->assertSame($value, $result);
    }

    /**
     * "0" must be returned as string (not int 0) when $forceString is true.
     */
    public function testForceStringWithZeroString(): void
    {
        $value = '0';
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value, false, true);
        $this->assertIsString($result);
        $this->assertSame($value, $result);
    }

    // -------------------------------------------------------------------------
    // Numeric string type promotion (forceString = false, default)
    // -------------------------------------------------------------------------

    /**
     * The string "0" without forceString must resolve to integer 0.
     */
    public function testNumericStringZeroResolvesToInt(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue('0');
        $this->assertIsInt($result);
        $this->assertSame(0, $result);
    }

    /**
     * The string "-1" without forceString must resolve to integer -1.
     */
    public function testNegativeNumericStringResolvesToInt(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue('-1');
        $this->assertIsInt($result);
        $this->assertSame(-1, $result);
    }

    /**
     * The string "-1.5" without forceString must resolve to float -1.5.
     */
    public function testNegativeNumericFloatStringResolvesToFloat(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue('-1.5');
        $this->assertIsFloat($result);
        $this->assertSame(-1.5, $result);
    }

    // -------------------------------------------------------------------------
    // Large integers
    // -------------------------------------------------------------------------

    /**
     * PHP_INT_MAX must be preserved as integer.
     */
    public function testPhpIntMax(): void
    {
        $value = PHP_INT_MAX;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertIsInt($result);
        $this->assertSame($value, $result);
    }

    /**
     * PHP_INT_MIN must be preserved as integer.
     */
    public function testPhpIntMin(): void
    {
        $value = PHP_INT_MIN;
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value);
        $this->assertIsInt($result);
        $this->assertSame($value, $result);
    }

    // -------------------------------------------------------------------------
    // getTypedValueFromArray edge cases
    // -------------------------------------------------------------------------

    /**
     * When the requested key does not exist in the array, null must be returned.
     */
    public function testGetTypedValueFromArrayMissingKeyReturnsNull(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValueFromArray('missing', ['other' => 'value']);
        $this->assertNull($result);
    }

    /**
     * When $array is null, null must be returned regardless of the needle.
     */
    public function testGetTypedValueFromArrayNullArrayReturnsNull(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValueFromArray('key', null);
        $this->assertNull($result);
    }

    /**
     * When the value stored under the key is null, null must be returned.
     */
    public function testGetTypedValueFromArrayNullValueReturnsNull(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValueFromArray('key', ['key' => null]);
        $this->assertNull($result);
    }

    /**
     * An empty array passed directly to getTypedValue must return an empty array.
     */
    public function testGetTypedValueEmptyArray(): void
    {
        $ept = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue([]);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // -------------------------------------------------------------------------
    // Mixed-type array processing
    // -------------------------------------------------------------------------

    /**
     * getTypedValue on a flat array with mixed types must return each element
     * cast to its effective primitive type.
     */
    public function testGetTypedValueMixedTypeArray(): void
    {
        $input = [
            'int'    => '42',
            'float'  => '3.14',
            'string' => 'hello',
            'null'   => null,
            'bool'   => true,
        ];

        $ept    = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($input);

        $this->assertIsArray($result);
        $this->assertIsInt($result['int']);
        $this->assertSame(42, $result['int']);
        $this->assertIsFloat($result['float']);
        $this->assertSame(3.14, $result['float']);
        $this->assertIsString($result['string']);
        $this->assertSame('hello', $result['string']);
        $this->assertNull($result['null']);
        $this->assertIsBool($result['bool']);
        $this->assertTrue($result['bool']);
    }

    /**
     * getTypedValue on a flat array with $forceString = true must keep every
     * numeric string as a string.
     */
    public function testGetTypedValueMixedTypeArrayForceString(): void
    {
        $input = [
            'int_string'   => '42',
            'float_string' => '3.14',
            'plain_string' => 'hello',
        ];

        $ept    = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($input, false, true);

        $this->assertIsString($result['int_string']);
        $this->assertSame('42', $result['int_string']);
        $this->assertIsString($result['float_string']);
        $this->assertSame('3.14', $result['float_string']);
        $this->assertIsString($result['plain_string']);
    }

    // -------------------------------------------------------------------------
    // HTML sanitization edge cases
    // -------------------------------------------------------------------------

    /**
     * A string without any HTML must be unchanged after HTML sanitization.
     */
    public function testSanitizeHtmlOnCleanStringIsUnchanged(): void
    {
        $value = "C'era una volta, cappuccetto rosso.";
        $ept   = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value, false, false, true);
        $this->assertIsString($result);
        $this->assertSame($value, $result);
    }

    /**
     * A double-encoded XSS payload (&lt;script&gt;alert(1)&lt;/script&gt;) must be
     * fully neutralised by the corrected pipeline order:
     *  1. html_entity_decode() → &lt;script&gt; becomes <script>
     *  2. strip_tags()         → <script>...</script> is removed entirely
     *  3. preg_replace()       → ( and ) are stripped from alert(1)
     *
     * Expected result: 'alert1' — inert plain text with no executable syntax.
     */
    public function testSanitizeHtmlDoubleEncodedXss(): void
    {
        $value  = '&lt;script&gt;alert(1)&lt;/script&gt;';
        $ept    = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value, false, false, true);
        $this->assertIsString($result);
        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
        $this->assertStringNotContainsString('(', $result);
        $this->assertStringNotContainsString(')', $result);
        $this->assertStringNotContainsString('script', $result);
        $this->assertSame('alert1', $result);
    }

    /**
     * An HTML string containing only safe text must keep only that text after
     * sanitization (tags stripped, entities decoded).
     */
    public function testSanitizeHtmlKeepsInnerText(): void
    {
        $value    = '<strong>Hello</strong>';
        $ept      = new EffectivePrimitiveTypeIdentifierService();
        $result   = $ept->getTypedValue($value, true, false, true);
        $this->assertIsString($result);
        $this->assertSame('Hello', $result);
    }

    /**
     * Trim must be applied after HTML sanitization.
     */
    public function testSanitizeHtmlWithTrim(): void
    {
        $value  = '  <b>word</b>  ';
        $ept    = new EffectivePrimitiveTypeIdentifierService();
        $result = $ept->getTypedValue($value, true, false, true);
        $this->assertIsString($result);
        $this->assertSame('word', $result);
    }
}
