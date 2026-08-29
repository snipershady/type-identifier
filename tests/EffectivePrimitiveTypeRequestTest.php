<?php

declare(strict_types=1);

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
 * Integration tests for the $_GET / $_POST / $_SERVER accessors over real HTTP.
 *
 * These exist for one reason: readFromInput() reads the SAPI input stream with
 * filter_input() first and only falls back to the $_* array when that returns
 * null. Under CLI filter_input() *always* returns null, so every unit test in
 * this suite exercises the fallback and never the branch that actually runs in
 * production. Driving tests/entrypoint.php over a real request is the only way
 * to cover it.
 *
 * Covered per request, through the SAPI:
 *  - getTypedValueFromGet() / getTypedValueFromPost()
 *  - getIntValueFrom*, getFloatValueFrom*, getBoolValueFrom*, getStringValueFrom*
 *    for the request source
 *  - getTypedValueFromServer() plus its four typed accessors, fed by X-Test-*
 *    request headers
 *  - getTypedValueFromCookie() plus its four typed accessors, fed by real
 *    request cookies through CURLOPT_COOKIE
 *  - the filter_input() branch itself, isolated with the X-Test-Sapi-Only header:
 *    the endpoint empties the superglobal entries first, so a value that still
 *    comes back cannot have arrived through the fallback
 *
 * The endpoint defaults to the docker-compose service and can be pointed
 * anywhere with the TYPEIDENTIFIER_ENDPOINT environment variable, e.g. against
 * PHP's built-in server:
 *
 *     php -S 127.0.0.1:8080 -t . &
 *     TYPEIDENTIFIER_ENDPOINT=http://127.0.0.1:8080/tests/entrypoint.php \
 *         vendor/bin/phpunit --testsuite integration
 *
 * When no endpoint answers, every test skips instead of failing, so a plain
 * `composer test` works with no server around. CI runs this suite with
 * --fail-on-skipped so that a missing endpoint cannot pass silently.
 *
 * @example ./vendor/bin/phpunit --testsuite integration
 *
 * @author Stefano Perrini <perrini.stefano@gmail.com> aka La Matrigna
 */
final class EffectivePrimitiveTypeRequestTest extends AbstractTestCase
{
    private const string DEFAULT_ENDPOINT = 'http://endpoint-test/tests/entrypoint.php';

    private const string USER_AGENT = 'typeidentifier';

