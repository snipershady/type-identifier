<?php

namespace TypeIdentifier\Tests;

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
 * Description of EffectivePrimitiveTypeTest.
 *
 * Integration tests that exercise tests/entrypoint.php over a real HTTP
 * request (GET and POST), verifying that EffectivePrimitiveTypeIdentifierService
 * correctly types values read from the $_GET / $_POST superglobals.
 *
 * @example ./vendor/bin/phpunit tests/EffectivePrimitiveTypeRequestTest.php
 * @example ./vendor/bin/phpunit tests/EffectivePrimitiveTypeRequestTest.php --colors="auto" --debug
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
class EffectivePrimitiveTypeRequestTest extends AbstractTestCase
{
    public function testInputGet(): void
    {
        $response = $this->callEntrypoint('GET', 'typeidentifierinputget');
        $expectedInputServer = 'typeidentifier';
        $expectedInput = 'typeidentifierinputget';
        $this->assertEquals($expectedInputServer, $response['agent']);
        $this->assertEquals($expectedInput, $response['value']);
        $this->assertIsString($response['value']);
        $this->assertTrue($response['is_valid']);
    }

    public function testInputPost(): void
    {
        $response = $this->callEntrypoint('POST', 'typeidentifierinputpost');
        $expectedInputServer = 'typeidentifier';
        $expectedInput = 'typeidentifierinputpost';
        $this->assertEquals($expectedInputServer, $response['agent']);
        $this->assertEquals($expectedInput, $response['value']);
        $this->assertIsString($response['value']);
        $this->assertTrue($response['is_valid']);
    }

    public function testInputGetNumericStringIsCastToInt(): void
    {
        $response = $this->callEntrypoint('GET', '42');
        $this->assertSame(42, $response['value']);
        $this->assertIsInt($response['value']);
    }

    public function testInputPostNumericStringIsCastToInt(): void
    {
        $response = $this->callEntrypoint('POST', '42');
        $this->assertSame(42, $response['value']);
        $this->assertIsInt($response['value']);
    }

    public function testInputGetNumericStringIsCastToFloat(): void
    {
        $response = $this->callEntrypoint('GET', '3.14');
        $this->assertSame(3.14, $response['value']);
        $this->assertIsFloat($response['value']);
    }

    public function testInputPostNumericStringIsCastToFloat(): void
    {
        $response = $this->callEntrypoint('POST', '3.14');
        $this->assertSame(3.14, $response['value']);
        $this->assertIsFloat($response['value']);
    }

    public function testInputGetNegativeNumericString(): void
    {
        $response = $this->callEntrypoint('GET', '-7');
        $this->assertSame(-7, $response['value']);
        $this->assertIsInt($response['value']);
    }

    public function testInputPostNegativeNumericString(): void
    {
        $response = $this->callEntrypoint('POST', '-7');
        $this->assertSame(-7, $response['value']);
        $this->assertIsInt($response['value']);
    }

    public function testInputGetEmptyValueReturnsEmptyString(): void
    {
        $response = $this->callEntrypoint('GET', '');
        $this->assertSame('', $response['value']);
        $this->assertIsString($response['value']);
    }

    public function testInputPostEmptyValueReturnsEmptyString(): void
    {
        $response = $this->callEntrypoint('POST', '');
        $this->assertSame('', $response['value']);
        $this->assertIsString($response['value']);
    }

    public function testInputGetMissingParamReturnsNull(): void
    {
        $response = $this->callEntrypoint('GET');
        $this->assertNull($response['value']);
    }

    public function testInputPostMissingParamReturnsNull(): void
    {
        $response = $this->callEntrypoint('POST');
        $this->assertNull($response['value']);
    }

    public function testInputGetBooleanLikeStringStaysString(): void
    {
        $response = $this->callEntrypoint('GET', 'true');
        $this->assertSame('true', $response['value']);
        $this->assertIsString($response['value']);
    }

    public function testInputPostBooleanLikeStringStaysString(): void
    {
        $response = $this->callEntrypoint('POST', 'true');
        $this->assertSame('true', $response['value']);
        $this->assertIsString($response['value']);
    }

    private function callEntrypoint(string $httpMethodString, ?string $inputParameter = null): array
    {
        $httpMethod = strtoupper($httpMethodString);
        $ch = curl_init();
        $url = 'http://endpoint-test/tests/entrypoint.php';
        // $url = 'http://epti.com/entrypoint.php';
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'User-Agent: typeidentifier',
            ],
        ];

        if ('POST' === $httpMethod) {
            $options[CURLOPT_URL] = $url;
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = null === $inputParameter ? [] : ['param' => $inputParameter];
        } else {
            $options[CURLOPT_URL] = null === $inputParameter ? $url : $url . '?param=' . urlencode($inputParameter);
        }

        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);

        return json_decode($response, true);
    }
}
