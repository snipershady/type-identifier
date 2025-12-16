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
        $this->assertTrue($response['is_valid']);
    }

    public function testInputPost(): void
    {
        $response = $this->callEntrypoint('GET', 'typeidentifierinputpost');
        $expectedInputServer = 'typeidentifier';
        $expectedInput = 'typeidentifierinputpost';
        $this->assertEquals($expectedInputServer, $response['agent']);
        $this->assertEquals($expectedInput, $response['value']);
        $this->assertTrue($response['is_valid']);
    }

    private function callEntrypoint(string $httpMethodString, string $inputParameter): array
    {
        $httpMethod = strtoupper($httpMethodString);
        $ch = curl_init();
        $url = 'http://127.0.0.1/tests/entrypoint.php';

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'User-Agent: typeidentifier',
            ],
        ];

        if ('POST' === $httpMethod) {
            $options[CURLOPT_URL] = $url;
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = ['param' => $inputParameter];
        } else {
            $options[CURLOPT_URL] = $url.'?param='.urlencode($inputParameter);
        }

        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);

        return json_decode($response,true);
    }
}