    /**
     * Reachability is probed once for the whole class: null = not probed yet.
     */
    private static ?bool $endpointIsUp = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (!$this->endpointIsReachable()) {
            self::markTestSkipped(sprintf(
                'No test endpoint answering at %s. Start one with '
                . '`php -S 127.0.0.1:8080 -t .` and set TYPEIDENTIFIER_ENDPOINT, '
                . 'or run the docker-compose stack.',
                $this->endpoint(),
            ));
        }
    }

    // -------------------------------------------------------------------------
    // getTypedValueFrom{Get,Post}() and getTypedValueFromServer()
    // -------------------------------------------------------------------------

    public function testInputGet(): void
    {
        $response = $this->callEntrypoint('GET', 'typeidentifierinputget');
        $this->assertSame('GET', $response['method']);
        $this->assertSame(self::USER_AGENT, $response['agent']);
        $this->assertSame('typeidentifierinputget', $response['value']);
        $this->assertIsString($response['value']);
        $this->assertTrue($response['is_valid']);
    }

    public function testInputPost(): void
    {
        $response = $this->callEntrypoint('POST', 'typeidentifierinputpost');
        $this->assertSame('POST', $response['method']);
        $this->assertSame(self::USER_AGENT, $response['agent']);
        $this->assertSame('typeidentifierinputpost', $response['value']);
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
        $this->assertEqualsWithDelta(3.14, $response['value'], PHP_FLOAT_EPSILON);
        $this->assertIsFloat($response['value']);
    }

    public function testInputPostNumericStringIsCastToFloat(): void
    {
        $response = $this->callEntrypoint('POST', '3.14');
        $this->assertEqualsWithDelta(3.14, $response['value'], PHP_FLOAT_EPSILON);
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

    // -------------------------------------------------------------------------
    // Typed accessors of the request source, over the SAPI
    // -------------------------------------------------------------------------

    public function testGetTypedAccessorsOverRealGetRequest(): void
    {
        $typed = $this->typedSection($this->callEntrypoint('GET', '42'));

        $this->assertSame(42, $typed['int']);
        $this->assertEqualsWithDelta(42.0, $typed['float'], PHP_FLOAT_EPSILON);
        $this->assertTrue($typed['bool']);
        $this->assertSame('42', $typed['string']);
    }

    public function testPostTypedAccessorsOverRealPostRequest(): void
    {
        $typed = $this->typedSection($this->callEntrypoint('POST', '42'));

        $this->assertSame(42, $typed['int']);
        $this->assertEqualsWithDelta(42.0, $typed['float'], PHP_FLOAT_EPSILON);
        $this->assertTrue($typed['bool']);
        $this->assertSame('42', $typed['string']);
    }

    public function testGetTypedAccessorsOnAFloatValue(): void
    {
        $typed = $this->typedSection($this->callEntrypoint('GET', '3.99'));

        $this->assertSame(3, $typed['int'], 'getIntValueFromGet() must truncate towards zero.');
        $this->assertEqualsWithDelta(3.99, $typed['float'], PHP_FLOAT_EPSILON);
        $this->assertTrue($typed['bool']);
        $this->assertSame('3.99', $typed['string']);
    }

    public function testPostTypedAccessorsOnANonNumericValue(): void
    {
        $typed = $this->typedSection($this->callEntrypoint('POST', 'snipershady'));

        $this->assertSame(0, $typed['int']);
        $this->assertEqualsWithDelta(0.0, $typed['float'], PHP_FLOAT_EPSILON);
        $this->assertTrue($typed['bool'], 'A non-empty string is truthy.');
        $this->assertSame('snipershady', $typed['string']);
    }

    public function testGetTypedAccessorsOnAFalsyValue(): void
    {
        $typed = $this->typedSection($this->callEntrypoint('GET', '0'));

        $this->assertSame(0, $typed['int']);
        $this->assertEqualsWithDelta(0.0, $typed['float'], PHP_FLOAT_EPSILON);
        $this->assertFalse($typed['bool']);
        $this->assertSame('0', $typed['string']);
    }

    public function testTypedAccessorsOnAMissingParameter(): void
    {
        $typed = $this->typedSection($this->callEntrypoint('GET'));

        $this->assertSame(0, $typed['int']);
        $this->assertEqualsWithDelta(0.0, $typed['float'], PHP_FLOAT_EPSILON);
        $this->assertFalse($typed['bool']);
        $this->assertSame('', $typed['string']);
    }

    // -------------------------------------------------------------------------
    // Typed accessors of $_SERVER, fed by X-Test-* request headers
    // -------------------------------------------------------------------------

    public function testServerTypedAccessorsOverRealRequest(): void
    {
        $response = $this->callEntrypoint('GET', 'x', [
            'X-Test-Int: 8080',
            'X-Test-Float: 2.5',
            'X-Test-Bool: 1',
            'X-Test-String:   spinfo  ',
        ]);

        $server = $this->typedSection($response, 'server');

        $this->assertSame(8080, $server['int']);
        $this->assertEqualsWithDelta(2.5, $server['float'], PHP_FLOAT_EPSILON);
        $this->assertTrue($server['bool']);
        $this->assertSame('spinfo', $server['string'], 'getStringValueFromServer() must honour $trim.');
    }

    public function testServerTypedAccessorsOnAbsentHeaders(): void
    {
        $server = $this->typedSection($this->callEntrypoint('GET', 'x'), 'server');

        $this->assertSame(0, $server['int']);
        $this->assertEqualsWithDelta(0.0, $server['float'], PHP_FLOAT_EPSILON);
        $this->assertFalse($server['bool']);
        $this->assertSame('', $server['string']);
    }

    public function testServerTypedAccessorsOnAFalsyHeader(): void
    {
        $server = $this->typedSection(
            $this->callEntrypoint('GET', 'x', ['X-Test-Bool: 0', 'X-Test-Int: -1']),
            'server',
        );

        $this->assertFalse($server['bool']);
        $this->assertSame(-1, $server['int']);
    }

    // -------------------------------------------------------------------------
    // Typed accessors of $_COOKIE, fed by real request cookies
    // -------------------------------------------------------------------------

    public function testCookieTypedAccessorsOverRealRequest(): void
    {
        $response = $this->callEntrypoint('GET', 'x', [], [
            'test_int' => '8080',
            'test_float' => '2.5',
            'test_bool' => '1',
            'test_string' => '  spinfo  ',
        ]);

        $cookie = $this->typedSection($response, 'cookie');

        $this->assertSame(8080, $cookie['int']);
        $this->assertEqualsWithDelta(2.5, $cookie['float'], PHP_FLOAT_EPSILON);
        $this->assertTrue($cookie['bool']);
        $this->assertSame('spinfo', $cookie['string'], 'getStringValueFromCookie() must honour $trim.');
    }

    public function testGetTypedValueFromCookieOverRealRequest(): void
    {
        $response = $this->callEntrypoint('GET', 'x', [], ['test_int' => '8080']);

        $this->assertSame(8080, $response['cookie_value']);
        $this->assertIsInt($response['cookie_value']);
    }

    public function testCookieTypedAccessorsOnAbsentCookies(): void
    {
        $cookie = $this->typedSection($this->callEntrypoint('GET', 'x'), 'cookie');

        $this->assertSame(0, $cookie['int']);
        $this->assertEqualsWithDelta(0.0, $cookie['float'], PHP_FLOAT_EPSILON);
        $this->assertFalse($cookie['bool']);
        $this->assertSame('', $cookie['string']);
    }

    public function testCookieTypedAccessorsOnFalsyCookies(): void
    {
        $cookie = $this->typedSection(
            $this->callEntrypoint('GET', 'x', [], ['test_bool' => '0', 'test_int' => '-1', 'test_float' => '-0.5']),
            'cookie',
        );

        $this->assertFalse($cookie['bool']);
        $this->assertSame(-1, $cookie['int']);
        $this->assertEqualsWithDelta(-0.5, $cookie['float'], PHP_FLOAT_EPSILON);
    }

    /**
     * A cookie also reaches POST requests, which take a different branch in the
     * endpoint: the cookie readers must not depend on the request method.
     */
    public function testCookieTypedAccessorsOverRealPostRequest(): void
    {
        $cookie = $this->typedSection(
            $this->callEntrypoint('POST', 'x', [], ['test_int' => '7', 'test_bool' => '1']),
            'cookie',
        );

        $this->assertSame(7, $cookie['int']);
        $this->assertTrue($cookie['bool']);
    }

    // -------------------------------------------------------------------------
    // Proof that the value really comes from filter_input()
    // -------------------------------------------------------------------------
    //
    // Everything above would pass just as well if readFromInput() only ever used
    // its $_* fallback: over a real request both halves see the same data, so
    // they are behaviourally indistinguishable. The X-Test-Sapi-Only header makes
    // the endpoint empty the superglobal entries before reading, which disarms
    // the fallback: a value that still comes back can only have been read from
    // the SAPI input stream. This is what pins the branch down — the CLI coverage
    // driver cannot see it, because it executes in the server process.

    public function testGetValueIsReadFromTheSapiWhenTheSuperglobalIsEmpty(): void
    {
        $response = $this->callEntrypoint('GET', '42', ['X-Test-Sapi-Only: 1']);

        $this->assertTrue($response['sapi_only'], 'The endpoint did not run in SAPI-only mode.');
        $this->assertSame(42, $response['value'], '$_GET was emptied, so this value proves filter_input(INPUT_GET) ran.');
        $this->assertSame(42, $this->typedSection($response)['int']);
    }

    public function testPostValueIsReadFromTheSapiWhenTheSuperglobalIsEmpty(): void
    {
        $response = $this->callEntrypoint('POST', '42', ['X-Test-Sapi-Only: 1']);

        $this->assertTrue($response['sapi_only']);
        $this->assertSame(42, $response['value'], '$_POST was emptied, so this value proves filter_input(INPUT_POST) ran.');
        $this->assertSame(42, $this->typedSection($response)['int']);
    }

    public function testServerValueIsReadFromTheSapiWhenTheSuperglobalIsEmpty(): void
    {
        $response = $this->callEntrypoint('GET', 'x', ['X-Test-Int: 8080', 'X-Test-Sapi-Only: 1']);

        $this->assertTrue($response['sapi_only']);
        $this->assertSame(
            self::USER_AGENT,
            $response['agent'],
            '$_SERVER[HTTP_USER_AGENT] was unset, so this value proves filter_input(INPUT_SERVER) ran.',
        );
        $this->assertSame(8080, $this->typedSection($response, 'server')['int']);
    }

    public function testCookieValueIsReadFromTheSapiWhenTheSuperglobalIsEmpty(): void
    {
        $response = $this->callEntrypoint(
            'GET',
            'x',
            ['X-Test-Sapi-Only: 1'],
            ['test_int' => '8080', 'test_string' => '  spinfo  '],
        );

        $this->assertTrue($response['sapi_only']);
        $this->assertSame(
            8080,
            $this->typedSection($response, 'cookie')['int'],
            '$_COOKIE was emptied, so this value proves filter_input(INPUT_COOKIE) ran.',
        );
        $this->assertSame('spinfo', $this->typedSection($response, 'cookie')['string']);
    }

    /**
     * Control: without the header the endpoint keeps the superglobals intact, so
     * a failure of the three tests above means the SAPI path broke, not the
     * endpoint.
     */
    public function testSapiOnlyModeIsOffByDefault(): void
    {
        $response = $this->callEntrypoint('GET', '42');
        $this->assertFalse($response['sapi_only']);
        $this->assertSame(42, $response['value']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Extracts a nested section of the response, keyed by accessor name.
     *
     * @param array<array-key, mixed> $response
     *
     * @return array<array-key, mixed>
     */
    private function typedSection(array $response, string $section = 'typed'): array
    {
        $this->assertArrayHasKey($section, $response);
        $values = $response[$section];
        $this->assertIsArray($values);

        foreach (['int', 'float', 'bool', 'string'] as $key) {
            $this->assertArrayHasKey($key, $values, sprintf('Missing %s.%s in the endpoint response.', $section, $key));
        }

        return $values;
    }

    /**
     * The entrypoint URL: TYPEIDENTIFIER_ENDPOINT, or the docker-compose default.
     *
     * @return non-empty-string
     */
    private function endpoint(): string
    {
        $configured = getenv('TYPEIDENTIFIER_ENDPOINT');

        if (is_string($configured)) {
            $trimmed = trim($configured);

            if ('' !== $trimmed) {
                return $trimmed;
            }
        }

        return self::DEFAULT_ENDPOINT;
    }

    /**
     * Probes the endpoint once per class run.
     *
     * Deliberately a GET and not a HEAD: the entrypoint answers 405 to anything
     * that is neither GET nor POST, so a HEAD probe would report a perfectly
     * healthy endpoint as unreachable and silently skip the whole suite.
     */
    private function endpointIsReachable(): bool
    {
        if (null !== self::$endpointIsUp) {
            return self::$endpointIsUp;
        }

        $ch = curl_init();

        if (false === $ch) {
            return self::$endpointIsUp = false;
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->endpoint(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 2,
        ]);
        curl_exec($ch);
        $failed = '' !== curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        return self::$endpointIsUp = !$failed && 200 === $status;
    }

    /**
     * @param list<string>          $extraHeaders additional request headers, "Name: value"
     * @param array<string, string> $cookies      request cookies, sent through CURLOPT_COOKIE
     *
     * @return array<array-key, mixed> decoded JSON body returned by the test entrypoint
     */
    private function callEntrypoint(string $httpMethodString, ?string $inputParameter = null, array $extraHeaders = [], array $cookies = []): array
    {
        $httpMethod = strtoupper($httpMethodString);
        $url = $this->endpoint();
        $ch = curl_init();
        $this->assertNotFalse($ch, 'curl_init() failed to initialise a cURL handle.');

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => array_merge(['User-Agent: ' . self::USER_AGENT], $extraHeaders),
        ];

        if ('POST' === $httpMethod) {
            $options[CURLOPT_URL] = $url;
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = null === $inputParameter ? [] : ['param' => $inputParameter];
        } else {
            $options[CURLOPT_URL] = null === $inputParameter ? $url : $url . '?param=' . urlencode($inputParameter);
        }

        if ([] !== $cookies) {
            $pairs = [];
            foreach ($cookies as $name => $value) {
                // rawurlencode(), not urlencode(): PHP decodes cookie values with
                // rawurldecode() semantics, so a space encoded as "+" arrives as a
                // literal "+" instead of a space.
                $pairs[] = $name . '=' . rawurlencode($value);
            }

            $options[CURLOPT_COOKIE] = implode('; ', $pairs);
        }

        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        $this->assertSame('', $error, 'cURL request to the test entrypoint failed: ' . $error);
        $this->assertIsString($response);
        $this->assertSame(200, $status, 'Unexpected HTTP status from the test entrypoint. Body: ' . $response);

        $decoded = json_decode($response, associative: true);
        $this->assertIsArray($decoded, 'The test entrypoint did not return JSON. Body: ' . $response);

        return $decoded;
    }
}
